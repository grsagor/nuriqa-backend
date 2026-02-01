<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class WalletController extends Controller
{
    public function index()
    {
        return view('backend.pages.wallets.index');
    }

    public function list()
    {
        if (request()->ajax()) {
            $data = Wallet::with('user')
                ->selectRaw('wallets.*, (available_balance + pending_balance) as total_balance')
                ->latest();

            return DataTables::of($data)
                ->addColumn('user', function ($row) {
                    return $row->user ? $row->user->name : 'N/A';
                })
                ->addColumn('email', function ($row) {
                    return $row->user ? $row->user->email : 'N/A';
                })
                ->addColumn('available_balance', function ($row) {
                    return '<span class="text-success fw-bold">$'.number_format($row->available_balance, 2).'</span>';
                })
                ->addColumn('pending_balance', function ($row) {
                    return '<span class="text-warning fw-bold">$'.number_format($row->pending_balance, 2).'</span>';
                })
                ->addColumn('total_balance', function ($row) {
                    return '<span class="text-primary fw-bold">$'.number_format($row->total_balance, 2).'</span>';
                })
                ->addColumn('status', function ($row) {
                    if ($row->available_balance > 0) {
                        return '<span class="badge bg-success">Active</span>';
                    } else {
                        return '<span class="badge bg-secondary">No Balance</span>';
                    }
                })
                ->addColumn('created', function ($row) {
                    return $row->created_at ? Carbon::parse($row->created_at)->format('M j, Y') : 'N/A';
                })
                ->addColumn('action', function ($row) {
                    $view = '<a href="'.route('admin.wallets.show', $row->id).'" class="btn btn-sm btn-info" title="View Details"><i class="fas fa-eye"></i></a>';
                    $edit = '<a href="'.route('admin.wallets.edit', $row->id).'" class="btn btn-sm btn-primary" title="Edit Wallet"><i class="fas fa-edit"></i></a>';
                    $transactions = '<a href="'.route('admin.wallets.transactions', $row->id).'" class="btn btn-sm btn-primary" title="View Transactions"><i class="fas fa-exchange-alt"></i></a>';
                    
                    return $view.' '.$edit.' '.$transactions;
                })
                ->rawColumns(['available_balance', 'pending_balance', 'total_balance', 'status', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function show($id)
    {
        $wallet = Wallet::with(['user', 'withdrawals' => function($query) {
            $query->orderBy('created_at', 'desc')->take(20);
        }])->findOrFail($id);

        $withdrawals = $wallet->withdrawals()->paginate(10);
        
        return view('backend.pages.wallets.show', compact('wallet', 'withdrawals'));
    }

    public function edit($id)
    {
        $wallet = Wallet::with('user')->findOrFail($id);
        return view('backend.pages.wallets.edit', compact('wallet'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'available_balance' => 'required|numeric|min:0',
            'pending_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        $wallet = Wallet::findOrFail($id);
        $oldAvailable = $wallet->available_balance;
        $oldPending = $wallet->pending_balance;

        $wallet->update([
            'available_balance' => $request->available_balance,
            'pending_balance' => $request->pending_balance,
            'total_earnings' => $request->available_balance + $request->pending_balance,
            'notes' => $request->notes
        ]);

        // Log the adjustment
        $wallet->adjustments()->create([
            'type' => 'admin_adjustment',
            'amount' => $request->available_balance - $oldAvailable,
            'description' => $request->notes ?? 'Admin adjustment',
            'admin_id' => auth()->id(),
            'old_balance' => $oldAvailable,
            'new_balance' => $request->available_balance
        ]);

        return redirect()->route('admin.wallets.show', $wallet->id)
            ->with('success', 'Wallet updated successfully');
    }

    public function transactions($id)
    {
        $wallet = Wallet::with(['user', 'withdrawals' => function($query) {
            $query->with('paymentMethod')->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        return view('backend.pages.wallets.transactions', compact('wallet'));
    }
}