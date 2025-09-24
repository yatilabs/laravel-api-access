<?php

namespace Yatilabs\ApiAccess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ApiLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'api_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'api_key_id',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'route',
        'request_headers',
        'request_body',
        'query_parameters',
        'response_status',
        'response_headers',
        'response_body',
        'execution_time_ms',
        'error_message',
        'error_trace',
        'api_key_hash',
        'is_authenticated',
        'request_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_headers' => 'array',
        'query_parameters' => 'array',
        'response_headers' => 'array',
        'is_authenticated' => 'boolean',
        'execution_time_ms' => 'integer',
        'response_status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the API key that owns this log entry.
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Scope to filter logs by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter logs by API key.
     */
    public function scopeForApiKey($query, $apiKeyId)
    {
        return $query->where('api_key_id', $apiKeyId);
    }

    /**
     * Scope to filter logs by IP address.
     */
    public function scopeForIpAddress($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope to filter logs by response status code.
     */
    public function scopeWithStatus($query, $statusCode)
    {
        return $query->where('response_status', $statusCode);
    }

    /**
     * Scope to filter logs by authentication status.
     */
    public function scopeAuthenticated($query, $authenticated = true)
    {
        return $query->where('is_authenticated', $authenticated);
    }

    /**
     * Scope to get logs older than specified days.
     */
    public function scopeOlderThan($query, $days)
    {
        return $query->where('created_at', '<', Carbon::now()->subDays($days));
    }

    /**
     * Get formatted execution time.
     */
    public function getFormattedExecutionTimeAttribute()
    {
        if (!$this->execution_time_ms) {
            return 'N/A';
        }

        if ($this->execution_time_ms < 1000) {
            return $this->execution_time_ms . 'ms';
        }

        return round($this->execution_time_ms / 1000, 2) . 's';
    }

    /**
     * Get status code color for UI display.
     */
    public function getStatusColorAttribute()
    {
        if ($this->response_status >= 200 && $this->response_status < 300) {
            return 'success'; // Green
        } elseif ($this->response_status >= 300 && $this->response_status < 400) {
            return 'warning'; // Yellow
        } elseif ($this->response_status >= 400 && $this->response_status < 500) {
            return 'danger'; // Red
        } elseif ($this->response_status >= 500) {
            return 'dark'; // Black/Dark
        }
        
        return 'secondary'; // Gray
    }

    /**
     * Get truncated request body for display.
     */
    public function getTruncatedRequestBodyAttribute()
    {
        if (!$this->request_body) {
            return null;
        }

        $maxLength = 100;
        if (strlen($this->request_body) > $maxLength) {
            return substr($this->request_body, 0, $maxLength) . '...';
        }

        return $this->request_body;
    }

    /**
     * Get truncated response body for display.
     */
    public function getTruncatedResponseBodyAttribute()
    {
        if (!$this->response_body) {
            return null;
        }

        $maxLength = 100;
        if (strlen($this->response_body) > $maxLength) {
            return substr($this->response_body, 0, $maxLength) . '...';
        }

        return $this->response_body;
    }

    /**
     * Check if the log has error information.
     */
    public function hasError()
    {
        return !empty($this->error_message) || $this->response_status >= 400;
    }

    /**
     * Get the API key identifier for display (masked for security).
     */
    public function getApiKeyDisplayAttribute()
    {
        if ($this->apiKey) {
            return substr($this->apiKey->key, 0, 8) . '...';
        }

        return 'Unknown';
    }

    /**
     * Get browser/client information from user agent.
     */
    public function getClientInfoAttribute()
    {
        if (!$this->user_agent) {
            return 'Unknown';
        }

        // Simple user agent parsing - you might want to use a more sophisticated library
        $userAgent = $this->user_agent;
        
        if (strpos($userAgent, 'curl') !== false) {
            return 'cURL';
        } elseif (strpos($userAgent, 'Postman') !== false) {
            return 'Postman';
        } elseif (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        }

        return 'Other';
    }
}