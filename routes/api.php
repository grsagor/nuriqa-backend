<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\JoinUsController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SponsorRequestController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/signup', 'signup')->name('api.v1.signup');
        Route::post('/verify-otp', 'verifyOtp')->name('api.v1.verify-otp');
        Route::post('/login', 'login')->name('api.v1.login');
        Route::post('/logout', 'logout')->name('api.v1.logout')->middleware('auth:api');
        Route::get('/my-user-info', 'myUserInfo')->name('api.v1.my-user-info')->middleware('jwt.auth');
    });

    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::get('/', 'index')->name('api.v1.products.index');
        Route::get('/categories', 'categories')->name('api.v1.products.categories');
        Route::get('/sizes', 'sizes')->name('api.v1.products.sizes');
        Route::get('/details/{id}', 'show')->name('api.v1.products.show');
        Route::post('/store', 'store')->name('api.v1.products.store')->middleware('jwt.auth');
    });

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
        Route::get('/my-requests', 'myRequests')->name('api.v1.sponsor-requests.my-requests')->middleware('jwt.auth');
        Route::post('/', 'store')->name('api.v1.sponsor-requests.store')->middleware('jwt.auth');
        Route::get('/{id}', 'show')->name('api.v1.sponsor-requests.show')->middleware('jwt.auth');
    });

    Route::prefix('orders')->controller(OrderController::class)->middleware('jwt.auth')->group(function () {
        Route::post('/create-payment-intent', 'createPaymentIntent')->name('api.v1.orders.create-payment-intent');
        Route::post('/checkout', 'checkout')->name('api.v1.orders.checkout');
        Route::post('/sponsor-checkout', 'sponsorCheckout')->name('api.v1.orders.sponsor-checkout');
        Route::post('/confirm-payment', 'confirmPayment')->name('api.v1.orders.confirm-payment');
        Route::get('/', 'index')->name('api.v1.orders.index');
        Route::get('/{id}', 'show')->name('api.v1.orders.show');
    });

    // Stripe webhook (no auth required)
    Route::post('/stripe/webhook', [OrderController::class, 'handleWebhook'])->name('api.v1.stripe.webhook');

    // Join Us Applications (no auth required)
    Route::post('/join-us', [JoinUsController::class, 'store'])->name('api.v1.join-us.store');

    // Contact Form (no auth required)
    Route::post('/contact', [ContactController::class, 'store'])->name('api.v1.contact.store');
});
