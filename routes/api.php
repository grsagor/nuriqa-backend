<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\EVRiController;
use App\Http\Controllers\Api\JoinUsController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\SellerNotificationController;
use App\Http\Controllers\Api\SponsorRequestController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/signup', 'signup')->name('api.v1.signup');
        Route::post('/verify-otp', 'verifyOtp')->name('api.v1.verify-otp');
        Route::post('/resend-otp', 'resendOtp')->name('api.v1.resend-otp');
        Route::post('/login', 'login')->name('api.v1.login');
        Route::post('/logout', 'logout')->name('api.v1.logout')->middleware('auth:api');
        Route::get('/my-user-info', 'myUserInfo')->name('api.v1.my-user-info')->middleware('jwt.auth');
        Route::post('/my-notification-settings', 'updateNotificationSettings')->name('api.v1.my-notification-settings')->middleware('jwt.auth');
        Route::post('/update-profile', 'updateProfile')->name('api.v1.update-profile')->middleware('jwt.auth');
        Route::post('/change-password', 'changePassword')->name('api.v1.change-password')->middleware('jwt.auth');
    });

    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::get('/', 'index')->name('api.v1.products.index');
        Route::get('/categories', 'categories')->name('api.v1.products.categories');
        Route::get('/sizes', 'sizes')->name('api.v1.products.sizes');
        Route::get('/my-most-wishlisted', 'myMostWishlisted')->name('api.v1.products.my-most-wishlisted')->middleware('jwt.auth');
        Route::get('/details/{id}', 'show')->name('api.v1.products.show');
        Route::post('/store', 'store')->name('api.v1.products.store')->middleware('jwt.auth');
        Route::put('/{id}', 'update')->name('api.v1.products.update')->middleware('jwt.auth');
        Route::post('/{id}', 'update')->name('api.v1.products.update.post')->middleware('jwt.auth'); // POST for FormData (PHP doesn't parse PUT body)
        Route::delete('/{id}', 'destroy')->name('api.v1.products.destroy')->middleware('jwt.auth');
    });

    Route::get('/products/details/{id}/reviews', [ProductReviewController::class, 'index'])->name('api.v1.products.reviews.index');
    Route::post('/products/details/{id}/reviews', [ProductReviewController::class, 'store'])->name('api.v1.products.reviews.store')->middleware('jwt.auth');

    Route::get('/seller/reviews', [ProductReviewController::class, 'sellerIndex'])->name('api.v1.seller.reviews.index')->middleware('jwt.auth');

    Route::prefix('wishlist')->controller(WishlistController::class)->middleware('jwt.auth')->group(function () {
        Route::get('/', 'index')->name('api.v1.wishlist.index');
        Route::post('/', 'store')->name('api.v1.wishlist.store');
        Route::get('/check', 'check')->name('api.v1.wishlist.check');
        Route::delete('/{id}', 'destroy')->name('api.v1.wishlist.destroy');
        Route::delete('/product/{productId}', 'destroyByProduct')->name('api.v1.wishlist.destroy-by-product');
    });

    Route::prefix('cart')->controller(CartController::class)->middleware('jwt.auth')->group(function () {
        Route::get('/', 'index')->name('api.v1.cart.index');
        Route::post('/', 'store')->name('api.v1.cart.store');
        Route::delete('/', 'clear')->name('api.v1.cart.clear');
        Route::put('/{id}', 'update')->name('api.v1.cart.update');
        Route::delete('/{id}', 'destroy')->name('api.v1.cart.destroy');
    });

    Route::prefix('sponsor-requests')->controller(SponsorRequestController::class)->group(function () {
        Route::get('/', 'index')->name('api.v1.sponsor-requests.index');
        Route::get('/{id}/public', 'publicShow')->name('api.v1.sponsor-requests.public-show');
        Route::get('/seller', 'sellerIndex')->name('api.v1.sponsor-requests.seller-index')->middleware('jwt.auth');
        Route::get('/my-requests', 'myRequests')->name('api.v1.sponsor-requests.my-requests')->middleware('jwt.auth');
        Route::post('/', 'store')->name('api.v1.sponsor-requests.store')->middleware('jwt.auth');
        Route::get('/{id}', 'show')->name('api.v1.sponsor-requests.show')->middleware('jwt.auth');
    });

    Route::prefix('orders')->controller(OrderController::class)->middleware('jwt.auth')->group(function () {
        Route::post('/create-payment-intent', 'createPaymentIntent')->name('api.v1.orders.create-payment-intent');
        Route::post('/checkout', 'checkout')->name('api.v1.orders.checkout');
        Route::post('/sponsor-checkout', 'sponsorCheckout')->name('api.v1.orders.sponsor-checkout');
        Route::post('/confirm-payment', 'confirmPayment')->name('api.v1.orders.confirm-payment');
        Route::get('/seller', 'sellerIndex')->name('api.v1.orders.seller-index');
        Route::get('/sponsored', 'sponsoredIndex')->name('api.v1.orders.sponsored-index');
        Route::get('/', 'index')->name('api.v1.orders.index');
        Route::get('/{id}', 'show')->name('api.v1.orders.show');
    });

    Route::prefix('seller/notifications')->controller(SellerNotificationController::class)->middleware('jwt.auth')->group(function () {
        Route::get('/unread-count', 'unreadCount')->name('api.v1.seller.notifications.unread-count');
        Route::post('/mark-read', 'markRead')->name('api.v1.seller.notifications.mark-read');
        Route::get('/', 'index')->name('api.v1.seller.notifications.index');
    });

    // Stripe webhook (no auth required)
    Route::post('/stripe/webhook', [OrderController::class, 'handleWebhook'])->name('api.v1.stripe.webhook');

    // Join Us Applications (no auth required)
    Route::post('/join-us', [JoinUsController::class, 'store'])->name('api.v1.join-us.store');

    // Contact Form (no auth required)
    Route::post('/contact', [ContactController::class, 'store'])->name('api.v1.contact.store');

    // EVRi Integration
    Route::get('/evri/authenticate', [EVRiController::class, 'authenticate'])->name('api.v1.evri.authenticate');
    Route::post('/evri/validate-address', [EVRiController::class, 'validateAddress'])->name('api.v1.evri.validate-address');
    Route::get('/evri/rates', [EVRiController::class, 'getRates'])->name('api.v1.evri.rates');
    Route::post('/evri/webhook', [EVRiController::class, 'webhook'])->name('api.v1.evri.webhook');

    // EVRi Admin Routes (protected)
    Route::middleware('jwt.auth')->group(function () {
        Route::post('/evri/transactions/{transaction}/create-label', [EVRiController::class, 'createLabel'])->name('api.v1.evri.create-label');
        Route::get('/evri/shipments/{shipment}/tracking', [EVRiController::class, 'getTrackingInfo'])->name('api.v1.evri.tracking');
        Route::post('/evri/shipments/{shipment}/cancel', [EVRiController::class, 'cancelLabel'])->name('api.v1.evri.cancel');
        Route::post('/evri/tracking/update', [EVRiController::class, 'updateTracking'])->name('api.v1.evri.update-tracking');

        // Wallet Routes
        Route::prefix('wallet')->controller(WalletController::class)->group(function () {
            Route::get('/', 'index')->name('api.v1.wallet.index');
            Route::get('/balance', 'index')->name('api.v1.wallet.balance'); // Alias for frontend compatibility
            Route::get('/transactions', 'transactions')->name('api.v1.wallet.transactions');
            Route::get('/payment-methods', 'paymentMethods')->name('api.v1.wallet.payment-methods');
            Route::post('/payment-methods', 'storePaymentMethod')->name('api.v1.wallet.payment-methods.store');
            Route::put('/payment-methods/{id}', 'updatePaymentMethod')->name('api.v1.wallet.payment-methods.update');
            Route::delete('/payment-methods/{id}', 'deletePaymentMethod')->name('api.v1.wallet.payment-methods.delete');
            Route::post('/payment-methods/{id}/set-default', 'setDefaultPaymentMethod')->name('api.v1.wallet.payment-methods.set-default');
        });

        // Withdrawal Routes
        Route::prefix('withdrawals')->controller(WithdrawalController::class)->group(function () {
            Route::get('/', 'index')->name('api.v1.withdrawals.index');
            Route::post('/', 'store')->name('api.v1.withdrawals.store');
            Route::get('/limits', 'withdrawalLimits')->name('api.v1.withdrawals.limits');
            Route::get('/{id}', 'show')->name('api.v1.withdrawals.show');
            Route::put('/{id}/cancel', 'cancel')->name('api.v1.withdrawals.cancel');
        });
    });
});
