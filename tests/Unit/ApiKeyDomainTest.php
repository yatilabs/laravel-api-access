<?php

namespace Yatilabs\ApiAccess\Tests\Unit;

use Yatilabs\ApiAccess\Tests\TestCase;
use Yatilabs\ApiAccess\Models\ApiKey;
use Yatilabs\ApiAccess\Models\ApiKeyDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiKeyDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->testUser = \Illuminate\Foundation\Auth\User::forceCreate([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
    }

    /** @test */
    public function it_can_create_an_api_key_domain()
    {
        $apiKey = ApiKey::create(['user_id' => $this->testUser->id]);
        
        $domain = ApiKeyDomain::create([
            'api_key_id' => $apiKey->id,
            'domain_pattern' => 'example.com',
        ]);

        $this->assertInstanceOf(ApiKeyDomain::class, $domain);
        $this->assertEquals('example.com', $domain->domain_pattern);
        $this->assertEquals($apiKey->id, $domain->api_key_id);
    }

    /** @test */
    public function it_belongs_to_an_api_key()
    {
        $apiKey = ApiKey::create(['user_id' => $this->testUser->id]);
        
        $domain = ApiKeyDomain::create([
            'api_key_id' => $apiKey->id,
            'domain_pattern' => 'example.com',
        ]);

        $this->assertEquals($apiKey->id, $domain->apiKey->id);
    }

    /** @test */
    public function it_can_match_exact_domains()
    {
        $domain = new ApiKeyDomain(['domain_pattern' => 'example.com']);

        $this->assertTrue($domain->matches('example.com'));
        $this->assertFalse($domain->matches('other.com'));
        $this->assertFalse($domain->matches('sub.example.com'));
    }

    /** @test */
    public function it_can_match_wildcard_domains()
    {
        $wildcardDomain = new ApiKeyDomain(['domain_pattern' => '*']);

        $this->assertTrue($wildcardDomain->matches('any-domain.com'));
        $this->assertTrue($wildcardDomain->matches('example.org'));
    }

    /** @test */
    public function it_can_match_subdomain_wildcards()
    {
        $subdomainWildcard = new ApiKeyDomain(['domain_pattern' => '*.example.com']);

        $this->assertTrue($subdomainWildcard->matches('sub.example.com'));
        $this->assertTrue($subdomainWildcard->matches('api.example.com'));
        $this->assertFalse($subdomainWildcard->matches('example.com'));
        $this->assertFalse($subdomainWildcard->matches('other.com'));
    }

    /** @test */
    public function it_can_create_domain_for_api_key()
    {
        $apiKey = ApiKey::create(['user_id' => $this->testUser->id]);
        
        $domain = ApiKeyDomain::createForApiKey($apiKey->id, 'test.com');

        $this->assertEquals($apiKey->id, $domain->api_key_id);
        $this->assertEquals('test.com', $domain->domain_pattern);
    }

    /** @test */
    public function it_can_get_domains_for_api_key()
    {
        $apiKey = ApiKey::create(['user_id' => $this->testUser->id]);
        
        ApiKeyDomain::create(['api_key_id' => $apiKey->id, 'domain_pattern' => 'domain1.com']);
        ApiKeyDomain::create(['api_key_id' => $apiKey->id, 'domain_pattern' => 'domain2.com']);
        
        $otherApiKey = ApiKey::create(['user_id' => $this->testUser->id]);
        ApiKeyDomain::create(['api_key_id' => $otherApiKey->id, 'domain_pattern' => 'other.com']);

        $domains = ApiKeyDomain::forApiKey($apiKey->id);

        $this->assertCount(2, $domains);
        $this->assertTrue($domains->pluck('domain_pattern')->contains('domain1.com'));
        $this->assertTrue($domains->pluck('domain_pattern')->contains('domain2.com'));
        $this->assertFalse($domains->pluck('domain_pattern')->contains('other.com'));
    }
}