<?php

namespace Yatilabs\ApiAccess;

use Illuminate\Support\ServiceProvider;

class ApiAccessServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Merge the package configuration with the application's config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-access.php',
            'api-access'
        );

        // Register any service container bindings
        $this->app->singleton('api-access', function ($app) {
            return new \stdClass(); // Replace with your main service class
        });
    }

    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        // Publish the config file
        $this->publishes([
            __DIR__ . '/../config/api-access.php' => config_path('api-access.php'),
        ], 'config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        // Publish assets (CSS)
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/yatilabs/api-access'),
        ], 'assets');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/api-access'),
        ], 'views');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'api-access');

        // Load migrations when running in console
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Register middleware
        $this->app['router']->aliasMiddleware('api.key', \Yatilabs\ApiAccess\Middleware\VerifyApiKey::class);

        // Register the service
        $this->app->singleton(\Yatilabs\ApiAccess\Services\ApiAccessService::class);

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
