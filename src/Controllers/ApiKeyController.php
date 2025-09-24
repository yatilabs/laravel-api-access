<?php

namespace Yatilabs\ApiAccess\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Yatilabs\ApiAccess\Services\ApiAccessService;

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
        return response()->json([
            'success' => true,
            'modes' => [
                'test' => 'Test Mode (allows localhost)',
                'live' => 'Live Mode (strict domain validation)'
            ]
        ]);
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
            
            return response()->json([
                'success' => true,
                'api_key' => $apiKey,
                'modes' => [
                    'test' => 'Test Mode (allows localhost)',
                    'live' => 'Live Mode (strict domain validation)'
                ]
            ]);
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
}