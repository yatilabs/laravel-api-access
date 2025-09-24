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
    'default_mode' => 'test',
    'key_prefix' => 'ak_',
    'key_length' => 32,
    'secret_length' => 64,

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => 'api-access',
        'middleware' => ['web', 'auth'],
        'name_prefix' => 'api-access.',
    ],

    /*
    |--------------------------------------------------------------------------
    | View Settings
    |--------------------------------------------------------------------------
    | You can specify a custom layout file for the management interface.
    | If null, the package will use its standalone layout.
    */
    'layout' => null, // e.g., 'layouts.app' to use your app's main layout

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