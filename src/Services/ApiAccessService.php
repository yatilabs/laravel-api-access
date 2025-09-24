<?php

namespace Yatilabs\ApiAccess\Services;

use Yatilabs\ApiAccess\Models\ApiKey;
use Yatilabs\ApiAccess\Models\ApiKeyDomain;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ApiAccessService
{
    /**
     * Get paginated API keys with their domains for the current user
     */
    public function getApiKeys(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        return ApiKey::with(['domains'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get API key by ID for the current user
     */
    public function getApiKey($id)
    {
        return ApiKey::with(['domains'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);
    }

    /**
     * Create new API key
     */
    public function createApiKey(array $data)
    {
        $validator = $this->validateApiKeyData($data);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        DB::beginTransaction();
        
        try {
            // Generate key and secret
            $key = $this->generateApiKey();
            $secret = $this->generateApiSecret();
            
            $apiKey = ApiKey::create([
                'user_id' => auth()->id(),
                'key' => $key,
                'secret' => $secret,
                'description' => $data['description'] ?? null,
                'is_active' => isset($data['is_active']) && $data['is_active'] == '1',
                'expires_at' => !empty($data['expires_at']) ? $data['expires_at'] : null,
                'mode' => $data['mode'] ?? 'test',
            ]);

            DB::commit();

            // Return the API key with the plain secret (only time it will be visible)
            $apiKey->plain_secret = $secret;
            
            return $apiKey;
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update API key
     */
    public function updateApiKey($id, array $data)
    {
        $apiKey = $this->getApiKey($id);
        
        $validator = $this->validateApiKeyData($data, $id);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $apiKey->update([
            'description' => $data['description'] ?? null,
            'is_active' => isset($data['is_active']) && $data['is_active'] == '1',
            'expires_at' => !empty($data['expires_at']) ? $data['expires_at'] : null,
            'mode' => $data['mode'] ?? 'test',
        ]);

        return $apiKey->fresh();
    }

    /**
     * Delete API key
     */
    public function deleteApiKey($id)
    {
        $apiKey = $this->getApiKey($id);
        $apiKey->delete();
        
        return true;
    }

    /**
     * Regenerate API key secret
     */
    public function regenerateSecret($id)
    {
        $apiKey = $this->getApiKey($id);
        
        $newSecret = $this->generateApiSecret();
        $apiKey->update([
            'secret' => $newSecret
        ]);
        
        $apiKey->plain_secret = $newSecret;
        return $apiKey;
    }

    /**
     * Toggle API key active status
     */
    public function toggleStatus($id)
    {
        $apiKey = $this->getApiKey($id);
        $apiKey->update(['is_active' => !$apiKey->is_active]);
        
        return $apiKey->fresh();
    }

    /**
     * Create domain restriction
     */
    public function createDomain(array $data)
    {
        $validator = $this->validateDomainData($data);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Verify the API key belongs to the current user
        $apiKey = $this->getApiKey($data['api_key_id']);
        
        $domain = ApiKeyDomain::create([
            'api_key_id' => $data['api_key_id'],
            'domain_pattern' => strtolower(trim($data['domain_pattern']))
        ]);

        return $domain;
    }

    /**
     * Update domain restriction
     */
    public function updateDomain($id, array $data)
    {
        $domain = ApiKeyDomain::whereHas('apiKey', function($query) {
            $query->where('user_id', auth()->id());
        })->findOrFail($id);
        
        $validator = $this->validateDomainData($data, $id);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $domain->update([
            'domain_pattern' => strtolower(trim($data['domain_pattern']))
        ]);

        return $domain->fresh();
    }

    /**
     * Delete domain restriction
     */
    public function deleteDomain($id)
    {
        $domain = ApiKeyDomain::whereHas('apiKey', function($query) {
            $query->where('user_id', auth()->id());
        })->findOrFail($id);
        
        $domain->delete();
        return true;
    }

    /**
     * Get all API keys for domain dropdowns
     */
    public function getApiKeysForDropdown()
    {
        return ApiKey::where('user_id', auth()->id())
            ->orderBy('description')
            ->get(['id', 'key', 'description']);
    }

    /**
     * Test domain matching
     */
    public function testDomainMatch($apiKeyId, $domain)
    {
        $apiKey = $this->getApiKey($apiKeyId);
        
        $result = [
            'domain' => $domain,
            'api_key_name' => $apiKey->description ?: 'Unnamed Key',
            'mode' => $apiKey->mode,
            'allowed' => false,
            'matching_pattern' => null,
            'reason' => null
        ];

        // Test mode allows localhost
        if ($apiKey->mode === 'test' && $this->isLocalhost($domain)) {
            $result['allowed'] = true;
            $result['matching_pattern'] = 'localhost (test mode)';
            $result['reason'] = 'Test mode automatically allows localhost domains';
            return $result;
        }

        // Check domain patterns
        foreach ($apiKey->domains as $domainRestriction) {
            if ($this->matchesDomainPattern($domain, $domainRestriction->domain_pattern)) {
                $result['allowed'] = true;
                $result['matching_pattern'] = $domainRestriction->domain_pattern;
                $result['reason'] = "Matches pattern: {$domainRestriction->domain_pattern}";
                return $result;
            }
        }

        // No match found
        if ($apiKey->domains->count() === 0) {
            $result['reason'] = $apiKey->mode === 'live' 
                ? 'No domain restrictions set - blocked in live mode'
                : 'No domain restrictions set - only localhost allowed in test mode';
        } else {
            $result['reason'] = 'Domain does not match any configured patterns';
        }

        return $result;
    }

    /**
     * Validate API key data
     */
    protected function validateApiKeyData(array $data, $ignoreId = null)
    {
        $rules = [
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date|after:now',
            'mode' => 'required|in:test,live'
        ];

        $messages = [
            'mode.required' => 'Please select a mode',
            'mode.in' => 'Mode must be either test or live',
            'expires_at.after' => 'Expiration date must be in the future'
        ];

        return Validator::make($data, $rules, $messages);
    }

    /**
     * Validate domain data
     */
    protected function validateDomainData(array $data, $ignoreId = null)
    {
        $rules = [
            'api_key_id' => 'required|exists:api_keys,id',
            'domain_pattern' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($data, $ignoreId) {
                    $this->validateDomainPattern($value, $fail);
                    $this->validateUniqueDomainForApiKey($value, $data['api_key_id'], $ignoreId, $fail);
                }
            ]
        ];

        $messages = [
            'api_key_id.required' => 'Please select an API key',
            'api_key_id.exists' => 'Selected API key does not exist',
            'domain_pattern.required' => 'Domain pattern is required',
            'domain_pattern.max' => 'Domain pattern cannot exceed 255 characters'
        ];

        return Validator::make($data, $rules, $messages);
    }

    /**
     * Validate domain pattern format
     */
    protected function validateDomainPattern($pattern, $fail)
    {
        $normalized = strtolower(trim($pattern));
        
        if (empty($normalized)) {
            $fail('Domain pattern cannot be empty');
            return;
        }

        // Check for invalid characters
        if (!preg_match('/^[a-zA-Z0-9\*\.\-]+$/', $normalized)) {
            $fail('Domain pattern can only contain letters, numbers, dots, hyphens, and asterisks');
            return;
        }

        // Check for consecutive dots
        if (strpos($normalized, '..') !== false) {
            $fail('Domain pattern cannot contain consecutive dots');
            return;
        }

        // Basic wildcard validation
        if (str_contains($normalized, '*') && $normalized !== '*') {
            if (preg_match('/\*[^.]/', $normalized) || preg_match('/[^.]\*/', $normalized)) {
                $fail('Wildcards must be separated by dots (e.g., *.example.com)');
                return;
            }
        }
    }

    /**
     * Check uniqueness for API key
     */
    protected function validateUniqueDomainForApiKey($pattern, $apiKeyId, $ignoreId, $fail)
    {
        $normalized = strtolower(trim($pattern));
        
        $query = ApiKeyDomain::where('api_key_id', $apiKeyId)
            ->where('domain_pattern', $normalized);
            
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            $fail('This domain pattern already exists for the selected API key');
        }
    }

    /**
     * Generate unique API key
     */
    protected function generateApiKey()
    {
        do {
            $key = 'ak_' . bin2hex(random_bytes(16));
        } while (ApiKey::where('key', $key)->exists());
        
        return $key;
    }

    /**
     * Generate API secret
     */
    protected function generateApiSecret()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Check if domain is localhost
     */
    protected function isLocalhost($domain)
    {
        $localhost_patterns = [
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0'
        ];

        return in_array(strtolower($domain), $localhost_patterns) || 
               preg_match('/^127\.\d+\.\d+\.\d+$/', $domain) ||
               preg_match('/^192\.168\.\d+\.\d+$/', $domain) ||
               preg_match('/^10\.\d+\.\d+\.\d+$/', $domain);
    }

    /**
     * Check if domain matches pattern
     */
    protected function matchesDomainPattern($domain, $pattern)
    {
        $domain = strtolower($domain);
        $pattern = strtolower($pattern);

        if ($domain === $pattern) {
            return true;
        }

        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace(['\*', '\.'], ['.*', '\.'], preg_quote($pattern, '/')) . '$/';
            return preg_match($regex, $domain);
        }

        return false;
    }

    /**
     * Get view data for management interface
     */
    public function getViewData(Request $request)
    {
        $tab = $request->get('tab', 'api-keys');
        $apiKeys = $this->getApiKeys($request);
        $apiKeysForDropdown = $this->getApiKeysForDropdown();
        
        return [
            'apiKeys' => $apiKeys,
            'apiKeysForDropdown' => $apiKeysForDropdown,
            'currentTab' => $tab,
            'modes' => [
                'test' => 'Test Mode (allows localhost)',
                'live' => 'Live Mode (strict domain validation)'
            ]
        ];
    }
}