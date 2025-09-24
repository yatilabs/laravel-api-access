<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Access Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for the API Access package.
    | You can modify these values to customize the behavior of the package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'default_mode' => env('API_ACCESS_DEFAULT_MODE', 'test'),
    'key_prefix' => env('API_ACCESS_KEY_PREFIX', 'ak_'),
    'key_length' => env('API_ACCESS_KEY_LENGTH', 32),
    'secret_length' => env('API_ACCESS_SECRET_LENGTH', 64),

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => env('API_ACCESS_ROUTE_PREFIX', 'api-access'),
        'middleware' => ['web', 'auth'],
        'name_prefix' => 'api-access.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'hash_secrets' => env('API_ACCESS_HASH_SECRETS', true),
    'log_requests' => env('API_ACCESS_LOG_REQUESTS', true),
    'enforce_https' => env('API_ACCESS_ENFORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    */
    'table_prefix' => env('API_ACCESS_TABLE_PREFIX', ''),
    'connection' => env('API_ACCESS_DB_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | View Settings
    |--------------------------------------------------------------------------
    */
    'views' => [
        'layout' => env('API_ACCESS_LAYOUT', null), // Custom layout for views
        'pagination_size' => env('API_ACCESS_PAGINATION_SIZE', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Methods
    |--------------------------------------------------------------------------
    | Configure which authentication methods are enabled
    */
    'auth_methods' => [
        'bearer_token' => true,
        'custom_headers' => true,
        'query_params' => true,
        'request_body' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Localhost Domains (Test Mode)
    |--------------------------------------------------------------------------
    | Domains that are automatically allowed in test mode
    */
    'localhost_domains' => [
        'localhost',
        '127.0.0.1',
        '::1',
        '0.0.0.0',
        '*.test',
        '*.local',
        '*.dev',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for API requests and responses for debugging
    | and monitoring purposes.
    |
    */
    'logging' => [
        'enabled' => true,
        'log_requests' => true,
        'log_responses' => true,
        'log_errors' => true,
    ],
];