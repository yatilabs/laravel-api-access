<?php

namespace Yatilabs\ApiAccess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKeyDomain extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'api_key_domains';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'api_key_id',
        'domain_pattern',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'api_key_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the API key that owns the domain.
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Check if a domain matches the pattern.
     */
    public function matches(string $domain): bool
    {
        // Handle wildcard patterns
        if ($this->domain_pattern === '*') {
            return true;
        }

        // Handle subdomain wildcards (e.g., *.example.com)
        if (str_contains($this->domain_pattern, '*')) {
            // Convert wildcard pattern to regex pattern
            $pattern = str_replace(['.', '*'], ['\.', '.*'], $this->domain_pattern);
            return (bool) preg_match('/^' . $pattern . '$/', $domain);
        }

        // Exact match
        return $this->domain_pattern === $domain;
    }

    /**
     * Scope a query to match a specific domain.
     */
    public function scopeForDomain($query, string $domain)
    {
        return $query->where(function ($q) use ($domain) {
            $q->where('domain_pattern', $domain)
              ->orWhere('domain_pattern', '*')
              ->orWhere(function ($subQuery) use ($domain) {
                  $subQuery->where('domain_pattern', 'LIKE', '%*%')
                           ->whereRaw('? REGEXP REPLACE(domain_pattern, \'\\\\*\', \'.*\')', [$domain]);
              });
        });
    }

    /**
     * Create a domain pattern for an API key.
     */
    public static function createForApiKey(int $apiKeyId, string $domainPattern): self
    {
        return static::create([
            'api_key_id' => $apiKeyId,
            'domain_pattern' => $domainPattern,
        ]);
    }

    /**
     * Get all domains for a specific API key.
     */
    public static function forApiKey(int $apiKeyId)
    {
        return static::where('api_key_id', $apiKeyId)->get();
    }
}