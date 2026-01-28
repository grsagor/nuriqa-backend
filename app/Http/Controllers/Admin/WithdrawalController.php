<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WithdrawalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index(Request $request)
    {
        $query = Withdrawal::with(['user', 'wallet', 'paymentMethod']);

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

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(50);

        // Statistics
        $totalRequested = Withdrawal::sum('amount');
        $pendingAmount = Withdrawal::where('status', 'pending')->sum('amount');
        $completedAmount = Withdrawal::where('status', 'completed')->sum('amount');
        $rejectedAmount = Withdrawal::where('status', 'rejected')->sum('amount');

        return view('backend.pages.withdrawals.index', compact(
            'withdrawals',
            'totalRequested',
            'pendingAmount',
            'completedAmount',
            'rejectedAmount'
        ));
    }

    public function show($id)
    {
        $withdrawal = Withdrawal::with([
            'user',
            'wallet',
            'paymentMethod'
        ])->findOrFail($id);

        return view('backend.pages.withdrawals.show', compact('withdrawal'));
    }

    public function approve($id)
    {
        $withdrawal = Withdrawal::with('wallet')->findOrFail($id);
        
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending withdrawals can be approved');
        }

        DB::transaction(function() use ($withdrawal) {
            $withdrawal->update([
                'status' => 'approved',
                'processed_at' => now(),
                'admin_notes' => request('admin_notes'),
                'processed_by' => auth()->id()
            ]);

            // Mark withdrawal as completed for now (in real scenario, this would be done after payment processing)
            $withdrawal->update([
                'status' => 'completed'
            ]);

            // Send notification to user
            try {
                Mail::to($withdrawal->user->email)->send(new \App\Mail\WithdrawalApproved($withdrawal));
            } catch (\Exception $e) {
                \Log::error('Failed to send withdrawal approval email: ' . $e->getMessage());
            }
        });

        return back()->with('success', 'Withdrawal approved successfully');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        $withdrawal = Withdrawal::with('wallet')->findOrFail($id);
        
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending withdrawals can be rejected');
        }

        DB::transaction(function() use ($withdrawal, $request) {
            // Return the amount to available balance
            $withdrawal->wallet->increment('available_balance', $withdrawal->amount);

            $withdrawal->update([
                'status' => 'rejected',
                'processed_at' => now(),
                'rejection_reason' => $request->rejection_reason,
                'processed_by' => auth()->id()
            ]);

            // Send notification to user
            try {
                Mail::to($withdrawal->user->email)->send(new \App\Mail\WithdrawalRejected($withdrawal));
            } catch (\Exception $e) {
                \Log::error('Failed to send withdrawal rejection email: ' . $e->getMessage());
            }
        });

        return back()->with('success', 'Withdrawal rejected and amount returned to wallet');
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