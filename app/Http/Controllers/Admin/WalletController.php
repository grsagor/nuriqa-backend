<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $wallets = Wallet::with('user')
            ->selectRaw('wallets.*, (available_balance + pending_balance) as total_balance')
            ->orderBy('total_balance', 'desc')
            ->paginate(50);

        $totalBalance = Wallet::sum('available_balance');
        $totalPending = Wallet::sum('pending_balance');
        $totalWallets = Wallet::count();
        $activeUsers = User::whereHas('wallet')->count();

        return view('backend.pages.wallets.index', compact(
            'wallets',
            'totalBalance',
            'totalPending', 
            'totalWallets',
            'activeUsers'
        ));
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