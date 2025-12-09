<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure CORS settings for your application. CORS allows
    | controlled access to your API from different origins.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
        env('FRONTEND_URL', 'http://localhost:5173'),
        env('WEBSITE_URL', 'http://localhost:3000'),
    ],

    'allowed_origins_patterns' => [
        '#^https://.*\.coopvestafrica\.com$#',
        '#^https://coopvestafrica\.com$#',
    ],

    'allowed_headers' => [
        'Accept',
        'Accept-Language',
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-Token',
        'X-Firebase-Token',
        'X-API-Key',
    ],

    'exposed_headers' => [
        'X-Total-Count',
        'X-Page-Count',
        'X-Per-Page',
        'X-Current-Page',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,
];
