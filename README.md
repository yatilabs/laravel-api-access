# Laravel API Access Package

# Laravel API Access Package

A comprehensive Laravel package for managing API keys with domain restrictions, secret authentication, rate limiting, and a beautiful management interface.

## ✨ Features

- **Complete API Key Management**: Create, update, delete, and manage API keys with full metadata
- **Domain Restrictions**: Restrict API keys to specific domains with wildcard pattern support  
- **Secure Authentication**: Multiple authentication methods with bcrypt secret hashing
- **Test/Live Modes**: Separate environments with different validation rules
- **Beautiful UI**: Isolated Bootstrap CSS interface that integrates with any Laravel app
- **Service-Based Architecture**: Minimal controller code needed in your app
- **Rate Limiting**: Per-hour and per-day request limits (coming soon)
- **IP Filtering**: Allow/block specific IP addresses (coming soon)
- **Usage Tracking**: Track API key usage and last used timestamps

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

This will publish the isolated Bootstrap CSS to `public/vendor/yatilabs/api-access/api-access.css`.

## 📋 Basic Usage

### Step 1: Create a Controller in Your App

Create a simple controller that uses the package service:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yatilabs\ApiAccess\Services\ApiAccessService;
use Illuminate\Validation\ValidationException;

class ApiKeyController extends Controller
{
    protected $apiAccessService;

    public function __construct(ApiAccessService $apiAccessService)
    {
        $this->apiAccessService = $apiAccessService;
    }

    /**
     * Show API key management interface
     */
    public function index(Request $request)
    {
        $data = $this->apiAccessService->getViewData($request);
        
        return view('admin.api-keys.index', $data);
    }

