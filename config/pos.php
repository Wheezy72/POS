<?php

return [
    'auth' => [
        'allow_pin_roster_fallback' => env('POS_ALLOW_PIN_ROSTER_FALLBACK', false),
    ],
    'printer' => [
        'proxy_enabled' => env('POS_PRINTER_PROXY_ENABLED', false),
        'proxy_url' => env('POS_PRINTER_PROXY_URL'),
    ],
    'clock' => [
        'warn_drift_seconds' => env('POS_CLOCK_WARN_DRIFT_SECONDS', 30),
        'reject_drift_seconds' => env('POS_CLOCK_REJECT_DRIFT_SECONDS', 300),
    ],
    'pricing' => [
        'expiry_markdown_hours' => env('POS_EXPIRY_MARKDOWN_HOURS', 48),
        'expiry_markdown_percentage' => env('POS_EXPIRY_MARKDOWN_PERCENTAGE', 50),
        'stock_age_mid_days' => env('POS_STOCK_AGE_MID_DAYS', 90),
        'stock_age_old_days' => env('POS_STOCK_AGE_OLD_DAYS', 120),
        'margin_multiplier_fresh' => env('POS_MARGIN_MULTIPLIER_FRESH', 1.15),
        'margin_multiplier_mid' => env('POS_MARGIN_MULTIPLIER_MID', 1.05),
        'margin_multiplier_old' => env('POS_MARGIN_MULTIPLIER_OLD', 1.00),
    ],
];
