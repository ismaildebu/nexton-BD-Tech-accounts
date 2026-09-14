<?php

return [
    'enabled' => env('SSLCOMMERZ_ENABLED', false),
    'store_id' => env('SSLCOMMERZ_STORE_ID'),
    'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
    'sandbox_mode' => env('SSLCOMMERZ_SANDBOX_MODE', true),
    'sandbox_url' => env('SSLCOMMERZ_SANDBOX_URL', 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'),
    'live_url' => env('SSLCOMMERZ_LIVE_URL', 'https://securepay.sslcommerz.com/gwprocess/v4/api.php'),
    'sandbox_validation_url' => env(
        'SSLCOMMERZ_SANDBOX_VALIDATION_URL',
        'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php'
    ),
    'live_validation_url' => env(
        'SSLCOMMERZ_LIVE_VALIDATION_URL',
        'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php'
    ),
];
