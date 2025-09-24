# Laravel API Access Package

A Laravel package that provides a unified interface for accessing third-party APIs with built-in features like rate limiting, caching, retry mechanisms, and comprehensive logging.

## Features

- **Unified API Interface**: Consistent way to interact with different third-party APIs
- **Rate Limiting**: Built-in rate limiting to prevent hitting API limits
- **Caching**: Configurable response caching to improve performance
- **Retry Mechanism**: Automatic retry with exponential backoff for failed requests
- **Comprehensive Logging**: Request and response logging for debugging
- **Configuration Management**: Easy-to-use configuration system
- **Laravel Integration**: Seamless integration with Laravel service container

## Installation

You can install the package via Composer:

```bash
composer require yatilabs/api-access
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Yatilabs\ApiAccess\ApiAccessServiceProvider" --tag="config"
```

This will publish the `config/api-access.php` configuration file where you can customize:

- Default timeout and retry settings
- Rate limiting configuration
- Caching options
- Logging preferences

## Usage

Coming soon...

## Testing

Run the tests with:

```bash
composer test
```

## Development

This package uses the following development tools:

- **PHPUnit** for testing
- **Orchestra Testbench** for Laravel package testing
- **PHPStan** for static analysis
- **PHP CodeSniffer** for code style checking

To install development dependencies:

```bash
composer install
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email daman.mokha@yatilabs.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Daman Mokha](https://github.com/damanmokha)
- [All Contributors](../../contributors)
