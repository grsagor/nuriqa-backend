<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Withdrawal Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for the withdrawal system.
    |
    */

    'minimum_amount' => env('WITHDRAWAL_MINIMUM_AMOUNT', 10.00),

    'maximum_withdrawal' => env('WITHDRAWAL_MAXIMUM_AMOUNT', 10000.00),

    'max_pending_amount' => env('WITHDRAWAL_MAX_PENDING_AMOUNT', 5000.00),

    'withdrawal_fee_percentage' => env('WITHDRAWAL_FEE_PERCENTAGE', 0),

    'withdrawal_fee_fixed' => env('WITHDRAWAL_FEE_FIXED', 0),

    'processing_time_hours' => env('WITHDRAWAL_PROCESSING_TIME_HOURS', 24),

    'allowed_providers' => [
        'bank_account',
        'paypal', 
        'stripe',
        'wise'
    ],

    'auto_approval_threshold' => env('WITHDRAWAL_AUTO_APPROVAL_THRESHOLD', 100.00),

    'requires_manual_approval' => env('WITHDRAWAL_REQUIRES_MANUAL_APPROVAL', true),

    'notification_settings' => [
        'email_notifications' => env('WITHDRAWAL_EMAIL_NOTIFICATIONS', true),
        'sms_notifications' => env('WITHDRAWAL_SMS_NOTIFICATIONS', false),
    ],

    'security' => [
        'max_withdrawal_requests_per_day' => env('WITHDRAWAL_MAX_REQUESTS_PER_DAY', 5),
        'require_2fa_for_large_amounts' => env('WITHDRAWAL_REQUIRE_2FA_LARGE_AMOUNTS', true),
        'large_amount_threshold' => env('WITHDRAWAL_LARGE_AMOUNT_THRESHOLD', 1000.00),
    ],
];