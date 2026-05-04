<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP lifetime
    |--------------------------------------------------------------------------
    |
    | Number of minutes after generation that an OTP remains valid.
    |
    */

    'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | OTP email — forgot password (registration ignores this)
    |--------------------------------------------------------------------------
    |
    | Signup and resend OTP use SMS only; they do not read this value.
    |
    | Forgot password sends one OTP code via SMS (Twilio) always. When true, the same
    | code is also emailed — set true if you want email + SMS for password reset.
    |
    | Legacy: when OtpService::generateForUser($user) is called with no delivery array,
    | this flag also controls whether email is attempted (SMS defaults on).
    |
    */

    'send_email' => (bool) env('OTP_SEND_EMAIL', false),

];
