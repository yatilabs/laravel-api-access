<?php

namespace Yatilabs\ApiAccess\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yatilabs\ApiAccess\Models\ApiKey;
use Yatilabs\ApiAccess\Models\ApiLog;

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
        $startTime = microtime(true);
        $requestId = Str::uuid()->toString();
        $apiKeyModel = null;
        $isAuthenticated = false;
        $errorMessage = null;
        
        try {
            // Extract API key and secret from request
            $apiKey = $this->extractApiKey($request);
            $secret = $this->extractSecret($request);

            if (!$apiKey) {
                $errorMessage = 'API key is required';
                return $this->handleUnauthorized($request, $errorMessage, $startTime, $requestId, $apiKeyModel, $isAuthenticated);
            }

            // Find the API key in database
            $apiKeyModel = ApiKey::findByKey($apiKey);

            if (!$apiKeyModel) {
                $errorMessage = 'Invalid API key';
                return $this->handleUnauthorized($request, $errorMessage, $startTime, $requestId, $apiKeyModel, $isAuthenticated);
            }

            // Check if API key is active
            if (!$apiKeyModel->isActive()) {
                $errorMessage = 'API key is inactive or expired';
                return $this->handleUnauthorized($request, $errorMessage, $startTime, $requestId, $apiKeyModel, $isAuthenticated);
            }

            // Check if API key has expired
            if ($apiKeyModel->hasExpired()) {
                $errorMessage = 'API key has expired';
                return $this->handleUnauthorized($request, $errorMessage, $startTime, $requestId, $apiKeyModel, $isAuthenticated);
            }

            // Verify secret if provided
            if (!$apiKeyModel->verifySecret($secret)) {
                $errorMessage = 'Invalid API key secret';
                return $this->handleUnauthorized($request, $errorMessage, $startTime, $requestId, $apiKeyModel, $isAuthenticated);
            }

            // Check domain restrictions
            if (!$this->isDomainAllowed($request, $apiKeyModel)) {
                $errorMessage = 'Domain not allowed for this API key';
                return $this->handleUnauthorized($request, $errorMessage, $startTime, $requestId, $apiKeyModel, $isAuthenticated);
            }

            // Authentication successful
            $isAuthenticated = true;
            
            // Increment usage count
            $apiKeyModel->incrementUsage();

            // Add API key model to request for use in controllers
            $request->attributes->set('api_key_model', $apiKeyModel);
            $request->attributes->set('request_id', $requestId);

            $response = $next($request);
            
            // Log successful request
            $this->logRequest($request, $response, $startTime, $requestId, $apiKeyModel, $isAuthenticated);
            
            return $response;
            
        } catch (\Exception $e) {
            $errorMessage = 'Internal server error: ' . $e->getMessage();
            
            $response = response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An unexpected error occurred',
                'request_id' => $requestId,
            ], 500);
            
            // Log error
            $this->logRequest($request, $response, $startTime, $requestId, $apiKeyModel, $isAuthenticated, $errorMessage, $e->getTraceAsString());
            
            return $response;
        }
    }

    /**
     * Handle unauthorized response and log it.
     */
    protected function handleUnauthorized(Request $request, string $message, float $startTime, string $requestId, ?ApiKey $apiKeyModel, bool $isAuthenticated): JsonResponse
    {
        $response = $this->unauthorizedResponse($message, $requestId);
        
        // Log unauthorized request
        $this->logRequest($request, $response, $startTime, $requestId, $apiKeyModel, $isAuthenticated, $message);
        
        return $response;
    }

    /**
     * Log API request and response.
     */
    protected function logRequest(Request $request, $response, float $startTime, string $requestId, ?ApiKey $apiKeyModel, bool $isAuthenticated, ?string $errorMessage = null, ?string $errorTrace = null): void
    {
        // Check if logging is enabled
        if (!config('api-access.logging.enabled', true)) {
            return;
        }

        $executionTime = round((microtime(true) - $startTime) * 1000);
        $logConfig = config('api-access.logging', []);

        // Prepare log data
        $logData = [
            'api_key_id' => $apiKeyModel ? $apiKeyModel->id : null,
            'ip_address' => $this->getClientIpAddress($request),
            'user_agent' => $logConfig['log_user_agent'] ?? true ? $request->userAgent() : null,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route' => $request->route() ? $request->route()->getName() : null,
            'response_status' => $response->getStatusCode(),
            'execution_time_ms' => $logConfig['log_execution_time'] ?? true ? $executionTime : null,
            'error_message' => $errorMessage,
            'error_trace' => $errorTrace,
            'api_key_hash' => $apiKeyModel ? hash('sha256', $apiKeyModel->key) : null,
            'is_authenticated' => $isAuthenticated,
            'request_id' => $requestId,
        ];

        // Add request headers
        if ($logConfig['log_headers'] ?? true) {
            $logData['request_headers'] = $this->sanitizeHeaders($request->headers->all());
        }

        // Add query parameters  
        if ($logConfig['log_query_parameters'] ?? true) {
            $logData['query_parameters'] = $request->query->all();
        }

        // Add request body
        if ($logConfig['log_request_body'] ?? true) {
            $requestBody = $this->getRequestBody($request);
            if ($requestBody) {
                $logData['request_body'] = $this->truncateContent($this->sanitizeRequestBody($requestBody), $logConfig['max_body_size'] ?? 10240);
            }
        }

        // Add response data
        if ($logConfig['log_responses'] ?? true) {
            if ($logConfig['log_headers'] ?? true) {
                $logData['response_headers'] = $this->sanitizeHeaders($response->headers->all());
            }
            
            if ($logConfig['log_response_body'] ?? true) {
                $responseContent = $response->getContent();
                if ($responseContent) {
                    $logData['response_body'] = $this->truncateContent($responseContent, $logConfig['max_body_size'] ?? 10240);
                }
            }
        }

        // Create log entry
        try {
            ApiLog::create($logData);
        } catch (\Exception $e) {
            // Silently fail logging to prevent breaking the application
            Log::error('Failed to create API log entry: ' . $e->getMessage(), [
                'request_id' => $requestId,
                'url' => $request->fullUrl(),
            ]);
        }
    }

    /**
     * Get client IP address with proxy support.
     */
    protected function getClientIpAddress(Request $request): string
    {
        // Check for IP from shared internet
        if (!empty($request->server('HTTP_CLIENT_IP'))) {
            return $request->server('HTTP_CLIENT_IP');
        }
        // Check for IP passed from proxy
        elseif (!empty($request->server('HTTP_X_FORWARDED_FOR'))) {
            // Can contain multiple IPs, get the first one
            $ips = explode(',', $request->server('HTTP_X_FORWARDED_FOR'));
            return trim($ips[0]);
        }
        // Check for IP from remote address
        elseif (!empty($request->server('REMOTE_ADDR'))) {
            return $request->server('REMOTE_ADDR');
        }

        return $request->ip() ?: 'unknown';
    }

    /**
     * Get request body content.
     */
    protected function getRequestBody(Request $request): ?string
    {
        $content = $request->getContent();
        
        if (empty($content)) {
            // Try to get from input if JSON/form data
            $input = $request->all();
            if (!empty($input)) {
                return json_encode($input, JSON_UNESCAPED_UNICODE);
            }
        }

        return $content;
    }

    /**
     * Sanitize headers to remove sensitive information.
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = config('api-access.logging.sensitive_headers', [
            'authorization',
            'x-api-key',
            'x-api-secret',
            'cookie',
            'set-cookie',
        ]);

        $sanitized = [];
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize request body to mask sensitive fields.
     */
    protected function sanitizeRequestBody(string $body): string
    {
        $sensitiveFields = config('api-access.logging.sensitive_fields', [
            'password',
            'secret',
            'token',
            'api_key',
            'api_secret',
        ]);

        // Try to decode JSON
        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // Sanitize JSON data
            foreach ($sensitiveFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = '[REDACTED]';
                }
            }
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        // For non-JSON content, try basic string replacement
        foreach ($sensitiveFields as $field) {
            $pattern = '/("?' . preg_quote($field, '/') . '"?\s*[:=]\s*)[^&\s\n\r"]*/i';
            $body = preg_replace($pattern, '$1[REDACTED]', $body);
        }

        return $body;
    }

    /**
     * Truncate content to specified maximum size.
     */
    protected function truncateContent(string $content, int $maxSize): string
    {
        if (strlen($content) <= $maxSize) {
            return $content;
        }

        return substr($content, 0, $maxSize) . '... [TRUNCATED]';
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

        // For test mode, allow localhost domains from config
        if ($apiKey->mode === 'test' && $this->isTestModeDomainAllowed($domain)) {
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
     * Check if domain is allowed in test mode using config.
     *
     * @param  string  $domain
     * @return bool
     */
    protected function isTestModeDomainAllowed(string $domain): bool
    {
        $localhostDomains = config('api-access.localhost_domains', [
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0',
            '*.test',
            '*.local',
            '*.dev',
        ]);

        $domain = strtolower($domain);

        foreach ($localhostDomains as $pattern) {
            $pattern = strtolower($pattern);
            
            // Exact match
            if ($domain === $pattern) {
                return true;
            }
            
            // Wildcard pattern matching
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace(['\*', '\.'], ['.*', '\.'], preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, $domain)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return an unauthorized response.
     *
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse(string $message, string $requestId = null): JsonResponse
    {
        $response = [
            'error' => 'Unauthorized',
            'message' => $message,
        ];

        if ($requestId) {
            $response['request_id'] = $requestId;
        }

        return response()->json($response, 401);
    }
}