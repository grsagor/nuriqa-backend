<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\SponsorRequest;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\TransactionSellLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class OrderController extends Controller
{
    private StripeClient $stripe;

    public function __construct()
    {
        $secretKey = config('services.stripe.secret') ?? env('STRIPE_SECRET');

        if (empty($secretKey)) {
            throw new \RuntimeException('Stripe secret key is not configured. Please set STRIPE_SECRET in your .env file.');
        }

        $this->stripe = new StripeClient($secretKey);
    }

    /**
     * Create Stripe Payment Intent
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.50',
            'currency' => 'nullable|string|size:3',
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        $amount = (float) $request->amount * 100; // Convert to cents
        $currency = $request->currency ?? 'gbp';

        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) round($amount),
                'currency' => strtolower($currency),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'email' => $user->email ?? $request->billing_email ?? '',
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                ],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Intent Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Process checkout and create transaction
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        DB::beginTransaction();

        try {
            // Extract cart item IDs from request
            $requestedCartItemIds = collect($request->cart_items)->pluck('id')->toArray();
            $requestedQuantities = collect($request->cart_items)->keyBy('id')->map->quantity->toArray();

            // Get cart items that belong to the user
            $cartItems = Cart::where('user_id', $user->id)
                ->whereIn('id', $requestedCartItemIds)
                ->with('product')
                ->get();

            // Validate that all requested cart items exist and belong to the user
            $foundCartItemIds = $cartItems->pluck('id')->toArray();
            $missingCartItemIds = array_diff($requestedCartItemIds, $foundCartItemIds);

            if (! empty($missingCartItemIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some cart items were not found or do not belong to you.',
                    'missing_items' => $missingCartItemIds,
                ], 400);
            }

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                ], 400);
            }

            // Validate that products exist
            foreach ($cartItems as $cartItem) {
                if (! $cartItem->product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more products in your cart no longer exist.',
                    ], 400);
                }
            }

            // Calculate totals using quantities from request
            $subtotal = 0;
            $tax = 0;
            $deliveryFee = 15.00;
            $couponDiscount = 0;

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                // Use quantity from request if provided, otherwise use cart quantity
                $quantity = $requestedQuantities[$cartItem->id] ?? $cartItem->quantity;
                $price = (float) ($product->price ?? 0);
                $subtotal += $price * $quantity;
            }

            $total = $subtotal + $tax + $deliveryFee - $couponDiscount;

            // Generate invoice number
            $invoiceNo = $this->generateInvoiceNumber();

            // Create transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'invoice_no' => $invoiceNo,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'delivery_fee' => $deliveryFee,
                'coupon_discount' => $couponDiscount,
                'total' => $total,
                'billing_first_name' => $request->billing_first_name,
                'billing_last_name' => $request->billing_last_name,
                'billing_email' => $request->billing_email,
                'billing_phone' => $request->billing_phone,
                'billing_address' => $request->billing_address,
                'additional_info' => $request->additional_info,
                'donate_anonymous' => $request->donate_anonymous ?? false,
                'payment_method' => $request->payment_method,
                'keep_updated' => $request->keep_updated ?? false,
            ]);

            // Create transaction sell lines using quantities from request
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                // Use quantity from request if provided, otherwise use cart quantity
                $quantity = $requestedQuantities[$cartItem->id] ?? $cartItem->quantity;
                $unitPrice = (float) ($product->price ?? 0);
                $lineSubtotal = $unitPrice * $quantity;

                TransactionSellLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ]);
            }

            // Handle payment based on payment method
            if ($request->payment_method === 'card') {
                // For card payments, create payment intent if not provided
                if ($request->payment_intent_id) {
                    TransactionPayment::create([
                        'transaction_id' => $transaction->id,
                        'stripe_payment_intent_id' => $request->payment_intent_id,
                        'payment_method' => 'stripe',
                        'amount' => $total,
                        'currency' => 'GBP',
                        'status' => 'pending',
                        'metadata' => [
                            'billing_email' => $request->billing_email,
                            'billing_phone' => $request->billing_phone,
                        ],
                    ]);
                } else {
                    // If no payment intent, transaction is created but payment is pending
                    TransactionPayment::create([
                        'transaction_id' => $transaction->id,
                        'payment_method' => 'stripe',
                        'amount' => $total,
                        'currency' => 'GBP',
                        'status' => 'pending',
                    ]);
                }
            } else {
                // For other payment methods (paypal, bank, cod)
                TransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_method' => $request->payment_method === 'card' ? 'stripe' : $request->payment_method,
                    'amount' => $total,
                    'currency' => 'GBP',
                    'status' => 'pending',
                ]);
            }

            // Clear cart after successful transaction creation
            Cart::where('user_id', $user->id)
                ->whereIn('id', $cartItems->pluck('id')->toArray())
                ->delete();

            DB::commit();

            $transaction->load(['sellLines.product', 'payments']);

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process checkout. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Process sponsor checkout and create transaction
     */
    public function sponsorCheckout(Request $request): JsonResponse
    {
        $request->validate([
            'sponsor_request_id' => 'required|exists:sponsor_requests,id',
            'billing_first_name' => 'required|string|max:255',
            'billing_last_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_phone' => 'required|string|max:255',
            'billing_address' => 'nullable|string|max:2000',
            'additional_info' => 'nullable|string|max:2000',
            'donate_anonymous' => 'nullable|boolean',
            'payment_method' => 'required|in:card,paypal,bank,cod',
            'keep_updated' => 'nullable|boolean',
            'agree_terms' => 'required|boolean|accepted',
        ]);

        $sponsor = JWTAuth::parseToken()->authenticate();

        DB::beginTransaction();

        try {
            // Get sponsor request
            $sponsorRequest = SponsorRequest::with(['product', 'user'])
                ->where('status', 'pending')
                ->findOrFail($request->sponsor_request_id);

            $product = $sponsorRequest->product;
            $requester = $sponsorRequest->user;

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            // Calculate totals
            $productPrice = (float) ($product->price ?? 0);
            $subtotal = $productPrice;
            $tax = 0;
            $deliveryFee = 15.00;
            $couponDiscount = 0;
            $total = $subtotal + $tax + $deliveryFee - $couponDiscount;

            // Generate invoice number
            $invoiceNo = $this->generateInvoiceNumber();

            // Create transaction
            $transaction = Transaction::create([
                'user_id' => $sponsor->id,
                'invoice_no' => $invoiceNo,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'delivery_fee' => $deliveryFee,
                'coupon_discount' => $couponDiscount,
                'total' => $total,
                'billing_first_name' => $request->billing_first_name,
                'billing_last_name' => $request->billing_last_name,
                'billing_email' => $request->billing_email,
                'billing_phone' => $request->billing_phone,
                'billing_address' => $request->billing_address,
                'additional_info' => $request->additional_info,
                'donate_anonymous' => $request->donate_anonymous ?? false,
                'payment_method' => $request->payment_method,
                'keep_updated' => $request->keep_updated ?? false,
            ]);

            // Create transaction sell line with sponsor tracking
            TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'sponsor_request_id' => $sponsorRequest->id,
                'requester_user_id' => $requester->id,
                'sponsor_user_id' => $sponsor->id,
                'quantity' => 1,
                'unit_price' => $productPrice,
                'subtotal' => $productPrice,
            ]);

            // Handle payment
            if ($request->payment_method === 'card') {
                if ($request->payment_intent_id) {
                    TransactionPayment::create([
                        'transaction_id' => $transaction->id,
                        'stripe_payment_intent_id' => $request->payment_intent_id,
                        'payment_method' => 'stripe',
                        'amount' => $total,
                        'currency' => 'GBP',
                        'status' => 'pending',
                    ]);
                } else {
                    TransactionPayment::create([
                        'transaction_id' => $transaction->id,
                        'payment_method' => 'stripe',
                        'amount' => $total,
                        'currency' => 'GBP',
                        'status' => 'pending',
                    ]);
                }
            } else {
                TransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_method' => $request->payment_method,
                    'amount' => $total,
                    'currency' => 'GBP',
                    'status' => 'pending',
                ]);
            }

            // Update sponsor request status to approved
            $sponsorRequest->update(['status' => 'approved']);

            DB::commit();

            $transaction->load(['sellLines.product', 'sellLines.sponsorRequest', 'sellLines.requester', 'sellLines.sponsor', 'payments']);

            return response()->json([
                'success' => true,
                'message' => 'Sponsor transaction created successfully',
                'data' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sponsor Checkout Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process sponsor checkout. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Confirm payment and update transaction status
     */
    public function confirmPayment(Request $request): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        try {
            // Retrieve payment intent from Stripe
            $paymentIntent = $this->stripe->paymentIntents->retrieve($request->payment_intent_id);

            $transaction = Transaction::where('id', $request->transaction_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $payment = TransactionPayment::where('transaction_id', $transaction->id)
                ->where('stripe_payment_intent_id', $request->payment_intent_id)
                ->firstOrFail();

            DB::beginTransaction();

            if ($paymentIntent->status === 'succeeded') {
                $payment->update([
                    'status' => 'succeeded',
                    'stripe_charge_id' => $paymentIntent->latest_charge ?? null,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'stripe_response' => $paymentIntent->toArray(),
                    ]),
                ]);

                // Keep transaction as pending; admin will mark complete manually and credit seller wallets
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment confirmed successfully',
                    'data' => [
                        'transaction' => $transaction->fresh(['sellLines.product', 'payments']),
                    ],
                ]);
            } else {
                $payment->update([
                    'status' => 'failed',
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'stripe_response' => $paymentIntent->toArray(),
                        'error' => $paymentIntent->last_payment_error?->message ?? 'Payment failed',
                    ]),
                ]);

                $transaction->update([
                    'status' => 'failed',
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment failed',
                    'data' => [
                        'transaction' => $transaction->fresh(['payments']),
                    ],
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment Confirmation Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Error: '.$e->getMessage());

            return response()->json([
                'error' => 'Invalid webhook signature',
            ], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;

                $payment = TransactionPayment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

                if ($payment) {
                    DB::beginTransaction();

                    $payment->update([
                        'status' => 'succeeded',
                        'stripe_charge_id' => $paymentIntent->latest_charge ?? null,
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'webhook_data' => $paymentIntent->toArray(),
                        ]),
                    ]);

                    // Keep transaction as pending; admin will mark complete manually and credit seller wallets
                    DB::commit();
                }
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;

                $payment = TransactionPayment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

                if ($payment) {
                    DB::beginTransaction();

                    $payment->update([
                        'status' => 'failed',
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'webhook_data' => $paymentIntent->toArray(),
                            'error' => $paymentIntent->last_payment_error?->message ?? 'Payment failed',
                        ]),
                    ]);

                    $transaction = $payment->transaction;
                    $transaction->update([
                        'status' => 'failed',
                    ]);

                    DB::commit();
                }
                break;

            default:
                Log::info('Unhandled Stripe webhook event: '.$event->type);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Get user orders
     */
    public function index(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $transactions = Transaction::where('user_id', $user->id)
            ->with([
                'sellLines.product.size',
                'sellLines.product.images',
                'payments',
            ])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Get seller orders (transactions containing seller-owned products)
     */
    public function sellerIndex(Request $request): JsonResponse
    {
        $seller = JWTAuth::parseToken()->authenticate();

        $transactions = Transaction::query()
            ->whereHas('sellLines.product', function ($q) use ($seller) {
                $q->where('owner_id', $seller->id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->with([
                'user',
                'payments',
                'sellLines' => function ($q) use ($seller) {
                    $q->whereHas('product', function ($sub) use ($seller) {
                        $sub->where('owner_id', $seller->id);
                    })->with([
                        'product.owner',
                        'product.size',
                        'product.category',
                        'product.images',
                        'sponsorRequest.user',
                        'requester',
                        'sponsor',
                    ]);
                },
            ])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Get orders where the authenticated user is the sponsor
     */
    public function sponsoredIndex(Request $request): JsonResponse
    {
        $sponsor = JWTAuth::parseToken()->authenticate();

        $transactions = Transaction::query()
            ->whereHas('sellLines', function ($q) use ($sponsor) {
                $q->where('sponsor_user_id', $sponsor->id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->with([
                'user',
                'payments',
                'sellLines' => function ($q) use ($sponsor) {
                    $q->where('sponsor_user_id', $sponsor->id)->with([
                        'product.owner',
                        'product.size',
                        'product.category',
                        'product.images',
                        'sponsorRequest.user',
                        'requester',
                        'sponsor',
                    ]);
                },
            ])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Get single order
     */
    public function show(string $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $transaction = Transaction::where('id', $id)
            ->where('user_id', $user->id)
            ->with([
                'sellLines.product.size',
                'sellLines.product.images',
                'payments',
            ])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $year = date('Y');
        $month = date('m');

        $lastTransaction = Transaction::where('invoice_no', 'like', $prefix.$year.$month.'%')
            ->orderBy('invoice_no', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->invoice_no, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.$month.str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
}
