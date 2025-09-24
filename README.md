# Laravel API Access Package

A Laravel package that provides comprehensive API key management with domain restrictions, usage tracking, and secure authentication for your Laravel applications.

## Features

- **API Key Management**: Generate, manage, and validate unique API keys
- **Domain Restrictions**: Restrict API key usage to specific domains with wildcard support
- **Usage Tracking**: Track API key usage count and last used timestamps
- **Secure Authentication**: Optional secret-based authentication with hashing
- **Expiration Control**: Set expiration dates for API keys
- **Live/Test Modes**: Separate API keys for different environments
- **Middleware Protection**: Built-in Laravel middleware for route protection
- **Laravel Integration**: Seamless integration with Laravel's Eloquent ORM

## Installation

You can install the package via Composer:

```bash
composer require yatilabs/api-access
```

## Configuration

Publish the migrations:

```bash
php artisan vendor:publish --provider="Yatilabs\ApiAccess\ApiAccessServiceProvider" --tag="migrations"
```

Then run the migrations:

```bash
php artisan migrate
```

This will create the necessary database tables:
- `api_keys` - Store API keys with user associations and settings
- `api_key_domains` - Store domain restrictions for API keys

## Usage

### Creating API Keys

```php
use Yatilabs\ApiAccess\Models\ApiKey;

// Create a basic API key
$apiKey = ApiKey::create([
    'user_id' => 1,
    'description' => 'Mobile App API Key',
]);

// Create an API key with expiration and secret
$apiKey = ApiKey::create([
    'user_id' => 1,
    'description' => 'Partner Integration',
    'expires_at' => now()->addMonths(6),
    'secret' => 'your-secret-key',
    'mode' => 'live', // or 'test'
]);
```

### Adding Domain Restrictions

```php
// Allow specific domain
$apiKey->domains()->create(['domain_pattern' => 'example.com']);

// Allow all subdomains
$apiKey->domains()->create(['domain_pattern' => '*.example.com']);

// Allow multiple domains
$apiKey->domains()->createMany([
    ['domain_pattern' => 'app.example.com'],
    ['domain_pattern' => '*.staging.example.com'],
]);
```

### Validating API Keys

```php
use Yatilabs\ApiAccess\Models\ApiKey;

// Find and validate API key
$apiKey = ApiKey::findByKey('ak_your_api_key_here');

if ($apiKey && $apiKey->isActive()) {
    // Check domain restrictions
    if ($apiKey->isDomainAllowed('app.example.com')) {
        // API key is valid for this domain
        $apiKey->incrementUsage();
        
        // Your API logic here
    }
}
```

### Checking API Key Status

```php
// Check if API key is active (not expired and enabled)
$isActive = $apiKey->isActive();

// Check if API key has expired
$hasExpired = $apiKey->hasExpired();

// Verify secret (if API key has a secret)
$isValidSecret = $apiKey->verifySecret('provided-secret');
```

### Query Scopes

```php
// Get only active API keys
$activeKeys = ApiKey::active()->get();

// Get live mode API keys
$liveKeys = ApiKey::live()->get();

// Get test mode API keys
$testKeys = ApiKey::test()->get();

// Get API keys for a specific user
$userKeys = ApiKey::where('user_id', 1)->active()->get();
```

### Domain Pattern Matching

The package supports flexible domain pattern matching:

```php
use Yatilabs\ApiAccess\Models\ApiKeyDomain;

$domain = ApiKeyDomain::create([
    'api_key_id' => $apiKey->id,
    'domain_pattern' => '*.example.com'
]);

// Check if domain matches pattern
$domain->matches('app.example.com'); // true
$domain->matches('staging.example.com'); // true
$domain->matches('other.com'); // false
```

## Middleware Protection

The package includes a powerful middleware for automatic API key verification and protection of your routes.

### Basic Middleware Usage

```php
// In your routes file (routes/api.php)
Route::middleware('api.key')->group(function () {
    Route::get('/protected-endpoint', 'YourController@method');
    Route::post('/another-endpoint', 'YourController@another');
});

// Or apply to individual routes
Route::get('/single-protected', 'YourController@method')->middleware('api.key');
```

### Passing API Key and Secret

The middleware supports multiple ways to pass the API key and secret:

#### Method 1: Authorization Header (Bearer Token)
```bash
curl -H "Authorization: Bearer ak_your_api_key_here" \
     -H "X-API-Secret: your_secret_key" \
     https://yourapi.com/protected-endpoint
```

#### Method 2: Custom Headers
```bash
curl -H "X-API-Key: ak_your_api_key_here" \
     -H "X-API-Secret: your_secret_key" \
     https://yourapi.com/protected-endpoint
```

### Domain Restrictions

The middleware automatically handles domain restrictions:

#### Test Mode
- **Automatically allows**: `localhost`, `127.0.0.1`, `::1`, `0.0.0.0`
- **Also respects**: Any domains added to the `api_key_domains` table

```php
$apiKey = ApiKey::create([
    'user_id' => 1,
    'mode' => 'test', // Allows localhost + configured domains
]);
```

#### Live Mode
- **Only allows**: Domains explicitly added to the `api_key_domains` table
- **Blocks**: All localhost/local development domains

```php
$apiKey = ApiKey::create([
    'user_id' => 1,
    'mode' => 'live', // Only allows configured domains
]);

// Add allowed domains
$apiKey->domains()->create(['domain_pattern' => 'api.yoursite.com']);
$apiKey->domains()->create(['domain_pattern' => '*.yoursite.com']);
```

### Middleware Features

The middleware automatically:

