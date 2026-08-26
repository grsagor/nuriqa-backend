<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use App\Models\SponsorRequest;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\User;
use App\Services\CheckoutShippingService;
use App\Services\PayPalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class OrderPayPalCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function mockFlatShippingFee(float $fee = 15.0): void
    {
        $this->mock(CheckoutShippingService::class, function ($mock) use ($fee) {
            $mock->shouldReceive('quote')->andReturn([
                'total_fee' => $fee,
                'quote' => ['currency' => 'GBP', 'total_fee' => $fee, 'seller_count' => 1, 'sellers' => []],
            ]);
            $mock->shouldReceive('shippingAddressFromCheckout')->andReturn([
                'name' => 'Buyer Person',
                'address_line_1' => '10 Test Street',
                'city' => 'London',
                'postcode' => 'E1 1AA',
                'country' => 'GB',
            ]);
            $mock->shouldReceive('createShipmentsForTransaction')->andReturn([]);
        });
    }

    public function test_user_can_complete_paid_cart_checkout_with_paypal(): void
    {
        $this->mockFlatShippingFee(15.0);
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $product = $this->seedPaidProduct($seller);
        $cart = Cart::query()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $paypalOrderId = 'PAYPAL-ORDER-1001';
        $paypalCaptureId = 'PAYPAL-CAPTURE-1001';
        $this->mockSuccessfulPayPalFlow($paypalOrderId, $paypalCaptureId, $buyer->email);

        $token = JWTAuth::fromUser($buyer);

        $checkoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders/checkout', [
                'billing_first_name' => 'Buyer',
                'billing_last_name' => 'Person',
                'billing_email' => $buyer->email,
                'billing_phone' => '07123456789',
                'billing_address' => '10 Test Street, London, E1 1AA',
                'shipping_address_line_1' => '10 Test Street',
                'shipping_city' => 'London',
                'shipping_postcode' => 'E1 1AA',
                'payment_method' => 'paypal',
                'agree_terms' => true,
                'cart_items' => [
                    ['id' => $cart->id, 'quantity' => 1],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $transactionId = $checkoutResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders/paypal/create-order', [
                'transaction_id' => $transactionId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.paypal_order_id', $paypalOrderId);

        $captureResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders/paypal/capture-order', [
                'transaction_id' => $transactionId,
                'paypal_order_id' => $paypalOrderId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $payment = TransactionPayment::query()
            ->where('transaction_id', $transactionId)
            ->firstOrFail();

        $transaction = Transaction::query()->findOrFail($transactionId);

        $this->assertSame('paypal', $payment->payment_method);
        $this->assertSame('succeeded', $payment->status);
        $this->assertSame($paypalOrderId, $payment->metadata['paypal_order_id']);
        $this->assertSame($paypalCaptureId, $payment->metadata['paypal_capture_id']);
        $this->assertSame($buyer->email, $payment->metadata['paypal_payer_email']);
        $this->assertSame('pending', $transaction->status);
        $captureResponse->assertJsonPath('data.transaction.id', $transactionId);
    }

    public function test_sponsor_checkout_can_capture_paypal_payment(): void
    {
        $this->mockFlatShippingFee(15.0);
        $seller = User::factory()->create(['postal_code' => 'SW1A 1AA']);
        $requester = User::factory()->create();
        $sponsor = User::factory()->create();
        $product = $this->seedPaidProduct($seller);

        $sponsorRequest = SponsorRequest::query()->create([
            'user_id' => $requester->id,
            'product_id' => $product->id,
            'request_reason' => 'Need this for Eid.',
            'first_name' => 'Request',
            'last_name' => 'User',
            'email' => $requester->email,
            'phone' => '07000000000',
            'address' => '11 Hope Road',
            'apartment' => null,
            'city' => 'London',
            'postal_code' => 'E1 2AB',
            'additional_info' => null,
            'keep_updated' => true,
            'status' => 'pending',
        ]);

        $paypalOrderId = 'PAYPAL-ORDER-2002';
        $paypalCaptureId = 'PAYPAL-CAPTURE-2002';
        $this->mockSuccessfulPayPalFlow($paypalOrderId, $paypalCaptureId, $sponsor->email);

        $token = JWTAuth::fromUser($sponsor);

        $checkoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders/sponsor-checkout', [
                'sponsor_request_id' => $sponsorRequest->id,
                'billing_first_name' => 'Sponsor',
                'billing_last_name' => 'Person',
                'billing_email' => $sponsor->email,
                'billing_phone' => '07111222333',
                'shipping_address_line_1' => '11 Hope Road',
                'shipping_city' => 'London',
                'shipping_postcode' => 'E1 2AB',
                'payment_method' => 'paypal',
                'agree_terms' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $transactionId = $checkoutResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders/paypal/create-order', [
                'transaction_id' => $transactionId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.paypal_order_id', $paypalOrderId);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders/paypal/capture-order', [
                'transaction_id' => $transactionId,
                'paypal_order_id' => $paypalOrderId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $payment = TransactionPayment::query()
            ->where('transaction_id', $transactionId)
            ->firstOrFail();

        $sponsorRequest->refresh();

        $this->assertSame('paypal', $payment->payment_method);
        $this->assertSame('succeeded', $payment->status);
        $this->assertSame($paypalOrderId, $payment->metadata['paypal_order_id']);
        $this->assertSame($paypalCaptureId, $payment->metadata['paypal_capture_id']);
        $this->assertSame('approved', $sponsorRequest->status);
    }

    private function seedPaidProduct(User $seller): Product
    {
        $category = Category::query()->create(['name' => 'Cat']);
        $size = Size::query()->create(['name' => 'M', 'type' => 'general']);

        return Product::query()->create([
            'owner_id' => $seller->id,
            'title' => 'Jacket',
            'description' => 'Warm jacket',
            'size_id' => $size->id,
            'category_id' => $category->id,
            'condition' => 'new',
            'price' => 25.00,
            'is_free' => false,
            'platform_donation' => false,
            'donation_percentage' => 0,
            'stock' => 3,
            'type' => 'seller',
            'active_listing' => true,
        ]);
    }

    private function mockSuccessfulPayPalFlow(string $orderId, string $captureId, string $payerEmail): void
    {
        $this->mock(PayPalService::class, function (MockInterface $mock) use ($orderId, $captureId, $payerEmail): void {
            $mock->shouldReceive('createOrder')
                ->once()
                ->andReturn([
                    'id' => $orderId,
                    'status' => 'CREATED',
                ]);

            $mock->shouldReceive('captureOrder')
                ->once()
                ->with($orderId)
                ->andReturn([
                    'id' => $orderId,
                    'status' => 'COMPLETED',
                    'payer' => [
                        'payer_id' => 'TESTPAYER123',
                        'email_address' => $payerEmail,
                    ],
                    'purchase_units' => [
                        [
                            'payments' => [
                                'captures' => [
                                    [
                                        'id' => $captureId,
                                        'status' => 'COMPLETED',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);
        });
    }
}
