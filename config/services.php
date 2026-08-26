<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'base_url' => env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    ],

    'evri' => [
        // Hermes World / Evri Cloud (from integration email):
        // Auth Token URL: https://oauth.prod.evricloud.co.uk
        // API Base URL: https://api.hermesworld.co.uk
        'auth_token_url' => env('EVRI_AUTH_TOKEN_URL'),
        'base_url' => env('EVRI_BASE_URL', 'https://api.hermesworld.co.uk'),
        // Required by oauth.prod.evricloud.co.uk (Auth0). Usually the API base URL.
        'audience' => env('EVRI_AUDIENCE', env('EVRI_BASE_URL', 'https://api.hermesworld.co.uk')),
        // Portal client number / Client ID (e.g. 10917).
        'client_number' => env('EVRI_CLIENT_NUMBER', '10917'),
        'client_name' => env('EVRI_CLIENT_NAME', 'Nuriqa'),
        // Auth ID + Auth Secret from EVRi (used for Basic Auth on token request).
        'auth_id' => env('EVRI_AUTH_ID'),
        'auth_secret' => env('EVRI_AUTH_SECRET'),
        // Optional OAuth body client_id/client_secret. Defaults to Auth ID/Secret.
        'oauth_client_id' => env('EVRI_OAUTH_CLIENT_ID', env('EVRI_AUTH_ID')),
        'oauth_client_secret' => env('EVRI_OAUTH_CLIENT_SECRET', env('EVRI_AUTH_SECRET')),
        // Portal username/password (Nuriqa-sit / Nuriqa) — kept for reference / support.
        'username' => env('EVRI_USERNAME'),
        'password' => env('EVRI_PASSWORD'),
        'api_key' => env('EVRI_API_KEY'),
        'storage_disk' => env('EVRI_STORAGE_DISK', 'public'),
        's3_bucket' => env('EVRI_S3_BUCKET', 'nuriqa-labels'),
        'webhook_secret' => env('EVRI_WEBHOOK_SECRET'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM_NUMBER'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
    ],

    'firebase' => [
        // Public project ID only — enough to verify Google/Apple ID tokens (no service account JSON).
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

];
