<?php

use Illuminate\Support\Facades\Route;
use Modules\Gazian\Http\Controllers\Api\NewsletterController;
use Modules\Gazian\Http\Controllers\Api\TradeEnquiryController;

Route::prefix('v1/gazian')->group(function () {
    Route::post('/newsletter', [NewsletterController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('v1.gazian.newsletter');

    Route::post('/trade-enquiry', [TradeEnquiryController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('v1.gazian.trade-enquiry');
});
