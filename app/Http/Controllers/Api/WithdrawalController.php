<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\WithdrawalNotification;
use App\Models\SellerPaymentMethod;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Withdrawal::where('user_id', $user->id)
            ->with('paymentMethod:id,type,provider,account_name')
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $withdrawals->items(),
            'pagination' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:seller_payment_methods,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        $wallet = Wallet::getOrCreateForUser($user->id);

        $pendingWithdrawalsSum = (float) Withdrawal::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');
        $withdrawableBalance = max(0, (float) $wallet->available_balance - $pendingWithdrawalsSum);

        if ($request->amount > $withdrawableBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient available balance for withdrawal (consider your pending requests)',
            ], 422);
        }

        $paymentMethod = SellerPaymentMethod::where('user_id', $user->id)
            ->where('id', $request->payment_method_id)
            ->where('status', 'active')
            ->firstOrFail();

        $minimumWithdrawal = config('withdrawal.minimum_amount', 10);
        if ($request->amount < $minimumWithdrawal) {
            return response()->json([
                'success' => false,
                'message' => "Minimum withdrawal amount is {$minimumWithdrawal}",
            ], 422);
        }

        $maxPendingWithdrawal = config('withdrawal.max_pending_amount', 5000);
        if (($pendingWithdrawalsSum + $request->amount) > $maxPendingWithdrawal) {
            return response()->json([
                'success' => false,
                'message' => "Maximum pending withdrawal amount is {$maxPendingWithdrawal}",
            ], 422);
        }

        DB::beginTransaction();

        try {
            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $request->amount,
                'status' => 'pending',
                'notes' => $request->notes,
                'payment_details' => [
                    'type' => $paymentMethod->type,
                    'provider' => $paymentMethod->provider,
                    'account_name' => $paymentMethod->account_name,
                    'account_details' => $paymentMethod->account_details,
                ],
            ]);

            // Do not deduct from wallet here; admin will approve later and then amount is deducted

            // Send admin notification email
            try {
                $adminEmail = config('mail.from.address', 'admin@nuriqa.com');
                Mail::to($adminEmail)->send(new WithdrawalNotification($withdrawal, $user, $paymentMethod));
            } catch (\Exception $e) {
                // Log email error but don't fail the withdrawal
                Log::error('Failed to send withdrawal notification email: '.$e->getMessage());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully',
                'data' => [
                    'id' => $withdrawal->id,
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'payment_method' => [
                        'type' => $paymentMethod->type,
                        'provider' => $paymentMethod->provider,
                        'account_name' => $paymentMethod->account_name,
                        'masked_account_details' => $paymentMethod->masked_account_details,
                    ],
                    'notes' => $withdrawal->notes,
                    'created_at' => $withdrawal->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process withdrawal request',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    public function show($id)
    {
        $user = Auth::user();

        $withdrawal = Withdrawal::where('user_id', $user->id)
            ->where('id', $id)
            ->with('paymentMethod:id,type,provider,account_name')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'status' => $withdrawal->status,
                'payment_method' => [
                    'type' => $withdrawal->paymentMethod->type,
                    'provider' => $withdrawal->paymentMethod->provider,
                    'account_name' => $withdrawal->paymentMethod->account_name,
                    'masked_account_details' => $withdrawal->paymentMethod->masked_account_details,
                ],
                'notes' => $withdrawal->notes,
                'rejection_reason' => $withdrawal->rejection_reason,
                'transaction_id' => $withdrawal->transaction_id,
                'created_at' => $withdrawal->created_at,
                'updated_at' => $withdrawal->updated_at,
                'processed_at' => $withdrawal->processed_at,
            ],
        ]);
    }

    public function cancel($id)
    {
        $user = Auth::user();

        $withdrawal = Withdrawal::where('user_id', $user->id)
            ->where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            $withdrawal->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request cancelled successfully',
                'data' => [
                    'id' => $withdrawal->id,
                    'status' => $withdrawal->status,
                    'updated_at' => $withdrawal->updated_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function withdrawalLimits()
    {
        $user = Auth::user();
        $wallet = Wallet::getOrCreateForUser($user->id);

        $pendingWithdrawals = (float) Withdrawal::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');
        $withdrawableBalance = max(0, (float) $wallet->available_balance - $pendingWithdrawals);

        return response()->json([
            'success' => true,
            'data' => [
                'available_balance' => $wallet->available_balance,
                'pending_balance' => $wallet->pending_balance,
                'withdrawable_balance' => $withdrawableBalance,
                'minimum_withdrawal' => config('withdrawal.minimum_amount', 10),
                'maximum_withdrawal' => $withdrawableBalance,
                'max_pending_amount' => config('withdrawal.max_pending_amount', 5000),
                'current_pending_amount' => $pendingWithdrawals,
                'remaining_pending_limit' => config('withdrawal.max_pending_amount', 5000) - $pendingWithdrawals,
            ],
        ]);
    }
}