    /**
     * Create new API key
     */
    public function store(Request $request)
    {
        try {
            $apiKey = $this->apiAccessService->createApiKey($request->all());
            
            // Store the secret in session to show once
            session()->flash("new_secret_{$apiKey->id}", $apiKey->plain_secret);
            
            return redirect()->route('admin.api-keys.index')
                ->with('success', "API key '{$apiKey->description}' created successfully!");
                
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create API key: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update API key
     */
    public function update(Request $request, $id)
    {
        try {
            $apiKey = $this->apiAccessService->updateApiKey($id, $request->all());
            
            return redirect()->route('admin.api-keys.index')
                ->with('success', "API key '{$apiKey->description}' updated successfully!");
                
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update API key: ' . $e->getMessage());
        }
    }

    /**
     * Delete API key
     */
    public function destroy($id)
    {
        try {
            $this->apiAccessService->deleteApiKey($id);
            return redirect()->route('admin.api-keys.index')
                ->with('success', 'API key deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete API key: ' . $e->getMessage());
        }
    }

    /**
     * Toggle API key status
     */
    public function toggleStatus($id)
    {
        try {
            $apiKey = $this->apiAccessService->toggleStatus($id);
            $status = $apiKey->is_active ? 'activated' : 'deactivated';
            
            return redirect()->route('admin.api-keys.index')
                ->with('success', "API key {$status} successfully!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Regenerate API key secret
     */
    public function regenerateSecret($id)
    {
        try {
            $apiKey = $this->apiAccessService->regenerateSecret($id);
            
            // Store the secret in session to show once
            session()->flash("new_secret_{$apiKey->id}", $apiKey->plain_secret);
            
            return redirect()->route('admin.api-keys.index')
                ->with('success', 'New secret generated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to regenerate secret: ' . $e->getMessage());
        }
    }
}
```

### Step 2: Create Domain Management Controller

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yatilabs\ApiAccess\Services\ApiAccessService;
use Illuminate\Validation\ValidationException;

class ApiKeyDomainController extends Controller
{
    protected $apiAccessService;

    public function __construct(ApiAccessService $apiAccessService)
    {
        $this->apiAccessService = $apiAccessService;
    }

    /**
     * Store domain restriction
     */
    public function store(Request $request)
    {
        try {
            $domain = $this->apiAccessService->createDomain($request->all());
            
            return redirect()->route('admin.api-keys.index', ['tab' => 'domains'])
                ->with('success', "Domain restriction '{$domain->domain_pattern}' added successfully!");
                
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->withFragment('domains');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to add domain restriction: ' . $e->getMessage())
                ->withInput()
                ->withFragment('domains');
        }
    }

    /**
     * Update domain restriction
     */
    public function update(Request $request, $id)
    {
        try {
            $domain = $this->apiAccessService->updateDomain($id, $request->all());
            
            return redirect()->route('admin.api-keys.index', ['tab' => 'domains'])
                ->with('success', "Domain restriction updated to '{$domain->domain_pattern}'!");
                
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withFragment('domains');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update domain restriction: ' . $e->getMessage())
                ->withFragment('domains');
        }
    }

    /**
     * Delete domain restriction
     */
    public function destroy($id)
    {
        try {
            $this->apiAccessService->deleteDomain($id);
            
            return redirect()->route('admin.api-keys.index', ['tab' => 'domains'])
                ->with('success', 'Domain restriction deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete domain restriction: ' . $e->getMessage())
                ->withFragment('domains');
        }
    }

    /**
     * Test domain matching (AJAX)
     */
    public function test(Request $request)
    {
        $request->validate([
            'api_key_id' => 'required|integer',
            'domain' => 'required|string'
        ]);

        try {
            $result = $this->apiAccessService->testDomainMatch(
                $request->api_key_id, 
                $request->domain
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

### Step 3: Add Routes

Add these routes to your `routes/web.php`:

```php
use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\ApiKeyDomainController;

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::put('api-keys/{id}', [ApiKeyController::class, 'update'])->name('api-keys.update');
    Route::delete('api-keys/{id}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
    Route::post('api-keys/{id}/toggle-status', [ApiKeyController::class, 'toggleStatus'])->name('api-keys.toggle-status');
    Route::post('api-keys/{id}/regenerate-secret', [ApiKeyController::class, 'regenerateSecret'])->name('api-keys.regenerate-secret');
    
    // Domain management routes
    Route::post('api-keys/domains', [ApiKeyDomainController::class, 'store'])->name('api-keys.domains.store');
    Route::put('api-keys/domains/{id}', [ApiKeyDomainController::class, 'update'])->name('api-keys.domains.update');
    Route::delete('api-keys/domains/{id}', [ApiKeyDomainController::class, 'destroy'])->name('api-keys.domains.destroy');
    Route::post('api-keys/domains/test', [ApiKeyDomainController::class, 'test'])->name('api-keys.domains.test');
});
```

### Step 4: Create Your View

Create `resources/views/admin/api-keys/index.blade.php`:

```blade
@extends('layouts.admin')

@section('title', 'API Keys Management')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>API Keys Management</h1>
                    <div class="text-muted">
                        Total: {{ $apiKeys->total() }} keys
                    </div>
                </div>

                {{-- Include the package view --}}
                @include('api-access::manage', [
                    'apiKeys' => $apiKeys,
                    'apiKeysForDropdown' => $apiKeysForDropdown,
                    'currentTab' => $currentTab,
                    'modes' => $modes
                ])
            </div>
        </div>
    </div>
@endsection
```

### Step 5: Protect Your API Routes

Add the middleware to your API routes:

```php
Route::middleware(['api.key'])->prefix('api/v1')->group(function () {
    Route::get('users', [ApiController::class, 'users']);
    Route::post('orders', [ApiController::class, 'createOrder']);
    // ... other protected routes
});
```

## 🔐 API Authentication

Your package supports multiple authentication methods:

### Method 1: Authorization Header (Recommended)
```bash
curl -H "Authorization: Bearer your-api-key-here" \
     -H "X-API-Secret: your-secret-here" \
     https://yourapp.com/api/v1/users
```

### Method 2: Custom Headers
```bash
curl -H "X-API-Key: your-api-key-here" \
     -H "X-API-Secret: your-secret-here" \
     https://yourapp.com/api/v1/users
```

### Method 3: Query Parameters
```bash
curl "https://yourapp.com/api/v1/users?api_key=your-key&api_secret=your-secret"
```

### Method 4: Request Body (POST requests)
```bash
curl -X POST https://yourapp.com/api/v1/orders \
     -H "Content-Type: application/json" \
     -d '{
       "api_key": "your-key-here",
       "api_secret": "your-secret-here",
       "order_data": {...}
     }'
```

## 🎨 Customizing the Interface

### Using Your Own Layout

The package view is designed to work within your existing layouts:

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>API Management</h4>
                </div>
                <div class="card-body">
                    @include('api-access::manage', compact('apiKeys', 'apiKeysForDropdown', 'currentTab', 'modes'))
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

### Customizing Styles

The package includes isolated CSS that won't conflict with your existing Bootstrap. All styles are scoped to `.api-access-wrapper`.

To customize:
1. Publish the views: `php artisan vendor:publish --provider="Yatilabs\ApiAccess\ApiAccessServiceProvider" --tag="views"`
2. Modify the published views in `resources/views/vendor/api-access/`
3. Override CSS by targeting `.api-access-wrapper` classes

### Adding Custom Fields

You can extend the service and add custom fields to the forms by overriding the views:

```blade
{{-- In your custom view --}}
<div class="form-group">
    <label for="custom_field" class="form-label">Custom Field</label>
    <input type="text" class="form-control" name="custom_field" value="{{ old('custom_field') }}">
</div>
```

## 🔧 Advanced Configuration

### Environment Configuration

Set these in your `.env`:

```env
# API Key Settings
API_KEY_LENGTH=32
API_SECRET_LENGTH=64
API_DEFAULT_MODE=test

# Rate Limiting (future feature)
API_RATE_LIMIT_ENABLED=true
API_RATE_LIMIT_STORAGE=database
```

### Service Provider Configuration

You can extend the service provider to add custom configurations:

```php
// In your AppServiceProvider
public function boot()
{
    // Custom API key generation
    ApiKey::creating(function ($apiKey) {
        $apiKey->user_id = auth()->id();
        // Add custom logic here
    });
}
```

## 🌐 Domain Patterns

### Pattern Types

| Pattern | Description | Examples |
|---------|-------------|----------|
| `example.com` | Exact domain | Only `example.com` |
| `*.example.com` | Subdomain wildcard | `api.example.com`, `app.example.com` |
| `api.*.example.com` | Third-level wildcard | `api.v1.example.com`, `api.staging.example.com` |
| `*` | Allow all domains | Any domain (not recommended for live mode) |

### Test vs Live Mode

**Test Mode:**
- Automatically allows `localhost`, `127.0.0.1`, and private IPs
- Perfect for development and testing
- Domain restrictions are optional

**Live Mode:**
- Strict domain validation required
- All requests blocked unless domain is explicitly allowed
- Recommended for production environments

## 🛠️ API Usage in Controllers

### Basic Usage

```php
use Yatilabs\ApiAccess\Traits\HasApiKeyAccess;

class ApiController extends Controller 
{
    use HasApiKeyAccess;
    
    public function users(Request $request)
    {
        $apiKey = $this->getApiKey();
        
        // Your logic here
        return response()->json([
            'users' => User::all(),
            'api_key_info' => [
                'description' => $apiKey->description,
                'mode' => $apiKey->mode,
                'usage_count' => $apiKey->usage_count
            ]
        ]);
    }
}
```

### Advanced Usage with Metadata

```php
public function orders(Request $request)
{
    $apiKey = $this->getApiKey();
    
    // Check if API key has specific permissions
    $metadata = $apiKey->metadata ?? [];
    if (!in_array('orders', $metadata['permissions'] ?? [])) {
        return response()->json(['error' => 'Insufficient permissions'], 403);
    }
    
    return response()->json(Order::all());
}
```

## 🚨 Error Handling

The middleware returns standard HTTP error codes:

### 401 Unauthorized
```json
{
    "error": "Invalid API key",
    "code": "INVALID_API_KEY"
}
```

### 403 Forbidden
```json
{
    "error": "Domain not allowed",
    "code": "DOMAIN_NOT_ALLOWED",
    "domain": "example.com"
}
```

### 401 Expired
```json
{
    "error": "API key has expired",
    "code": "API_KEY_EXPIRED",
    "expired_at": "2024-01-01T00:00:00Z"
}
```

## 📊 Database Schema

The package creates two tables:

### `api_keys` Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `key` - Unique API key (auto-generated)
- `secret_hash` - Bcrypt hash of secret
- `description` - Optional description
- `is_active` - Boolean status
- `expires_at` - Optional expiration date
- `mode` - 'test' or 'live'
- `usage_count` - Request counter
- `last_used_at` - Last usage timestamp
- `created_at` / `updated_at` - Timestamps

### `api_key_domains` Table
- `id` - Primary key
- `api_key_id` - Foreign key to api_keys
- `domain_pattern` - Domain pattern with wildcards
- `created_at` / `updated_at` - Timestamps

## 🧪 Testing

### Testing in Your Application

```php
use Yatilabs\ApiAccess\Models\ApiKey;

class ApiKeyTest extends TestCase
{
    public function test_api_access_with_valid_key()
    {
        $user = User::factory()->create();
        
        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'key' => 'test_key',
            'secret_hash' => bcrypt('test_secret'),
            'description' => 'Test Key',
            'is_active' => true,
            'mode' => 'test'
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer test_key',
            'X-API-Secret' => 'test_secret',
        ])->get('/api/users');
        
        $response->assertStatus(200);
    }
}
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🔗 Links

- [GitHub Repository](https://github.com/yatilabs/laravel-api-access)
- [Packagist](https://packagist.org/packages/yatilabs/laravel-api-access)
- [Issues](https://github.com/yatilabs/laravel-api-access/issues)

---

**Made with ❤️ for the Laravel community**

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
