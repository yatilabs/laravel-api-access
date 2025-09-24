# Laravel API Access Package

A Laravel package that provides comprehensive API key management with domain restrictions, usage tracking, and secure authentication for your Laravel applications.

## Features

- **API Key Management**: Generate, manage, and validate unique API keys
- **Domain Restrictions**: Restrict API key usage to specific domains with wildcard support
- **Usage Tracking**: Track API key usage count and last used timestamps
- **Secure Authentication**: Optional secret-based authentication with hashing
- **Expiration Control**: Set expiration dates for API keys
- **Live/Test Modes**: Separate API keys for different environments
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
