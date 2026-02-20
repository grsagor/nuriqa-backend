<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Yajra\DataTables\DataTables;

class WithdrawalController extends Controller
{
    public function index()
    {
        // Statistics
        $totalRequested = Withdrawal::sum('amount');
        $pendingAmount = Withdrawal::where('status', 'pending')->sum('amount');
        $completedAmount = Withdrawal::where('status', 'completed')->sum('amount');
        $rejectedAmount = Withdrawal::where('status', 'rejected')->sum('amount');

        return view('backend.pages.withdrawals.index', compact(
            'totalRequested',
            'pendingAmount',
            'completedAmount',
            'rejectedAmount'
        ));
    }

    public function list(Request $request)
    {
        if (request()->ajax()) {
            $query = Withdrawal::with(['user', 'wallet', 'paymentMethod', 'processor']);

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter by amount range
            if ($request->amount_from) {
                $query->where('amount', '>=', $request->amount_from);
            }
            if ($request->amount_to) {
                $query->where('amount', '<=', $request->amount_to);
            }

            return DataTables::of($query)
                ->addColumn('id', function ($row) {
                    return '#'.$row->id;
                })
                ->addColumn('user', function ($row) {
                    if ($row->user) {
                        return '<div><strong>'.$row->user->name.'</strong><br><small class="text-muted">'.$row->user->email.'</small></div>';
                    }

                    return 'N/A';
                })
                ->addColumn('amount', function ($row) {
                    return '<span class="fw-bold">$'.number_format($row->amount, 2).'</span>';
                })
                ->addColumn('status', function ($row) {
                    $badges = [
                        'pending' => '<span class="badge bg-warning">Pending</span>',
                        'approved' => '<span class="badge bg-info">Approved</span>',
                        'completed' => '<span class="badge bg-success">Completed</span>',
                        'rejected' => '<span class="badge bg-danger">Rejected</span>',
                        'cancelled' => '<span class="badge bg-secondary">Cancelled</span>',
                    ];

                    return $badges[$row->status] ?? '<span class="badge bg-secondary">'.$row->status.'</span>';
                })
                ->addColumn('payment_method', function ($row) {
                    if ($row->paymentMethod) {
                        $type = ucfirst($row->paymentMethod->type);
                        $accountName = $row->paymentMethod->account_name ?? 'N/A';
                        $default = $row->paymentMethod->is_default ? '<span class="badge bg-primary">Default</span>' : '';

                        return '<div><strong>'.$type.'</strong><br><small class="text-muted">'.$accountName.' '.$default.'</small></div>';
                    }
                    if ($row->payment_details && is_array($row->payment_details)) {
                        $type = ucfirst($row->payment_details['type'] ?? $row->payment_method ?? 'N/A');
                        $accountName = $row->payment_details['account_name'] ?? 'N/A';

                        return '<div><strong>'.$type.'</strong><br><small class="text-muted">'.$accountName.'</small></div>';
                    }

                    return '<span class="text-muted">N/A</span>';
                })
                ->addColumn('requested', function ($row) {
                    return $row->created_at ? Carbon::parse($row->created_at)->format('M j, Y g:i A') : '-';
                })
                ->addColumn('processed', function ($row) {
                    return $row->processed_at ? Carbon::parse($row->processed_at)->format('M j, Y g:i A') : '-';
                })
                ->addColumn('processed_by', function ($row) {
                    if ($row->processed_by && $row->processor) {
                        return $row->processor->name;
                    }

                    return '-';
                })
                ->addColumn('action', function ($row) {
                    $view = '<a href="'.route('admin.withdrawals.show', $row->id).'" class="btn btn-sm btn-info" title="View Details"><i class="fas fa-eye"></i></a>';

                    // Get wallet ID - wallet relationship uses user_id, so we need to find wallet by user_id
                    $wallet = Wallet::where('user_id', $row->user_id)->first();
                    $walletLink = '';
                    if ($wallet) {
                        $walletLink = '<a href="'.route('admin.wallets.show', $wallet->id).'" class="btn btn-sm btn-primary" title="View Wallet"><i class="fas fa-wallet"></i></a>';
                    }

                    $actions = $view;
                    if ($walletLink) {
                        $actions .= ' '.$walletLink;
                    }

                    if ($row->status === 'pending') {
                        $approve = '<form method="POST" action="'.route('admin.withdrawals.approve', $row->id).'" class="d-inline">'.csrf_field().'<button type="submit" class="btn btn-sm btn-success" title="Approve" onclick="return confirm(\'Are you sure you want to approve this withdrawal?\')"><i class="fas fa-check"></i></button></form>';
                        $reject = '<button type="button" class="btn btn-sm btn-danger" onclick="showRejectForm('.$row->id.')" title="Reject"><i class="fas fa-times"></i></button>';
                        $actions .= ' '.$approve.' '.$reject;
                    }

                    return $actions;
                })
                ->rawColumns(['id', 'user', 'amount', 'status', 'payment_method', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function show($id)
    {
        $withdrawal = Withdrawal::with([
            'user',
            'wallet',
            'paymentMethod',
        ])->findOrFail($id);

        return view('backend.pages.withdrawals.show', compact('withdrawal'));
    }

    public function approve($id)
    {
        $withdrawal = Withdrawal::with(['wallet', 'user'])->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending withdrawals can be approved');
        }

        $wallet = Wallet::getOrCreateForUser($withdrawal->user_id);
        if ((float) $withdrawal->amount > (float) $wallet->available_balance) {
            return back()->with('error', 'Insufficient wallet balance to approve this withdrawal.');
        }

        DB::transaction(function () use ($withdrawal, $wallet) {
            $wallet->withdraw($withdrawal->amount);

            $withdrawal->update([
                'status' => 'completed',
                'processed_at' => now(),
                'admin_notes' => request('admin_notes'),
                'processed_by' => auth()->id(),
            ]);

            try {
                Mail::to($withdrawal->user->email)->send(new \App\Mail\WithdrawalApproved($withdrawal));
            } catch (\Exception $e) {
                \Log::error('Failed to send withdrawal approval email: '.$e->getMessage());
            }
        });

        return back()->with('success', 'Withdrawal approved successfully');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $withdrawal = Withdrawal::with('user')->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending withdrawals can be rejected');
        }

        DB::transaction(function () use ($withdrawal, $request) {
            $withdrawal->update([
                'status' => 'rejected',
                'processed_at' => now(),
                'rejection_reason' => $request->rejection_reason,
                'processed_by' => auth()->id(),
            ]);

            try {
                Mail::to($withdrawal->user->email)->send(new \App\Mail\WithdrawalRejected($withdrawal));
            } catch (\Exception $e) {
                \Log::error('Failed to send withdrawal rejection email: '.$e->getMessage());
            }
        });

        return back()->with('success', 'Withdrawal rejected.');
    }

    public function statistics()
    {
        $stats = [
            'total_withdrawals' => Withdrawal::count(),
            'total_amount' => Withdrawal::sum('amount'),
            'pending_count' => Withdrawal::where('status', 'pending')->count(),
            'pending_amount' => Withdrawal::where('status', 'pending')->sum('amount'),
            'completed_count' => Withdrawal::where('status', 'completed')->count(),
            'completed_amount' => Withdrawal::where('status', 'completed')->sum('amount'),
            'rejected_count' => Withdrawal::where('status', 'rejected')->count(),
            'rejected_amount' => Withdrawal::where('status', 'rejected')->sum('amount'),
        ];

        // Monthly statistics for the last 12 months
        $monthlyStats = Withdrawal::selectRaw('
            DATE_FORMAT(created_at, "%Y-%m") as month,
            COUNT(*) as count,
            SUM(amount) as total
        ')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        return view('backend.pages.withdrawals.statistics', compact('stats', 'monthlyStats'));
    }
}
