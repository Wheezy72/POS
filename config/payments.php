<?php

return [
    'daraja' => [
        'base_url' => env('DARAJA_BASE_URL', 'https://sandbox.safaricom.co.ke'),
        'consumer_key' => env('DARAJA_CONSUMER_KEY'),
        'consumer_secret' => env('DARAJA_CONSUMER_SECRET'),
        'shortcode' => env('DARAJA_SHORTCODE'),
        'passkey' => env('DARAJA_PASSKEY'),
        'stk_callback_url' => env('DARAJA_STK_CALLBACK_URL'),
        'timeout_seconds' => env('DARAJA_TIMEOUT_SECONDS', 15),
    ],
    'pesapal' => [
        'base_url' => env('PESAPAL_BASE_URL', 'https://cybqa.pesapal.com/pesapalv3'),
        'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
        'callback_url' => env('PESAPAL_CALLBACK_URL'),
    ],
];
