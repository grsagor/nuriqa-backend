<?php

use Illuminate\Support\Facades\Route;
use Modules\Gazian\Http\Controllers\Admin\DashboardController;
use Modules\Gazian\Http\Controllers\Admin\NewsletterSubscriberController;
use Modules\Gazian\Http\Controllers\Admin\TradeEnquiryController;

Route::prefix('gazian/admin')->name('gazian.admin.')->middleware('role:admin')->group(function () {
    Route::prefix('dashboard')->name('dashboard.')->controller(DashboardController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });
    Route::get('/', fn () => redirect()->route('gazian.admin.dashboard.index'));

    Route::prefix('newsletter-subscribers')->name('newsletter-subscribers.')->controller(NewsletterSubscriberController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export-csv', 'exportCsv')->name('export-csv');
        Route::get('/list', 'list')->name('list');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    Route::prefix('trade-enquiries')->name('trade-enquiries.')->controller(TradeEnquiryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/list', 'list')->name('list');
        Route::get('/show/{id}', 'show')->name('show');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
});
