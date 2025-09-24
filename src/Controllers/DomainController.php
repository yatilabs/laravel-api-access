<?php

namespace Yatilabs\ApiAccess\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Yatilabs\ApiAccess\Services\ApiAccessService;

class DomainController extends Controller
{
    protected $apiAccessService;

    public function __construct(ApiAccessService $apiAccessService)
    {
        $this->middleware('auth');
        $this->apiAccessService = $apiAccessService;
    }

    /**
     * Show the form for creating a new domain restriction.
     */
    public function create()
    {
        try {
            $apiKeysForDropdown = $this->apiAccessService->getApiKeysForDropdown();
            
            return response()->json([
                'success' => true,
                'api_keys' => $apiKeysForDropdown
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load API keys'
            ], 500);
        }
    }

    /**
     * Store a newly created domain restriction.
     */
    public function store(Request $request)
    {
        try {
            $domain = $this->apiAccessService->createDomain($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Domain restriction created successfully!',
                'domain' => $domain->load('apiKey')
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create domain restriction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing a domain restriction.
     */
    public function edit($id)
    {
        try {
            $domain = $this->apiAccessService->getDomain($id);
            $apiKeysForDropdown = $this->apiAccessService->getApiKeysForDropdown();
            
            return response()->json([
                'success' => true,
                'domain' => $domain,
                'api_keys' => $apiKeysForDropdown
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Domain restriction not found'
            ], 404);
        }
    }

    /**
     * Update the specified domain restriction.
     */
    public function update(Request $request, $id)
    {
        try {
            $domain = $this->apiAccessService->updateDomain($id, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Domain restriction updated successfully!',
                'domain' => $domain->load('apiKey')
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update domain restriction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified domain restriction.
     */
    public function destroy($id)
    {
        try {
            $this->apiAccessService->deleteDomain($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Domain restriction deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete domain restriction: ' . $e->getMessage()
            ], 500);
        }
    }
}