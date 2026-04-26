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
    | OTP email delivery
    |--------------------------------------------------------------------------
    |
    | Keep email OTP support available, but disabled by default so OTP can be
    | phone-only until you choose to re-enable it.
    |
    */

    'send_email' => (bool) env('OTP_SEND_EMAIL', false),

];
