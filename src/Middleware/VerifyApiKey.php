<?php

namespace Yatilabs\ApiAccess\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yatilabs\ApiAccess\Models\ApiKey;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Extract API key and secret from request
        $apiKey = $this->extractApiKey($request);
        $secret = $this->extractSecret($request);

        if (!$apiKey) {
            return $this->unauthorizedResponse('API key is required');
        }

        // Find the API key in database
        $apiKeyModel = ApiKey::findByKey($apiKey);

        if (!$apiKeyModel) {
            return $this->unauthorizedResponse('Invalid API key');
        }

        // Check if API key is active
        if (!$apiKeyModel->isActive()) {
            return $this->unauthorizedResponse('API key is inactive or expired');
        }

        // Check if API key has expired
        if ($apiKeyModel->hasExpired()) {
            return $this->unauthorizedResponse('API key has expired');
        }

        // Verify secret if provided
        if (!$apiKeyModel->verifySecret($secret)) {
            return $this->unauthorizedResponse('Invalid API key secret');
        }

        // Check domain restrictions
        if (!$this->isDomainAllowed($request, $apiKeyModel)) {
            return $this->unauthorizedResponse('Domain not allowed for this API key');
        }

        // Increment usage count
        $apiKeyModel->incrementUsage();

        // Add API key model to request for use in controllers
        $request->attributes->set('api_key_model', $apiKeyModel);

        return $next($request);
    }

    /**
     * Extract API key from request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function extractApiKey(Request $request): ?string
    {
        // Check Authorization header (Bearer token)
        if ($request->bearerToken()) {
            return $request->bearerToken();
        }

        // Check X-API-Key header
        if ($request->header('X-API-Key')) {
            return $request->header('X-API-Key');
        }

        // Check query parameter
        if ($request->query('api_key')) {
            return $request->query('api_key');
        }

        // Check request body
        if ($request->input('api_key')) {
            return $request->input('api_key');
        }

        return null;
    }

    /**
     * Extract API secret from request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function extractSecret(Request $request): ?string
    {
        // Check X-API-Secret header
        if ($request->header('X-API-Secret')) {
            return $request->header('X-API-Secret');
        }

        // Check query parameter
        if ($request->query('api_secret')) {
            return $request->query('api_secret');
        }

        // Check request body
        if ($request->input('api_secret')) {
            return $request->input('api_secret');
        }

        return null;
    }

    /**
     * Check if the current domain is allowed for the API key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Yatilabs\ApiAccess\Models\ApiKey  $apiKey
     * @return bool
     */
    protected function isDomainAllowed(Request $request, ApiKey $apiKey): bool
    {
        $domain = $this->extractDomain($request);

        // For test mode, allow localhost and 127.0.0.1
        if ($apiKey->mode === 'test' && $this->isLocalhost($domain)) {
            return true;
        }

        // For live mode or test mode with non-localhost domains, check domain restrictions
        return $apiKey->isDomainAllowed($domain);
    }

    /**
     * Extract domain from request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function extractDomain(Request $request): string
    {
        $host = $request->getHost();
        
        // Remove port if present
        if (strpos($host, ':') !== false) {
            $host = explode(':', $host)[0];
        }

        return $host;
    }

    /**
     * Check if domain is localhost or 127.0.0.1.
     *
     * @param  string  $domain
     * @return bool
     */
    protected function isLocalhost(string $domain): bool
    {
        $localhostPatterns = [
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0',
        ];

        return in_array(strtolower($domain), $localhostPatterns);
    }

    /**
     * Return an unauthorized response.
     *
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
        ], 401);
    }
}