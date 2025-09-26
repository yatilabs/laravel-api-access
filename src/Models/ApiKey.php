<?php

namespace Yatilabs\ApiAccess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApiKey extends Model
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     */
    protected $table = 'api_keys';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'secret',
        'user_id',
        'description',
        'is_active',
        'expires_at',
        'last_used_at',
        'usage_count',
        'mode',
        'owner_type',
        'owner_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'is_active' => true,
        'usage_count' => 0,
        'mode' => 'live',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($apiKey) {
            if (empty($apiKey->key)) {
                $apiKey->key = static::generateApiKey();
            }
        });
    }

    /**
     * Get the user that owns the API key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Get the domains associated with the API key.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(ApiKeyDomain::class);
    }

    /**
     * Get the owner model (polymorphic relationship).
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the owner display name based on configuration.
     */
    public function getOwnerDisplayNameAttribute(): ?string
    {
        $config = config('api-access.model_owner');
        
        if (!$config['enabled'] || !$this->owner) {
            return null;
        }

        $titleColumn = $config['title_column'] ?? 'name';
        return $this->owner->{$titleColumn} ?? null;
    }

    /**
     * Get the owner label based on configuration.
     */
    public function getOwnerLabelAttribute(): ?string
    {
        $config = config('api-access.model_owner');
        
        if (!$config['enabled'] || !$this->owner) {
            return null;
        }

        return $config['label'] ?? 'Owner';
    }

    /**
     * Check if model owner functionality is enabled.
     */
    public static function isOwnerEnabled(): bool
    {
        return config('api-access.model_owner.enabled', false);
    }

    /**
     * Check if owner selection is required.
     */
    public static function isOwnerRequired(): bool
    {
        return config('api-access.model_owner.required', false);
    }

    /**
     * Get available owners for selection.
     */
    public static function getAvailableOwners()
    {
        $config = config('api-access.model_owner');
        
        if (!$config['enabled']) {
            return collect();
        }

        $modelClass = $config['model'];
        if (!class_exists($modelClass)) {
            return collect();
        }

        $query = $modelClass::query();
        
        // Apply constraints if configured
        if (isset($config['constraints']) && is_array($config['constraints'])) {
            foreach ($config['constraints'] as $column => $value) {
                $query->where($column, $value);
            }
        }

        $idColumn = $config['id_column'] ?? 'id';
        $titleColumn = $config['title_column'] ?? 'name';
        $additionalColumns = $config['additional_columns'] ?? [];

        $selectColumns = [$idColumn, $titleColumn];
        if (!empty($additionalColumns)) {
            $selectColumns = array_merge($selectColumns, $additionalColumns);
        }

        return $query->select($selectColumns)->orderBy($titleColumn)->get();
    }

    /**
     * Check if the API key is active.
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the API key has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Increment the usage count and update last used timestamp.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check if a domain is allowed for this API key.
     */
    public function isDomainAllowed(string $domain): bool
    {
        if ($this->domains()->count() === 0) {
            return true; // No domain restrictions
        }

        return $this->domains()
            ->where(function ($query) use ($domain) {
                $query->where('domain_pattern', $domain)
                      ->orWhere('domain_pattern', '*')
                      ->orWhere(function ($q) use ($domain) {
                          $q->whereRaw('? LIKE domain_pattern', [$domain]);
                      });
            })
            ->exists();
    }

    /**
     * Verify the secret if provided.
     */
    public function verifySecret(?string $secret): bool
    {
        if (!$this->secret) {
            return true; // No secret required
        }

        if (!$secret) {
            return false; // Secret required but not provided
        }

        return Hash::check($secret, $this->secret);
    }

    /**
     * Set the secret attribute.
     */
    public function setSecretAttribute(?string $value): void
    {
        $this->attributes['secret'] = $value ? Hash::make($value) : null;
    }

    /**
     * Generate a unique API key.
     */
    public static function generateApiKey(): string
    {
        do {
            $key = 'ak_' . Str::random(60);
        } while (static::where('key', $key)->exists());

        return $key;
    }

    /**
     * Find an API key by its key value.
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    /**
     * Scope a query to only include active API keys.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope a query to only include live mode API keys.
     */
    public function scopeLive($query)
    {
        return $query->where('mode', 'live');
    }

    /**
     * Scope a query to only include test mode API keys.
     */
    public function scopeTest($query)
    {
        return $query->where('mode', 'test');
    }
}