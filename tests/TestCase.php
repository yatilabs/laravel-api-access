<?php

namespace Yatilabs\ApiAccess\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Yatilabs\ApiAccess\ApiAccessServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create users table first
        $this->setUpUsersTable();
        
        // Run the package migrations
        $this->artisan('migrate')->run();
    }

    protected function getPackageProviders($app)
    {
        return [
            ApiAccessServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup testing environment
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Set up the users table for testing.
     */
    protected function setUpUsersTable()
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
}