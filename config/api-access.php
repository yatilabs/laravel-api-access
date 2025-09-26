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
    'layout' => "layouts.app", // e.g., 'layouts.app' to use your app's main layout

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
    | Model Owner Configuration
    |--------------------------------------------------------------------------
    | Configure API keys to be linked to specific model instances (e.g., Users).
    | This allows tracking which model owns each API key.
    |
    | Set 'enabled' to false to disable this feature entirely.
    | When enabled, API keys can be associated with any specified model.
    |--------------------------------------------------------------------------
    */
    'model_owner' => [
        'enabled' => true,                     // Enable model owner functionality
        'required' => false,                   // Whether owner selection is required
        'model' => 'App\\Models\\User',            // Model class to link API keys to
        'id_column' => 'id',                   // Primary key column name
        'title_column' => 'name',              // Column to display as owner name
        'label' => 'User',                     // Display label for the model type
        
        // Optional: Additional columns to display in dropdowns
        'additional_columns' => [
            // 'email',                        // Show email in dropdown
        ],
        
        // Optional: Query constraints for owner selection
        'constraints' => [
            // 'active' => true,               // Only show active users
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    | Configure comprehensive logging for API requests and responses.
    | This includes request/response data, timing, errors, and security info.
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,                     // Master switch for logging
        'log_requests' => true,                // Log incoming request data
        'log_responses' => true,               // Log response data  
        'log_errors' => true,                  // Log errors and failures
        'log_headers' => true,                 // Include request/response headers
        'log_request_body' => true,            // Include request body content
        'log_response_body' => true,           // Include response body content
        'log_query_parameters' => true,        // Include query string parameters
        'log_execution_time' => true,          // Track request processing time
        'log_ip_address' => true,              // Track client IP addresses
        'log_user_agent' => true,              // Track user agents
        'max_body_size' => 102400,             // Max bytes to log for request/response body (100KB)
        'retention_days' => 90,                // Days to keep logs before cleanup
        'cleanup_enabled' => true,             // Enable automatic log cleanup
        'sensitive_headers' => [               // Headers to exclude from logging for security
            'authorization',
            'x-api-key',
            'x-api-secret',
            'cookie',
            'set-cookie',
        ],
        'sensitive_fields' => [                // Request/response fields to mask in logs
            'password',
            'secret',
            'token',
            'api_key',
            'api_secret',
        ],
    ],
];