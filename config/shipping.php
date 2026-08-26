<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Buyer-facing EVRi delivery fees by seller→buyer distance
    |--------------------------------------------------------------------------
    |
    | EVRi Classic bills the linked Nuriqa account and does not expose a public
    | rate quote API. These bands are what Nuriqa charges the buyer at checkout.
    | Distance is calculated from seller profile postal_code → buyer postcode.
    | Fees are charged once per distinct seller in the cart.
    |
    */

    'currency' => 'GBP',

    'fallback_fee' => (float) env('SHIPPING_FALLBACK_FEE', 7.99),

    'default_package' => [
        'weight_g' => (int) env('SHIPPING_DEFAULT_WEIGHT_G', 500),
        'length_cm' => (int) env('SHIPPING_DEFAULT_LENGTH_CM', 30),
        'width_cm' => (int) env('SHIPPING_DEFAULT_WIDTH_CM', 20),
        'height_cm' => (int) env('SHIPPING_DEFAULT_HEIGHT_CM', 10),
    ],

    /*
    | Distance bands are evaluated in order; the first matching max_miles wins.
    | Miles are approximate road/crow-flies distance between UK postcodes.
    */
    'distance_bands' => [
        ['max_miles' => 20, 'fee' => (float) env('SHIPPING_BAND_0_20', 3.99)],
        ['max_miles' => 50, 'fee' => (float) env('SHIPPING_BAND_20_50', 5.49)],
        ['max_miles' => 100, 'fee' => (float) env('SHIPPING_BAND_50_100', 6.99)],
        ['max_miles' => 200, 'fee' => (float) env('SHIPPING_BAND_100_200', 8.49)],
        ['max_miles' => null, 'fee' => (float) env('SHIPPING_BAND_200_PLUS', 9.99)],
    ],

    'postcodes_io' => [
        'base_url' => env('POSTCODES_IO_BASE_URL', 'https://api.postcodes.io'),
        'timeout' => 10,
    ],

];
