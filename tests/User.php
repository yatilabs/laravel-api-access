<?php

namespace Yatilabs\ApiAccess\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Yatilabs\ApiAccess\Models\ApiKey;

class User extends Model
{
    protected $fillable = ['name', 'email'];

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }
}