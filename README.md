# Laravel API Access Package

A comprehensive Laravel package for managing API keys with domain restrictions, secret authentication, and a beautiful management interface.

## ✨ Features

- **Complete API Key Management**: Create, update, delete, and manage API keys with full metadata
- **Domain Restrictions**: Restrict API keys to specific domains with wildcard pattern support  
- **Secure Authentication**: Multiple authentication methods with bcrypt secret hashing
- **Test/Live Modes**: Separate environments with different validation rules
- **Beautiful UI**: Modern responsive interface with copy buttons and modals
- **Built-in Controllers & Routes**: No need to create controllers in your app
- **Usage Tracking**: Track API key usage and last used timestamps
- **Copy-to-Clipboard**: Easy copying of API keys and secrets

## 🚀 Quick Installation

### 1. Install via Composer

```bash
composer require yatilabs/laravel-api-access
```

### 2. Publish and Run Migrations

```bash
php artisan vendor:publish --provider="Yatilabs\ApiAccess\ApiAccessServiceProvider" --tag="migrations"
php artisan migrate
```

### 3. Publish Assets (CSS)

```bash
php artisan vendor:publish --provider="Yatilabs\ApiAccess\ApiAccessServiceProvider" --tag="assets"
```

### 4. Publish Configuration (Optional)

```bash
php artisan vendor:publish --provider="Yatilabs\ApiAccess\ApiAccessServiceProvider" --tag="config"
```

## 📋 Usage

### Built-in Routes

The package automatically registers routes for you! No need to create controllers.

By default, the management interface is available at: **`/api-access`**

### Configuration

You can customize the route prefix in your `.env` file:

```env
API_ACCESS_ROUTE_PREFIX=api-management
API_ACCESS_DEFAULT_MODE=test
```

Or publish and edit the config file:

```php
## Configuration

After installation, you can customize the package behavior by editing the published configuration file:

```php
// config/api-access.php

return [
    /**
     * Custom Layout Integration
     * Set to your application's layout file to integrate the management interface
     * with your existing design. Leave null for standalone interface.
     * Example: 'layouts.app'
     */
    'layout' => null,

    /**
     * Route Configuration
     */
    'routes' => [
        'prefix' => 'api-access',
        'middleware' => ['web'],
        'name_prefix' => 'api-access.',
    ],

    /**
     * Database Configuration
     */
    'database' => [
        'connection' => null, // Use default connection
        'api_keys_table' => 'api_keys',
        'api_key_domains_table' => 'api_key_domains',
    ],
];
```

### Layout Integration

To integrate the API Access management interface with your existing Laravel application layout:

1. **Set your layout file** in the configuration:
   ```php
   'layout' => 'layouts.app', // Your app's main layout
   ```

2. **Ensure your layout has the required sections**:
   ```blade
   <!-- layouts/app.blade.php -->
   <!DOCTYPE html>
   <html>
   <head>
       <!-- Your head content -->
       <meta name="csrf-token" content="{{ csrf_token() }}">
       <!-- Bootstrap CSS (required) -->
       <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
       <!-- Font Awesome (required) -->
       <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   </head>
   <body>
       <!-- Your navigation/header -->
       
       <main>
           @yield('content')
       </main>
       
       <!-- Your footer -->
       
       <!-- Bootstrap JS (required) -->
       <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
       <!-- jQuery (required) -->
       <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
       
       @stack('scripts')
   </body>
   </html>
   ```

3. **Required Dependencies**: The management interface requires:
   - Bootstrap 5.3+
   - Font Awesome 6.0+
   - jQuery 3.7+
   - CSRF token meta tag

If you leave `layout` as `null`, the package will use its own standalone layout with all dependencies included.

### Force Publishing Configuration

To update your configuration file after package updates, use the dedicated command:

```bash
php artisan api-access:publish-config --force
```

This command provides helpful information about configuration options and safely overwrites your existing config file.
```

### Available Routes

The package provides these routes automatically:

- `GET /api-access` - Main management interface
- `POST /api-access/api-keys` - Create API key
- `POST /api-access/api-keys/{id}/update` - Update API key
- `POST /api-access/api-keys/{id}/delete` - Delete API key
- `POST /api-access/api-keys/{id}/regenerate-secret` - Regenerate secret
- `POST /api-access/api-keys/{id}/toggle-status` - Toggle active status
- `POST /api-access/domains` - Create domain restriction
- `POST /api-access/domains/{id}/update` - Update domain restriction
- `POST /api-access/domains/{id}/delete` - Delete domain restriction

### Navigation

Simply visit `/api-access` in your application (after authenticating) to access the management interface.

## 🛡️ API Authentication

### Using the Middleware

Add the middleware to protect your API routes:

```php
// In your routes/api.php or routes/web.php
Route::middleware('api.key')->group(function () {
    Route::get('/protected', function () {
        return 'This is protected!';
    });
});
```

### Authentication Methods

The package supports multiple authentication methods:

1. **Bearer Token** (Recommended)
```bash
curl -H "Authorization: Bearer your-api-key" http://your-app.com/api/protected
```

2. **Custom Header**
```bash
curl -H "X-API-Key: your-api-key" -H "X-API-Secret: your-secret" http://your-app.com/api/protected
```

3. **Query Parameters**
```bash
curl "http://your-app.com/api/protected?api_key=your-api-key&api_secret=your-secret"
```

4. **Request Body**
```json
{
    "api_key": "your-api-key",
    "api_secret": "your-secret"
}
```

## 🎨 UI Features

### Modern Interface
- **Clean Tables**: Properly formatted tables with responsive design
- **Modal Dialogs**: Create and edit forms in elegant modals
- **Copy Buttons**: One-click copying for API keys and secrets
- **Toast Notifications**: Instant feedback for all actions
- **Status Badges**: Visual indicators for API key status and mode

