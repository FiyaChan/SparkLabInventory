<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ToyyibPay API Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials and configuration settings for the ToyyibPay Payment Gateway.
    | Set TOYYIBPAY_SANDBOX to false in production to use live servers.
    |
    */

    'user_secret_key' => env('TOYYIBPAY_USER_SECRET_KEY', ''),
    'category_code'   => env('TOYYIBPAY_CATEGORY_CODE', ''),
    'sandbox'         => env('TOYYIBPAY_SANDBOX', true),

    'sandbox_url'     => env('TOYYIBPAY_SANDBOX_URL', 'https://dev.toyyibpay.com'),
    'production_url'  => env('TOYYIBPAY_PRODUCTION_URL', 'https://toyyibpay.com'),

    // Bill charge setting: 0 = owner pays, 1 = customer pays FPX charge, 2 = card charges
    'bill_charge_to_customer' => env('TOYYIBPAY_CHARGE_TO_CUSTOMER', 1),

    // Payment channel: 0 = FPX only, 1 = Credit Card only, 2 = FPX & Credit Card
    'payment_channel' => env('TOYYIBPAY_PAYMENT_CHANNEL', '0'),
];
