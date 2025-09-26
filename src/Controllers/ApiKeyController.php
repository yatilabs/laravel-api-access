<?php

namespace Yatilabs\ApiAccess\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Yatilabs\ApiAccess\Services\ApiAccessService;
use Yatilabs\ApiAccess\Models\ApiLog;
use Yatilabs\ApiAccess\Models\ApiKey;

class ApiKeyController extends Controller
{
    protected $apiAccessService;

    public function __construct(ApiAccessService $apiAccessService)
    {
        $this->middleware('auth');
        $this->apiAccessService = $apiAccessService;
    }

    /**
     * Display the API keys management interface.
     */
    public function index(Request $request)
    {
        $viewData = $this->apiAccessService->getViewData($request);
        
        return view('api-access::manage', $viewData);
    }

    /**
     * Show the form for creating a new API key.
     */
    public function create()
    {
        $response = [
            'success' => true,
            'modes' => [
                'test' => 'Test Mode (allows localhost)',
                'live' => 'Live Mode (strict domain validation)'
            ]
        ];

        // Add owner options if enabled
        if (ApiKey::isOwnerEnabled()) {
            $response['owner_enabled'] = true;
            $response['owner_required'] = ApiKey::isOwnerRequired();
            $response['owner_label'] = config('api-access.model_owner.label', 'Owner');
            $response['available_owners'] = ApiKey::getAvailableOwners()->map(function ($owner) {
                $config = config('api-access.model_owner');
                $idColumn = $config['id_column'] ?? 'id';
                $titleColumn = $config['title_column'] ?? 'name';
                $additionalColumns = $config['additional_columns'] ?? [];

                $displayText = $owner->{$titleColumn};
                
                // Add additional columns to display text
                if (!empty($additionalColumns)) {
                    $additionalData = [];
                    foreach ($additionalColumns as $column) {
                        if (isset($owner->{$column})) {
                            $additionalData[] = $owner->{$column};
                        }
                    }
                    if (!empty($additionalData)) {
                        $displayText .= ' (' . implode(', ', $additionalData) . ')';
                    }
                }

                return [
                    'id' => $owner->{$idColumn},
                    'text' => $displayText,
                ];
            });
        } else {
            $response['owner_enabled'] = false;
        }

        return response()->json($response);
    }

