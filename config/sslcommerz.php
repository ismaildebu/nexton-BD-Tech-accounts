<?php

return [
    'enabled' => env('SSLCOMMERZ_ENABLED', false),
    'store_id' => env('SSLCOMMERZ_STORE_ID'),
    'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
    'sandbox_mode' => env('SSLCOMMERZ_SANDBOX_MODE', true),
    'sandbox_url' => env('SSLCOMMERZ_SANDBOX_URL', 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'),
    'live_url' => env('SSLCOMMERZ_LIVE_URL', 'https://securepay.sslcommerz.com/gwprocess/v4/api.php'),
];