<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\SellerPaymentMethod;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get wallet, creating if it doesn't exist
        $wallet = Wallet::getOrCreateForUser($user->id);
        
        // Refresh from database to ensure we have latest values
        $wallet->refresh();
        
        // Log for debugging (remove in production)
        \Log::info('Wallet balance for user ' . $user->id, [
            'available_balance' => $wallet->available_balance,
            'pending_balance' => $wallet->pending_balance,
            'total_earnings' => $wallet->total_earnings,
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'available_balance' => (float) $wallet->available_balance,
                'pending_balance' => (float) $wallet->pending_balance,
                'total_earnings' => (float) $wallet->total_earnings,
                'total_balance' => (float) ($wallet->available_balance + $wallet->pending_balance),
            ]
        ]);
    }

    public function paymentMethods()
    {
        $user = Auth::user();
        $paymentMethods = SellerPaymentMethod::where('user_id', $user->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $paymentMethods->map(function ($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'provider' => $method->provider,
                    'account_name' => $method->account_name,
                    'masked_account_details' => $method->masked_account_details,
                    'is_default' => $method->is_default,
                    'status' => $method->status,
                    'created_at' => $method->created_at,
                ];
            })
        ]);
    }

    public function storePaymentMethod(Request $request)
    {
        $request->validate([
            'type' => ['required', Rule::in(['bank_account', 'paypal', 'stripe', 'wise'])],
            'provider' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_details' => 'required|array',
            'is_default' => 'boolean',
        ]);

        $user = Auth::user();

        if ($request->boolean('is_default')) {
            SellerPaymentMethod::where('user_id', $user->id)
                ->update(['is_default' => false]);
        }

        $paymentMethod = SellerPaymentMethod::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'provider' => $request->provider,
            'account_name' => $request->account_name,
            'account_details' => $request->account_details,
            'is_default' => $request->boolean('is_default', false),
            'status' => 'active',
        ]);

        // Refresh to get all attributes including masked_account_details
        $paymentMethod->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Payment method added successfully',
            'data' => [
                'id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'provider' => $paymentMethod->provider,
                'account_name' => $paymentMethod->account_name,
                'masked_account_details' => $paymentMethod->masked_account_details,
                'is_default' => $paymentMethod->is_default,
                'status' => $paymentMethod->status,
                'created_at' => $paymentMethod->created_at,
            ]
        ], 201);
    }

    public function updatePaymentMethod(Request $request, $id)
    {
        $request->validate([
            'account_name' => 'sometimes|string|max:255',
            'account_details' => 'sometimes|array',
            'is_default' => 'boolean',
        ]);

        $user = Auth::user();
        $paymentMethod = SellerPaymentMethod::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($request->boolean('is_default')) {
            SellerPaymentMethod::where('user_id', $user->id)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        if ($request->has('account_name')) {
            $paymentMethod->account_name = $request->account_name;
        }

        if ($request->has('account_details')) {
            $paymentMethod->account_details = $request->account_details;
        }

        if ($request->has('is_default')) {
            $paymentMethod->is_default = $request->boolean('is_default');
        }

        $paymentMethod->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated successfully',
            'data' => [
                'id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'provider' => $paymentMethod->provider,
                'account_name' => $paymentMethod->account_name,
                'masked_account_details' => $paymentMethod->masked_account_details,
                'is_default' => $paymentMethod->is_default,
                'status' => $paymentMethod->status,
            ]
        ]);
    }

    public function deletePaymentMethod($id)
    {
        $user = Auth::user();
        $paymentMethod = SellerPaymentMethod::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($paymentMethod->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete default payment method. Please set another method as default first.'
            ], 422);
        }

        $paymentMethod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully'
        ]);
    }


    public function transactions(Request $request)
    {
        $user = Auth::user();
        
        // Get withdrawals as transactions
        $withdrawals = Withdrawal::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        $transactions = $withdrawals->map(function ($withdrawal) {
            return [
                'id' => $withdrawal->id,
                'type' => 'withdrawal',
                'amount' => -abs($withdrawal->amount), // Negative for withdrawals
                'status' => $withdrawal->status,
                'description' => 'Withdrawal request - ' . ucfirst($withdrawal->status),
                'created_at' => $withdrawal->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'pagination' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
            ]
        ]);
    }

    public function setDefaultPaymentMethod($id)
    {
        $user = Auth::user();
        $paymentMethod = SellerPaymentMethod::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        SellerPaymentMethod::setDefault($user->id, $paymentMethod->id);

        return response()->json([
            'success' => true,
            'message' => 'Default payment method updated successfully'
        ]);
    }
}