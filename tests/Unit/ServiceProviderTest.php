<?php

namespace Yatilabs\ApiAccess\Tests\Unit;

use Yatilabs\ApiAccess\Tests\TestCase;
use Yatilabs\ApiAccess\ApiAccessServiceProvider;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function it_can_register_the_service_provider()
    {
        $this->assertInstanceOf(ApiAccessServiceProvider::class, $this->app->getProvider(ApiAccessServiceProvider::class));
    }

    /** @test */
    public function it_publishes_config_file()
    {
        $this->artisan('vendor:publish', [
            '--provider' => 'Yatilabs\ApiAccess\ApiAccessServiceProvider',
            '--tag' => 'config'
        ]);

        $this->assertTrue(file_exists(config_path('api-access.php')));
    }
}