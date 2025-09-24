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
    | View Settings
    |--------------------------------------------------------------------------
    | You can specify a custom layout file for the management interface.
    | If null, the package will use its standalone layout.
    */
    'layout' => null, // e.g., 'layouts.app' to use your app's main layout

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
    | Test Mode Allowed Domains
    |--------------------------------------------------------------------------
    | These domains are automatically allowed when API keys are in test mode.
    | Wildcard patterns are supported (e.g., *.test, *.local).
    |
    | This makes local development easier by allowing common development
    | domains without manually adding domain restrictions.
    |--------------------------------------------------------------------------
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
    | Logging Configuration
    |--------------------------------------------------------------------------
    | Configure logging for API requests and responses for debugging
    | and monitoring purposes.
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'log_requests' => true,
        'log_responses' => true,
        'log_errors' => true,
    ],
];