### Copy-to-Clipboard
The interface includes copy buttons next to all API keys, secrets, and domain patterns for easy copying.

### Secret Management
- Secrets are only shown once after creation/regeneration
- Secure modal display with copy functionality
- Visual warnings about secret security

## 🔧 Advanced Usage

### Custom Middleware

You can create custom middleware that uses the API key verification:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Yatilabs\ApiAccess\Middleware\VerifyApiKey;

class CustomApiMiddleware extends VerifyApiKey
{
    public function handle(Request $request, Closure $next)
    {
        // First verify the API key
        $response = parent::handle($request, $next);
        
        if ($response->getStatusCode() === 401) {
            return $response;
        }
        
        // Get API key model
        $apiKey = $request->attributes->get('api_key_model');
        
        // Add custom checks (e.g., usage limits, specific permissions)
        if ($apiKey->usage_count > 1000) {
            return response()->json([
                'error' => 'Usage limit exceeded',
                'message' => 'API key has exceeded usage limits'
            ], 429);
        }
        
        return $next($request);
    }
}
```

### Using the Service Directly

If you want to manage API keys programmatically:

```php
use Yatilabs\ApiAccess\Services\ApiAccessService;

class YourController extends Controller
{
    protected $apiAccessService;

    public function __construct(ApiAccessService $apiAccessService)
    {
        $this->apiAccessService = $apiAccessService;
    }

    public function createApiKey()
    {
        $apiKey = $this->apiAccessService->createApiKey([
            'description' => 'My API Key',
            'mode' => 'test',
            'is_active' => true
        ]);

        // $apiKey->plain_secret contains the unhashed secret (only available once)
        return $apiKey;
    }
}
```

## 📊 Database Structure

### `api_keys` Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `key` - Unique API key (auto-generated)
- `secret` - Bcrypt hash of secret (nullable)
- `description` - Optional description
- `is_active` - Boolean status
- `expires_at` - Optional expiration date
- `mode` - 'test' or 'live'
- `usage_count` - Request counter
- `last_used_at` - Last usage timestamp
- `created_at` / `updated_at` - Timestamps

### `api_key_domains` Table
- `id` - Primary key
- `api_key_id` - Foreign key to api_keys table
- `domain_pattern` - Domain pattern (supports wildcards)
- `created_at` / `updated_at` - Timestamps

## 🔍 Domain Patterns

The package supports flexible domain patterns:

- `example.com` - Exact match
- `*.example.com` - Subdomain wildcard
- `*` - Match any domain
- Test mode automatically allows domains configured in `localhost_domains` config

### Test Mode Domain Configuration

In test mode, API keys automatically allow domains specified in the `localhost_domains` configuration array. This makes development easier by allowing local development domains without manually adding domain restrictions.

**Default allowed domains in test mode:**
- `localhost`
- `127.0.0.1`
- `::1` (IPv6 localhost)
- `0.0.0.0`
- `*.test` (any .test domain)
- `*.local` (any .local domain)
- `*.dev` (any .dev domain)

You can customize these in your config file:

```php
'localhost_domains' => [
    'localhost',
    '127.0.0.1',
    '::1',
    '0.0.0.0',
    '*.test',
    '*.local',
    '*.dev',
    'my-custom-dev.domain',
    '*.staging',
],
```

**Note:** Wildcard patterns are supported in the localhost_domains configuration.

## 🧪 Testing

```php
use Yatilabs\ApiAccess\Models\ApiKey;

public function test_api_key_creation()
{
    $user = User::factory()->create();
    
    $apiKey = ApiKey::create([
        'user_id' => $user->id,
        'key' => 'test_key',
        'secret' => 'test_secret', // Will be auto-hashed by model
        'description' => 'Test Key',
        'is_active' => true,
        'mode' => 'test'
    ]);

    $this->assertTrue($apiKey->verifySecret('test_secret'));
}
```

## 📝 Configuration Options

```php
return [
    // Basic Settings
    'default_mode' => 'test',              // Default mode for new API keys
    'key_prefix' => 'ak_',                 // Prefix for generated API keys
    'key_length' => 32,                    // Length of the API key
    'secret_length' => 64,                 // Length of the API secret

    // Layout Integration
    'layout' => null,                      // Custom layout file (e.g., 'layouts.app')

    // Routes Configuration  
    'routes' => [
        'prefix' => 'api-access',          // URL prefix for management interface
        'middleware' => ['web', 'auth'],   // Middleware for management routes
        'name_prefix' => 'api-access.',    // Route name prefix
    ],

    // Test Mode Allowed Domains
    // These domains are automatically allowed when API keys are in test mode
    'localhost_domains' => [
        'localhost',                        // Standard localhost
        '127.0.0.1',                       // IPv4 loopback
        '::1',                             // IPv6 loopback
        '0.0.0.0',                         // Any IPv4 address
        '*.test',                          // All .test domains (wildcards supported)
        '*.local',                         // All .local domains
        '*.dev',                           // All .dev domains
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => true,                 // Enable request logging
        'log_requests' => true,            // Log incoming requests
        'log_responses' => true,           // Log responses
        'log_errors' => true,              // Log errors
    ],
];
```

### Key Configuration Notes

- **localhost_domains**: Domains automatically allowed for API keys in test mode. Add your custom development domains here.
- **layout**: Set to integrate with your existing app layout (e.g., 'layouts.app'). Leave null for standalone interface.
- **routes.prefix**: Change if you need a different URL prefix than 'api-access'.
- **routes.middleware**: Customize the middleware stack for the management interface.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🆘 Support

If you encounter any issues or have questions, please [open an issue](https://github.com/yatilabs/laravel-api-access/issues) on GitHub.