    /**
     * Store a newly created API key.
     */
    public function store(Request $request)
    {
        try {
            $apiKey = $this->apiAccessService->createApiKey($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'API key created successfully!',
                'api_key' => $apiKey,
                'plain_secret' => $apiKey->plain_secret
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create API key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing an API key.
     */
    public function edit($id)
    {
        try {
            $apiKey = $this->apiAccessService->getApiKey($id);
            
            $response = [
                'success' => true,
                'api_key' => $apiKey,
                'modes' => [
                    'test' => 'Test Mode (allows localhost)',
                    'live' => 'Live Mode (strict domain validation)'
                ]
            ];

            // Add owner information if enabled
            if (ApiKey::isOwnerEnabled()) {
                $response['owner_enabled'] = true;
                $response['owner_required'] = ApiKey::isOwnerRequired();
                $response['owner_label'] = config('api-access.model_owner.label', 'Owner');
                $response['available_owners'] = ApiKey::getAvailableOwners()->map(function ($owner) {
                    $config = config('api-access.model_owner');
                    $idColumn = $config['id_column'] ?? 'id';
                    $titleColumn = $config['title_column'] ?? 'name';
                    $additionalColumns = $config['additional_columns'] ?? [];

                    $displayText = $owner->{$titleColumn};
                    
                    // Add additional columns to display text
                    if (!empty($additionalColumns)) {
                        $additionalData = [];
                        foreach ($additionalColumns as $column) {
                            if (isset($owner->{$column})) {
                                $additionalData[] = $owner->{$column};
                            }
                        }
                        if (!empty($additionalData)) {
                            $displayText .= ' (' . implode(', ', $additionalData) . ')';
                        }
                    }

                    return [
                        'id' => $owner->{$idColumn},
                        'text' => $displayText,
                    ];
                });
                
                // Add current owner information
                if ($apiKey->owner) {
                    $config = config('api-access.model_owner');
                    $idColumn = $config['id_column'] ?? 'id';
                    $response['api_key']['owner_id'] = $apiKey->owner->{$idColumn};
                    $response['api_key']['owner_display_name'] = $apiKey->owner_display_name;
                }
            } else {
                $response['owner_enabled'] = false;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API key not found'
            ], 404);
        }
    }

    /**
     * Update the specified API key.
     */
    public function update(Request $request, $id)
    {
        try {
            $apiKey = $this->apiAccessService->updateApiKey($id, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'API key updated successfully!',
                'api_key' => $apiKey
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update API key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified API key.
     */
    public function destroy($id)
    {
        try {
            $this->apiAccessService->deleteApiKey($id);
            
            return response()->json([
                'success' => true,
                'message' => 'API key deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete API key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regenerate API key secret.
     */
    public function regenerateSecret($id)
    {
        try {
            $apiKey = $this->apiAccessService->regenerateSecret($id);
            
            return response()->json([
                'success' => true,
                'message' => 'New secret generated successfully!',
                'api_key' => $apiKey,
                'plain_secret' => $apiKey->plain_secret
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate secret: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle API key active status.
     */
    public function toggleStatus($id)
    {
        try {
            $apiKey = $this->apiAccessService->toggleStatus($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully!',
                'api_key' => $apiKey
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get logs with filters and pagination.
     */
    public function logs(Request $request)
    {
        // Check if logging is enabled
        if (!config('api-access.logging.enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Logging is disabled in configuration',
                'logs_enabled' => false
            ]);
        }

        try {
            $query = ApiLog::with(['apiKey' => function($query) {
                    if (ApiKey::isOwnerEnabled()) {
                        $query->with('owner');
                    }
                }])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('api_key_id') && $request->api_key_id !== '') {
                $query->where('api_key_id', $request->api_key_id);
            }

            if ($request->has('ip_address') && $request->ip_address !== '') {
                $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
            }

            if ($request->has('status_code') && $request->status_code !== '') {
                $query->where('response_status', $request->status_code);
            }

            if ($request->has('method') && $request->method !== '') {
                $query->where('method', $request->method);
            }

            if ($request->has('date_from') && $request->date_from !== '') {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to !== '') {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->has('authenticated') && $request->authenticated !== '') {
                $query->where('is_authenticated', (bool) $request->authenticated);
            }

            // Get paginated results
            $perPage = min($request->get('per_page', 25), 100); // Max 100 per page
            $logs = $query->paginate($perPage);

            // Format logs for display
            $logs->getCollection()->transform(function ($log) {
                return [
                    'id' => $log->id,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'method' => $log->method,
                    'url' => $log->url,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'response_status' => $log->response_status,
                    'execution_time_ms' => $log->execution_time_ms,
                    'formatted_execution_time' => $log->formatted_execution_time,
                    'status_color' => $log->status_color,
                    'is_authenticated' => $log->is_authenticated,
                    'has_error' => $log->hasError(),
                    'api_key_display' => $log->api_key_display,
                    'client_info' => $log->client_info,
                    'truncated_request_body' => $log->truncated_request_body,
                    'truncated_response_body' => $log->truncated_response_body,
                    'error_message' => $log->error_message,
                    'api_key' => $log->apiKey ? [
                        'id' => $log->apiKey->id,
                        'description' => $log->apiKey->description,
                        'key_preview' => substr($log->apiKey->key, 0, 8) . '...',
                        'owner_display_name' => $log->apiKey->owner_display_name ?? null,
                        'owner_label' => $log->apiKey->owner_label ?? null,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'logs_enabled' => true,
                'data' => $logs->items(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed log information.
     */
    public function logDetail($id)
    {
        try {
            $query = ApiLog::with(['apiKey' => function($query) {
                if (ApiKey::isOwnerEnabled()) {
                    $query->with('owner');
                }
            }]);
            
            $log = $query->findOrFail($id);

            return response()->json([
                'success' => true,
                'log' => [
                    'id' => $log->id,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'request_id' => $log->request_id,
                    'method' => $log->method,
                    'url' => $log->url,
                    'route' => $log->route,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'response_status' => $log->response_status,
                    'execution_time_ms' => $log->execution_time_ms,
                    'formatted_execution_time' => $log->formatted_execution_time,
                    'is_authenticated' => $log->is_authenticated,
                    'error_message' => $log->error_message,
                    'error_trace' => $log->error_trace,
                    'request_headers' => $log->request_headers,
                    'request_body' => $log->request_body,
                    'query_parameters' => $log->query_parameters,
                    'response_headers' => $log->response_headers,
                    'response_body' => $log->response_body,
                    'api_key' => $log->apiKey ? [
                        'id' => $log->apiKey->id,
                        'description' => $log->apiKey->description,
                        'key' => substr($log->apiKey->key, 0, 8) . '...' . substr($log->apiKey->key, -4),
                        'mode' => $log->apiKey->mode,
                        'is_active' => $log->apiKey->is_active,
                        'owner_display_name' => $log->apiKey->owner_display_name ?? null,
                        'owner_label' => $log->apiKey->owner_label ?? null,
                    ] : null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Log not found'
            ], 404);
        }
    }

    /**
     * Get available filter options for logs.
     */
    public function logFilters()
    {
        try {
            // Get available API keys for filter dropdown
            $apiKeys = ApiKey::select('id', 'description', 'key', 'mode')
                ->orderBy('description')
                ->get()
                ->map(function ($key) {
                    return [
                        'id' => $key->id,
                        'label' => $key->description . ' (' . substr($key->key, 0, 8) . '... - ' . ucfirst($key->mode) . ')',
                        'description' => $key->description,
                        'key_preview' => substr($key->key, 0, 8) . '...',
                        'mode' => $key->mode,
                    ];
                });

            // Get distinct status codes from logs
            $statusCodes = ApiLog::select('response_status')
                ->distinct()
                ->orderBy('response_status')
                ->pluck('response_status')
                ->filter()
                ->values();

            // Get distinct HTTP methods
            $methods = ApiLog::select('method')
                ->distinct()
                ->orderBy('method')
                ->pluck('method')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'filters' => [
                    'api_keys' => $apiKeys,
                    'status_codes' => $statusCodes,
                    'methods' => $methods,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch filter options: ' . $e->getMessage()
            ], 500);
        }
    }
}