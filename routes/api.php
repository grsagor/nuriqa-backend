<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
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
        Route::post('/store', 'store')->name('api.v1.products.store')->middleware('jwt.auth');
    });
});
