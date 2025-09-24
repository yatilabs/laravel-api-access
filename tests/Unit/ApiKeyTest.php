<?php

namespace Yatilabs\ApiAccess\Tests\Unit;

use Yatilabs\ApiAccess\Tests\TestCase;
use Yatilabs\ApiAccess\Models\ApiKey;
use Yatilabs\ApiAccess\Models\ApiKeyDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    protected $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->testUser = $this->createUser();
    }
    
    private function createUser()
    {
        return \Illuminate\Foundation\Auth\User::forceCreate([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
    }

    /** @test */
    public function it_can_create_an_api_key()
    {
        $apiKey = ApiKey::create([
            'user_id' => $this->testUser->id,
            'description' => 'Test API Key',
        ]);

        $this->assertInstanceOf(ApiKey::class, $apiKey);
        $this->assertNotNull($apiKey->key);
        $this->assertTrue($apiKey->isActive());
    }

    /** @test */
    public function it_generates_unique_api_key_on_creation()
    {
        $apiKey1 = ApiKey::create(['user_id' => $this->testUser->id]);
        $apiKey2 = ApiKey::create(['user_id' => $this->testUser->id]);

        $this->assertNotEquals($apiKey1->key, $apiKey2->key);
        $this->assertStringStartsWith('ak_', $apiKey1->key);
        $this->assertStringStartsWith('ak_', $apiKey2->key);
    }

    /** @test */
    public function it_can_check_if_api_key_is_active()
    {
        $activeKey = ApiKey::create(['user_id' => $this->testUser->id, 'is_active' => true]);
        $inactiveKey = ApiKey::create(['user_id' => $this->testUser->id, 'is_active' => false]);

        $this->assertTrue($activeKey->isActive());
        $this->assertFalse($inactiveKey->isActive());
    }

    /** @test */
    public function it_can_check_if_api_key_has_expired()
    {
        $expiredKey = ApiKey::create([
            'user_id' => $this->testUser->id,
            'expires_at' => now()->subDay(),
        ]);

        $validKey = ApiKey::create([
            'user_id' => $this->testUser->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($expiredKey->hasExpired());
        $this->assertFalse($validKey->hasExpired());
        $this->assertFalse($expiredKey->isActive()); // Expired keys are not active
        $this->assertTrue($validKey->isActive());
    }

    /** @test */
    public function it_can_increment_usage_count()
    {
        $apiKey = ApiKey::create(['user_id' => $this->testUser->id]);

        $this->assertEquals(0, $apiKey->usage_count);
        $this->assertNull($apiKey->last_used_at);

        $apiKey->incrementUsage();
        $apiKey->refresh();

        $this->assertEquals(1, $apiKey->usage_count);
        $this->assertNotNull($apiKey->last_used_at);
    }

    /** @test */
    public function it_has_domains_relationship()
    {
        $apiKey = ApiKey::create(['user_id' => $this->testUser->id]);
        
        $domain = ApiKeyDomain::create([
            'api_key_id' => $apiKey->id,
            'domain_pattern' => 'example.com',
        ]);

        $this->assertTrue($apiKey->domains->contains($domain));
        $this->assertEquals($apiKey->id, $domain->apiKey->id);
    }

    /** @test */
    public function it_can_check_domain_restrictions()
    {
        $apiKey = ApiKey::create(['user_id' => $this->testUser->id]);

        // No domain restrictions - should allow all
        $this->assertTrue($apiKey->isDomainAllowed('any-domain.com'));

        // Add domain restriction
        ApiKeyDomain::create([
            'api_key_id' => $apiKey->id,
            'domain_pattern' => 'example.com',
        ]);

        $apiKey->refresh();

        $this->assertTrue($apiKey->isDomainAllowed('example.com'));
        $this->assertFalse($apiKey->isDomainAllowed('other.com'));
    }
}