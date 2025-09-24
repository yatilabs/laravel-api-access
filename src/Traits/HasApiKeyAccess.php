<?php

namespace Yatilabs\ApiAccess\Traits;

use Yatilabs\ApiAccess\Models\ApiKey;

trait HasApiKeyAccess
{
    /**
     * Get the authenticated API key from the request.
     *
     * @return \Yatilabs\ApiAccess\Models\ApiKey|null
     */
    protected function getApiKey(): ?ApiKey
    {
        return request()->attributes->get('api_key_model');
    }

    /**
     * Get the user ID associated with the authenticated API key.
     *
     * @return int|null
     */
    protected function getApiKeyUserId(): ?int
    {
        $apiKey = $this->getApiKey();
        return $apiKey ? $apiKey->user_id : null;
    }

    /**
     * Check if the authenticated API key is in test mode.
     *
     * @return bool
     */
    protected function isTestMode(): bool
    {
        $apiKey = $this->getApiKey();
        return $apiKey && $apiKey->mode === 'test';
    }

    /**
     * Check if the authenticated API key is in live mode.
     *
     * @return bool
     */
    protected function isLiveMode(): bool
    {
        $apiKey = $this->getApiKey();
        return $apiKey && $apiKey->mode === 'live';
    }

    /**
     * Get the usage count of the authenticated API key.
     *
     * @return int
     */
    protected function getApiKeyUsageCount(): int
    {
        $apiKey = $this->getApiKey();
        return $apiKey ? $apiKey->usage_count : 0;
    }
}