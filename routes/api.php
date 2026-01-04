<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ProductController;
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
});
