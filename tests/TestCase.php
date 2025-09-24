<?php

namespace Yatilabs\ApiAccess\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Yatilabs\ApiAccess\ApiAccessServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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
}