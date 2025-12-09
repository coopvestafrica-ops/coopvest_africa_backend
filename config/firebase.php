<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains all the settings needed to connect
    | to Firebase Admin SDK for authentication and user synchronization.
    |
    */

    'credentials_path' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase-credentials.json')),

    'database_url' => env('FIREBASE_DATABASE_URL'),

    'project_id' => env('FIREBASE_PROJECT_ID'),

    'api_key' => env('FIREBASE_API_KEY'),

    'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),

    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),

    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),

    'app_id' => env('FIREBASE_APP_ID'),

    'measurement_id' => env('FIREBASE_MEASUREMENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Admin SDK Settings
    |--------------------------------------------------------------------------
    */

    'admin_sdk' => [
        'enabled' => env('FIREBASE_ADMIN_SDK_ENABLED', true),
        'verify_id_token' => env('FIREBASE_VERIFY_ID_TOKEN', true),
        'cache_tokens' => env('FIREBASE_CACHE_TOKENS', true),
        'token_cache_ttl' => env('FIREBASE_TOKEN_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | User Synchronization Settings
    |--------------------------------------------------------------------------
    */

    'user_sync' => [
        'enabled' => env('FIREBASE_USER_SYNC_ENABLED', true),
        'auto_create_users' => env('FIREBASE_AUTO_CREATE_USERS', true),
        'sync_custom_claims' => env('FIREBASE_SYNC_CUSTOM_CLAIMS', true),
        'sync_metadata' => env('FIREBASE_SYNC_METADATA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Settings
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'auth' => [
            'enabled' => env('FIREBASE_AUTH_MIDDLEWARE_ENABLED', true),
            'throw_exceptions' => env('FIREBASE_AUTH_THROW_EXCEPTIONS', true),
        ],
        'sync' => [
            'enabled' => env('FIREBASE_SYNC_MIDDLEWARE_ENABLED', true),
            'queue_sync' => env('FIREBASE_QUEUE_SYNC', false),
        ],
    ],
];
