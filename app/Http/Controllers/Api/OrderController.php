<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductPriceOffer;
use App\Models\SponsorRequest;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\TransactionSellLine;
use App\Services\PayPalService;
use App\Services\PlatformFeeService;
use App\Services\ProductPriceOfferService;
use App\Services\SellerNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class OrderController extends Controller
{
    private ?StripeClient $stripe = null;

    public function __construct(private PayPalService $payPalService) {}

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
            $paymentIntent = $this->stripe()->paymentIntents->create([
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

            // Validate that products exist and have sufficient stock
            foreach ($cartItems as $cartItem) {
                if (! $cartItem->product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more products in your cart no longer exist.',
                    ], 400);
                }
                $quantity = $requestedQuantities[$cartItem->id] ?? $cartItem->quantity;
                if ($cartItem->product->stock < $quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for \"{$cartItem->product->title}\". Available: {$cartItem->product->stock}.",
                    ], 400);
                }
            }

            // Calculate totals: optional approved offer lowers seller unit; pay_unit_price >= resolved adds voluntary donation only.
            $subtotal = 0;
            $platformFeeTotal = 0;
            $donationTotal = 0;
            $voluntaryDonationOrderTotal = 0;
            $tax = 0;
            $deliveryFee = 15.00;
            $couponDiscount = 0;

            $payUnitByCartId = collect($request->cart_items)->keyBy('id')->map(function ($item) {
                if (! is_array($item) || ! array_key_exists('pay_unit_price', $item) || $item['pay_unit_price'] === null || $item['pay_unit_price'] === '') {
                    return null;
                }

                return round((float) $item['pay_unit_price'], 2);
            });

            $checkoutLines = [];

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                $quantity = $requestedQuantities[$cartItem->id] ?? $cartItem->quantity;

                $offer = null;
                if (! $product->is_free && ProductPriceOfferService::listSellerUnitPrice($product) > 0) {
                    $offer = ProductPriceOffer::query()
                        ->where('buyer_id', $user->id)
                        ->where('product_id', $product->id)
                        ->where('status', ProductPriceOffer::STATUS_APPROVED)
                        ->whereNull('consumed_at')
                        ->where(function ($q): void {
                            $q->whereNull('approved_until')
                                ->orWhere('approved_until', '>', now());
                        })
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->first();
                }

                $listUnit = ProductPriceOfferService::listSellerUnitPrice($product);
                $resolvedUnit = $offer ? round((float) $offer->offered_unit_price, 2) : $listUnit;

                if (! $product->is_free && $resolvedUnit <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid price for one or more items.',
                    ], 400);
                }

                $requestedPayUnit = $payUnitByCartId[$cartItem->id] ?? null;
                if ($requestedPayUnit !== null) {
                    if ($requestedPayUnit + 0.0001 < $resolvedUnit) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Pay amount cannot be less than the agreed price for "'.$product->title.'".',
                        ], 422);
                    }
                    if ($requestedPayUnit > $resolvedUnit + 1_000_000) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Pay amount is too large for "'.$product->title.'".',
                        ], 422);
                    }
                }

                $paySellerUnit = $requestedPayUnit !== null ? $requestedPayUnit : $resolvedUnit;
                $voluntaryLine = round(max(0, ($paySellerUnit - $resolvedUnit) * $quantity), 2);
                $voluntaryDonationOrderTotal += $voluntaryLine;

                $sellerSubtotal = round($resolvedUnit * $quantity, 2);
                $linePlatformFee = PlatformFeeService::platformFeeAmountForSellerSubtotal($sellerSubtotal, $product);
                $lineDonation = PlatformFeeService::donationAmountForLine($sellerSubtotal, $product);
                $lineBuyer = $sellerSubtotal + $linePlatformFee;
                $subtotal += $lineBuyer;
                $platformFeeTotal += $linePlatformFee;
                $donationTotal += $lineDonation + $voluntaryLine;

                $checkoutLines[] = [
                    'cartItem' => $cartItem,
                    'product' => $product,
                    'quantity' => $quantity,
                    'resolvedUnit' => $resolvedUnit,
                    'sellerSubtotal' => $sellerSubtotal,
                    'linePlatformFee' => $linePlatformFee,
                    'lineDonation' => $lineDonation,
                    'voluntaryLine' => $voluntaryLine,
                    'offer' => $offer,
                ];
            }

            $total = $subtotal + $tax + $deliveryFee - $couponDiscount + $voluntaryDonationOrderTotal;

            // Generate invoice number
            $invoiceNo = $this->generateInvoiceNumber();

            // Create transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'invoice_no' => $invoiceNo,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'platform_fee_total' => $platformFeeTotal,
                'donation_total' => $donationTotal,
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

            // Create transaction sell lines and decrement stock; consume approved offers once.
            $offersToConsume = [];
            foreach ($checkoutLines as $row) {
                $product = $row['product'];
                $quantity = $row['quantity'];
                $resolvedUnit = $row['resolvedUnit'];
                $lineSubtotal = $row['sellerSubtotal'];

                TransactionSellLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $resolvedUnit,
                    'subtotal' => $lineSubtotal,
                    'platform_fee_amount' => $row['linePlatformFee'],
                    'donation_amount' => $row['lineDonation'],
                    'voluntary_donation_amount' => $row['voluntaryLine'],
                ]);

                Product::where('id', $product->id)->decrement('stock', $quantity);

                if ($row['offer'] !== null) {
                    $offersToConsume[$row['offer']->id] = $row['offer'];
                }
            }

            foreach ($offersToConsume as $offerModel) {
                $offerModel->forceFill([
                    'status' => ProductPriceOffer::STATUS_CONSUMED,
                    'consumed_at' => now(),
                    'transaction_id' => $transaction->id,
                ])->save();
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
                    'metadata' => [
                        'billing_email' => $request->billing_email,
                        'billing_phone' => $request->billing_phone,
                    ],
                ]);
            }

            // Clear cart after successful transaction creation
            Cart::where('user_id', $user->id)
                ->whereIn('id', $cartItems->pluck('id')->toArray())
                ->delete();

            DB::commit();

            SellerNotificationService::notifyOrderCreated($transaction);

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

            if ($product->stock < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'This item is currently out of stock.',
                ], 400);
            }

            $productPrice = (float) ($product->price ?? 0);
            $sellerSubtotal = round($productPrice, 2);
            $linePlatformFee = PlatformFeeService::platformFeeAmountForSellerSubtotal($sellerSubtotal, $product);
            $lineDonation = PlatformFeeService::donationAmountForLine($sellerSubtotal, $product);
            $subtotal = $sellerSubtotal + $linePlatformFee;
            $platformFeeTotal = $linePlatformFee;
            $donationTotal = $lineDonation;
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
                'platform_fee_total' => $platformFeeTotal,
                'donation_total' => $donationTotal,
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

            TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'sponsor_request_id' => $sponsorRequest->id,
                'requester_user_id' => $requester->id,
                'sponsor_user_id' => $sponsor->id,
                'quantity' => 1,
                'unit_price' => $productPrice,
                'subtotal' => $sellerSubtotal,
                'platform_fee_amount' => $linePlatformFee,
                'donation_amount' => $lineDonation,
            ]);

            Product::where('id', $product->id)->decrement('stock', 1);

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
                    'metadata' => [
                        'billing_email' => $request->billing_email,
                        'billing_phone' => $request->billing_phone,
                    ],
                ]);
            }

            // Update sponsor request status to approved
            $sponsorRequest->update(['status' => 'approved']);

            DB::commit();

            SellerNotificationService::notifyOrderCreated($transaction);

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
            $paymentIntent = $this->stripe()->paymentIntents->retrieve($request->payment_intent_id);

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

                SellerNotificationService::notifyPaymentReceived($transaction->fresh());

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

    public function createPayPalOrder(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        try {
            $transaction = Transaction::query()
                ->where('id', $request->transaction_id)
                ->where('user_id', $user->id)
                ->with('payments')
                ->firstOrFail();

            if ($transaction->payment_method !== 'paypal') {
                return response()->json([
                    'success' => false,
                    'message' => 'This transaction is not using PayPal.',
                ], 422);
            }

            $payment = $transaction->payments()
                ->where('payment_method', 'paypal')
                ->latest('id')
                ->firstOrFail();

            if ($payment->status === 'succeeded') {
                return response()->json([
                    'success' => true,
                    'message' => 'PayPal payment is already completed.',
                    'data' => [
                        'transaction' => $transaction->fresh(['sellLines.product', 'payments']),
                        'paypal_order_id' => $payment->metadata['paypal_order_id'] ?? null,
                    ],
                ]);
            }

            $paypalOrder = $this->payPalService->createOrder($transaction, $payment);

            $payment->update([
                'metadata' => array_merge($payment->metadata ?? [], [
                    'paypal_order_id' => $paypalOrder['id'] ?? null,
                    'paypal_create_order_response' => $paypalOrder,
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PayPal order created successfully.',
                'data' => [
                    'paypal_order_id' => $paypalOrder['id'] ?? null,
                    'transaction_id' => $transaction->id,
                    'paypal_order' => $paypalOrder,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal Create Order Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create PayPal order. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function capturePayPalOrder(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'paypal_order_id' => 'required|string',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        try {
            $transaction = Transaction::query()
                ->where('id', $request->transaction_id)
                ->where('user_id', $user->id)
                ->with('payments')
                ->firstOrFail();

            $payment = $transaction->payments()
                ->where('payment_method', 'paypal')
                ->latest('id')
                ->firstOrFail();

            $existingOrderId = $payment->metadata['paypal_order_id'] ?? null;
            if ($existingOrderId && $existingOrderId !== $request->paypal_order_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'PayPal order does not match this transaction.',
                ], 422);
            }

            $paypalOrder = $this->payPalService->captureOrder($request->paypal_order_id);
            $capture = $paypalOrder['purchase_units'][0]['payments']['captures'][0] ?? null;
            $paypalStatus = strtoupper((string) ($paypalOrder['status'] ?? ''));
            $captureStatus = strtoupper((string) ($capture['status'] ?? ''));

            DB::beginTransaction();

            if ($paypalStatus === 'COMPLETED' || $captureStatus === 'COMPLETED') {
                $payment->update([
                    'status' => 'succeeded',
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'paypal_order_id' => $request->paypal_order_id,
                        'paypal_capture_id' => $capture['id'] ?? null,
                        'paypal_payer_id' => $paypalOrder['payer']['payer_id'] ?? null,
                        'paypal_payer_email' => $paypalOrder['payer']['email_address'] ?? null,
                        'paypal_capture_response' => $paypalOrder,
                    ]),
                ]);

                DB::commit();

                SellerNotificationService::notifyPaymentReceived($transaction->fresh());

                return response()->json([
                    'success' => true,
                    'message' => 'PayPal payment captured successfully',
                    'data' => [
                        'transaction' => $transaction->fresh(['sellLines.product', 'payments']),
                    ],
                ]);
            }

            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'paypal_order_id' => $request->paypal_order_id,
                    'paypal_capture_response' => $paypalOrder,
                    'error' => 'PayPal payment was not completed.',
                ]),
            ]);

            $transaction->update([
                'status' => 'failed',
            ]);

            DB::commit();

            return response()->json([
                'success' => false,
                'message' => 'PayPal payment was not completed.',
                'data' => [
                    'transaction' => $transaction->fresh(['payments']),
                ],
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayPal Capture Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to capture PayPal payment',
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

                    SellerNotificationService::notifyPaymentReceived($payment->transaction->fresh());
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
     * Allow a seller to update the status of a transaction that contains their products.
     * Sellers can only change the high-level transaction status; payment status remains system-controlled.
     */
    public function sellerUpdateStatus(Request $request, string $id): JsonResponse
    {
        $seller = JWTAuth::parseToken()->authenticate();

        $request->validate([
            'status' => 'required|in:pending,completed,failed',
        ]);

        $transaction = Transaction::query()
            ->where('id', $id)
            ->whereHas('sellLines.product', function ($q) use ($seller) {
                $q->where('owner_id', $seller->id);
            })
            ->with('payments')
            ->firstOrFail();

        // Prevent marking as completed before payment has succeeded
        if ($request->status === 'completed') {
            $payment = $transaction->payments->first();

            if (! $payment || $payment->status !== 'succeeded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be marked as completed until payment has succeeded.',
                ], 422);
            }
        }

        $transaction->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $transaction->fresh(['user', 'payments', 'sellLines.product']),
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

    private function stripe(): StripeClient
    {
        if ($this->stripe instanceof StripeClient) {
            return $this->stripe;
        }

        $secretKey = config('services.stripe.secret') ?? env('STRIPE_SECRET');

        if (empty($secretKey)) {
            throw new \RuntimeException('Stripe secret key is not configured. Please set STRIPE_SECRET in your .env file.');
        }

        $this->stripe = new StripeClient($secretKey);

        return $this->stripe;
    }
}
