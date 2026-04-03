<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP lifetime
    |--------------------------------------------------------------------------
    |
    | Number of minutes after generation that an email verification OTP remains valid.
    |
    */

    'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 15),

];