1. ✅ **Extracts API key** from multiple sources (headers, query, body)
2. ✅ **Validates API key exists** in database
3. ✅ **Checks if API key is active** (`is_active = true`)
4. ✅ **Checks expiration status** (not past `expires_at`)
5. ✅ **Verifies secret** (if API key has a secret)
6. ✅ **Validates domain restrictions** (test/live mode logic)
7. ✅ **Increments usage count** on successful validation
8. ✅ **Adds API key model** to request for controller access

### Accessing API Key in Controllers

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yatilabs\ApiAccess\Models\ApiKey;

class ApiController extends Controller
{
    public function protectedMethod(Request $request)
    {
        // Get the authenticated API key model
        $apiKeyModel = $request->attributes->get('api_key_model');
        
        // Access API key information
        $userId = $apiKeyModel->user_id;
        $usageCount = $apiKeyModel->usage_count;
        $mode = $apiKeyModel->mode; // 'live' or 'test'
        
        return response()->json([
            'message' => 'Access granted',
            'api_key_id' => $apiKeyModel->id,
            'user_id' => $userId,
            'usage_count' => $usageCount,
        ]);
    }
}
```

### Error Responses

The middleware returns JSON error responses:

```json
{
    "error": "Unauthorized",
    "message": "API key is required"
}
```

Common error messages:
- `"API key is required"` - No API key provided
- `"Invalid API key"` - API key not found in database
- `"API key is inactive or expired"` - API key is disabled or expired
- `"API key has expired"` - API key past expiration date
- `"Invalid API key secret"` - Secret doesn't match
- `"Domain not allowed for this API key"` - Domain restriction violation

### Development vs Production Setup

#### Development Setup (Test Mode)
```php
// Create API key for development
$devApiKey = ApiKey::create([
    'user_id' => 1,
    'description' => 'Development API Key',
    'mode' => 'test', // Allows localhost automatically
]);

// Optionally add specific development domains
$devApiKey->domains()->create(['domain_pattern' => 'dev.yoursite.com']);
$devApiKey->domains()->create(['domain_pattern' => '*.test']);
```

#### Production Setup (Live Mode)
```php
// Create API key for production
$prodApiKey = ApiKey::create([
    'user_id' => 1,
    'description' => 'Production API Key',
    'mode' => 'live',
    'expires_at' => now()->addYear(),
    'secret' => 'secure-secret-key',
]);

// Add production domains only
$prodApiKey->domains()->createMany([
    ['domain_pattern' => 'api.yoursite.com'],
    ['domain_pattern' => 'app.yoursite.com'],
    ['domain_pattern' => '*.yoursite.com'],
]);
```

### Custom Middleware Usage

You can also create custom middleware that extends the base functionality:

```php
<?php

namespace App\Http\Middleware;

use Yatilabs\ApiAccess\Middleware\VerifyApiKey;
use Illuminate\Http\Request;
use Closure;

class CustomApiKeyMiddleware extends VerifyApiKey
{
    public function handle(Request $request, Closure $next)
    {
        // Run base API key verification
        $response = parent::handle($request, $next);
        
        // Add custom logic here
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            return $response; // Return error response
        }
        
        // Get API key model
        $apiKey = $request->attributes->get('api_key_model');
        
        // Add custom checks (e.g., rate limiting, specific permissions)
        if ($apiKey->usage_count > 1000) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'API key has exceeded usage limits'
            ], 429);
        }
        
        return $next;
    }
}
```

### Controller Helper Trait

Use the `HasApiKeyAccess` trait for easier access to API key information in your controllers:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yatilabs\ApiAccess\Traits\HasApiKeyAccess;

class ApiController extends Controller
{
    use HasApiKeyAccess;

    public function getData(Request $request)
    {
        // Easy access to API key information
        $apiKey = $this->getApiKey();
        $userId = $this->getApiKeyUserId();
        $usageCount = $this->getApiKeyUsageCount();
        
        // Check mode
        if ($this->isTestMode()) {
            // Test mode logic
            return $this->getTestData();
        }
        
        if ($this->isLiveMode()) {
            // Live mode logic
        }
    }
}
```

## Examples

The package includes practical examples in the `examples/` directory:

- `ExampleApiController.php` - Complete controller demonstrating middleware usage
- `routes_example.php` - Route definitions with various middleware configurations

### Quick Setup Example

```php
// 1. Create API key
$apiKey = ApiKey::create([
    'user_id' => 1,
    'description' => 'Mobile App',
    'mode' => 'test', // or 'live'
    'secret' => 'optional-secret-key',
]);

// 2. Add domain restrictions
$apiKey->domains()->create(['domain_pattern' => '*.yourapp.com']);

// 3. Protect your routes
Route::middleware('api.key')->get('/api/data', function () {
    return ['message' => 'Protected data'];
});

// 4. Test with curl
// curl -H "Authorization: Bearer ak_generated_key" http://localhost:8000/api/data
```

## Database Schema

### API Keys Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | BigInteger | Primary key |
| `key` | String(64) | Unique API key (auto-generated) |
| `secret` | String(128) | Optional hashed secret for additional security |
| `user_id` | BigInteger | Foreign key to users table |
| `description` | String | Optional description for the API key |
| `is_active` | Boolean | Whether the API key is active (default: true) |
| `expires_at` | Timestamp | Optional expiration date |
| `last_used_at` | Timestamp | Last time the API key was used |
| `usage_count` | BigInteger | Number of times the API key has been used |
| `mode` | Enum | 'live' or 'test' mode (default: 'live') |

### API Key Domains Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | BigInteger | Primary key |
| `api_key_id` | BigInteger | Foreign key to api_keys table |
| `domain_pattern` | String | Domain pattern (supports wildcards like *.example.com) |

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email daman.mokha@yatilabs.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Daman Mokha](https://github.com/damanmokha)
- [All Contributors](../../contributors)
