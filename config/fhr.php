<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment routing
    |--------------------------------------------------------------------------
    |
    | 'external' (FHR hosts the checkout on their Stripe) or 'internal' (the
    | consuming app takes payment and confirms with FHR afterwards).
    |
    */

    'payment_mode' => env('FHR_PAYMENT_MODE', env('FHR_BOOKING_MODE', 'external')),

    /*
    |--------------------------------------------------------------------------
    | Production credentials
    |--------------------------------------------------------------------------
    */

    'base_url' => env('FHR_API_URL', 'https://www.bookfhr.com/api'),
    'payment_url' => env('FHR_PAYMENT_URL', 'https://www.bookfhr.com/payment'),
    'token' => env('FHR_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Sandbox credentials (used when the caller requests sandbox mode)
    |--------------------------------------------------------------------------
    */

    'sandbox_base_url' => env('FHR_SANDBOX_API_URL', 'https://bookfhr.dev/api'),
    'sandbox_payment_url' => env('FHR_SANDBOX_PAYMENT_URL', 'https://bookfhr.dev/payment'),
    'sandbox_token' => env('FHR_SANDBOX_API_TOKEN'),
    'sandbox_tenant' => env('FHR_SANDBOX_TENANT'),

    /*
    |--------------------------------------------------------------------------
    | Common settings
    |--------------------------------------------------------------------------
    */

    'tenant' => env('FHR_TENANT'),
    'image_base_url' => env('FHR_IMAGE_BASE_URL', 'https://img-assets.bookfhr.com'),
    'source' => env('FHR_SOURCE_CODE', 'SPS0'),
    'timeout' => env('FHR_TIMEOUT', 30),
    'retry_times' => env('FHR_RETRY_TIMES', 3),
    'retry_sleep_ms' => env('FHR_RETRY_SLEEP_MS', 500),

    /*
    |--------------------------------------------------------------------------
    | Log channel
    |--------------------------------------------------------------------------
    |
    | The channel the client logs to. Null uses the application's default
    | channel. Set FHR_LOG_CHANNEL to route FHR logs to a dedicated channel.
    |
    */

    'log_channel' => env('FHR_LOG_CHANNEL'),

    'rate_limit' => [
        'max_requests' => env('FHR_RATE_LIMIT_MAX', 100),
        'window_seconds' => env('FHR_RATE_LIMIT_WINDOW', 60),
    ],

    'cache' => [
        'search_ttl_minutes' => env('FHR_CACHE_SEARCH_TTL', 15),
        'inventory_ttl_minutes' => env('FHR_CACHE_INVENTORY_TTL', 1440), // 24 hours
    ],

    'sync' => [
        'search_months_ahead' => env('FHR_SYNC_SEARCH_MONTHS', 3),
        'search_duration_days' => env('FHR_SYNC_SEARCH_DAYS', 8),
    ],

    // FHR search sessions expire after this many minutes.
    'search_expiry_minutes' => env('FHR_SEARCH_EXPIRY_MINUTES', 15),

];
