<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default API Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration will be used as default settings for third-party
    | API access. You can override these settings per API endpoint.
    |
    */

    'default_timeout' => 30,
    'default_retry_attempts' => 3,
    'default_retry_delay' => 1000, // milliseconds

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for API requests to prevent hitting third-party
    | API limits and to manage request throttling.
    |
    */

    'rate_limiting' => [
        'enabled' => true,
        'max_requests_per_minute' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Enable caching for API responses to improve performance and reduce
    | the number of requests to third-party APIs.
    |
    */

    'cache' => [
        'enabled' => true,
        'ttl' => 300, // seconds
        'prefix' => 'api_access_',
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