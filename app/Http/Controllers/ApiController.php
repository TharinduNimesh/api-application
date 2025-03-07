<?php

namespace App\Http\Controllers;

use App\Models\Api;
use App\Models\Endpoint;
use App\Http\Requests\CreateApiRequest;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use GuzzleHttp\Client; // make sure to install guzzlehttp/guzzle via Composer
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    public function create(CreateApiRequest $request)
    {
        $validated = $request->validated();

        $api = Api::create([
            ...$validated,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        foreach ($validated['endpoints'] as $endpointData) {
            $parameters = $endpointData['parameters'] ?? [];
            unset($endpointData['parameters']);

            $endpoint = $api->endpoints()->create($endpointData);

            foreach ($parameters as $parameterData) {
                $endpoint->parameters()->create($parameterData);
            }
        }

        return response()->json($api->load('endpoints.parameters'), 201);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Api::with('endpoints');

        if ($user->role === 'admin') {
            // Admin can see all APIs (both active and inactive)
            $apis = $query->get();
        } else {
            // For non-admin users, get active departments they belong to
            $userDepartments = Department::where('is_active', true)
                ->where('user_assignments', 'elemMatch', ['userId' => $user->_id])
                ->get();
            
            if ($userDepartments->isEmpty()) {
                // User doesn't belong to any active department
                return response()->json([]);
            }

            // Collect all API IDs that the user has access to via their active departments
            $apiIds = [];
            foreach ($userDepartments as $department) {
                if (isset($department->api_assignments) && is_array($department->api_assignments)) {
                    foreach ($department->api_assignments as $assignment) {
                        if (isset($assignment['id'])) {
                            $apiIds[] = $assignment['id'];
                        }
                    }
                }
            }

            // Get unique API IDs
            $apiIds = array_unique($apiIds);
            
            // Query APIs that are active and belong to user's active departments
            if (!empty($apiIds)) {
                $apis = $query->where('is_active', true)
                             ->whereIn('_id', $apiIds)
                             ->get();
            } else {
                $apis = collect(); // Empty collection if no APIs found
            }
        }

        return response()->json($apis->map(function($api) {
            return [
                'id' => $api->_id,
                'name' => $api->name,
                'description' => $api->description,
                'type' => $api->type,
                'status' => $api->is_active ? 'ACTIVE' : 'INACTIVE',
                'endpointCount' => $api->endpoints->count(),
                'createdAt' => $api->created_at->format('Y-m-d')
            ];
        }));
    }

    public function show(Request $request, Api $api)
    {
        Log::info('ApiController show method started', [
            'request_id' => uniqid(),
            'path' => $request->path(),
            'method' => $request->method(),
            'is_xhr' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'accept_header' => $request->header('Accept'),
            'is_inertia' => $request->header('X-Inertia') ? true : false,
            'content_type' => $request->header('Content-Type'),
            'has_error_attr' => $request->attributes->has('api_access_error')
        ]);

        $user = Auth::user();
        
        // Check user access through active departments if not admin
        if ($user->role !== 'admin') {
            $hasAccess = Department::where('is_active', true)
                ->where('user_assignments', 'elemMatch', ['userId' => $user->_id])
                ->whereRaw(['api_assignments' => ['$elemMatch' => ['id' => $api->_id]]])
                ->exists();
                
            if (!$hasAccess) {
                Log::warning('API access denied - no active department access', [
                    'user_id' => $user->_id,
                    'api_id' => $api->_id
                ]);
                
                return Inertia::render('Error/Forbidden', [
                    'status' => 403,
                    'message' => 'You do not have access to this API through any active departments.'
                ]);
            }
        }

        // Check if there's an access error passed from middleware
        if ($request->attributes->has('api_access_error')) {
            $error = $request->attributes->get('api_access_error');
            Log::info('Handling api_access_error in controller', [
                'error' => $error,
                'response_type' => 'inertia'
            ]);

            return Inertia::render('Error/Forbidden', [
                'status' => $error['status'],
                'message' => $error['message']
            ]);
        }

        // Proceed with normal API show logic
        $api->load(['createdBy', 'endpoints.parameters']);

        Log::info('Rendering API show page', [
            'api_id' => $api->_id,
            'api_name' => $api->name,
            'endpoint_count' => $api->endpoints->count()
        ]);

        $apiData = [
            'id' => $api->_id,
            'name' => $api->name,
            'description' => $api->description,
            'type' => $api->type,
            'status' => $api->is_active ? 'ACTIVE' : 'INACTIVE',
            'rateLimit' => $api->rateLimit,
            'createdAt' => $api->created_at->toISOString(),
            'endpoints' => $api->endpoints->map(function($endpoint) {
                return [
                    'id' => $endpoint->_id,
                    'method' => $endpoint->method,
                    'name' => $endpoint->name,
                    'path' => $endpoint->path,
                    'description' => $endpoint->description,
                    'parameters' => $endpoint->parameters
                ];
            }),
            'createdBy' => [
                'name' => $api->createdBy->name,
                'email' => $api->createdBy->email,
            ]
        ];

        return Inertia::render('Api/Show', [
            'api' => $apiData
        ]);
    }

    public function activate(Api $api)
    {
        $api->update(['is_active' => true]);
        return response()->json(['message' => 'API activated successfully']);
    }

    /**
     * Archive (deactivate) an API
     */
    public function archive(Api $api)
    {
        // Check if API is already archived
        if (!$api->is_active) {
            return response()->json([
                'message' => 'API is already archived'
            ], 422);
        }

        $api->update(['is_active' => false]);

        return response()->json([
            'message' => 'API archived successfully',
            'status' => 'success'
        ]);
    }

    /**
     * Delete an API and all its associated endpoints and parameters
     */
    public function destroy(Api $api)
    {
        // Delete all associated endpoints and their parameters
        foreach ($api->endpoints as $endpoint) {
            $endpoint->parameters()->delete();
            $endpoint->delete();
        }
        
        // Delete any associated usage records
        // $api->usage()->delete();
        
        // Delete the API
        $api->delete();

        return response()->json([
            'message' => 'API deleted successfully',
            'status' => 'success'
        ]);
    }

    /**
     * Delete a specific endpoint and its parameters
     */
    public function deleteEndpoint(Api $api, string $endpoint)
    {
        try {
            $endpoint = $api->endpoints()->where('_id', $endpoint)->firstOrFail();
            
            // Delete parameters first
            $endpoint->parameters()->delete();
            
            // Delete the endpoint
            $endpoint->delete();
            
            return response()->json([
                'message' => 'Endpoint deleted successfully',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting endpoint',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateEndpoint(Request $request, Api $api, string $endpointId): JsonResponse
    {
        try {
            $endpoint = $api->endpoints()->findOrFail($endpointId);
            
            // Update basic endpoint info
            $endpoint->update([
                'name'        => $request->endpoint['name'],
                'method'      => $request->endpoint['method'],
                'path'        => $request->endpoint['path'],
                'description' => $request->endpoint['description'],
            ]);

            // Get input parameters from the endpoint (default to empty array)
            $inputParameters = $request->input('endpoint.parameters', []);
            
            // Get IDs of the endpoint's current parameters
            $existingIds = $endpoint->parameters->pluck('_id')->toArray();
            $updatedIds = []; // IDs we will update from input

            // Loop over each parameter from the request (new or update)
            foreach ($inputParameters as $paramData) {
                // Remove id and _id fields from parameter data
                $parameterData = collect($paramData)
                    ->except(['id', '_id'])
                    ->toArray();

                // If an ID is provided and exists, update that parameter
                if (!empty($paramData['id']) && in_array($paramData['id'], $existingIds)) {
                    $parameter = $endpoint->parameters()->find($paramData['id']);
                    if ($parameter) {
                        $parameter->update($parameterData);
                        $updatedIds[] = $paramData['id'];
                    }
                } else {
                    // Create new parameter without id fields
                    $endpoint->parameters()->create($parameterData);
                }
            }

            // Delete any parameters that were not provided in the update
            $toDelete = array_diff($existingIds, $updatedIds);
            if (!empty($toDelete)) {
                $endpoint->parameters()->whereIn('_id', $toDelete)->delete();
            }
            
            // Refresh and return the updated endpoint with parameters
            $endpoint->load('parameters');

            return response()->json([
                'message'  => 'Endpoint updated successfully',
                'endpoint' => $endpoint
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update endpoint',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Api $api): JsonResponse
    {
        try {
            // Update basic API info
            $api->update([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'baseUrl' => $request->baseUrl,
                'rateLimit' => $request->rateLimit,
            ]);

            // Handle new endpoints if any
            if (!empty($request->newEndpoints)) {
                foreach ($request->newEndpoints as $endpointData) {
                    $parameters = $endpointData['parameters'] ?? [];
                    unset($endpointData['parameters']);

                    $endpoint = $api->endpoints()->create($endpointData);

                    foreach ($parameters as $parameterData) {
                        $endpoint->parameters()->create($parameterData);
                    }
                }
            }

            // Refresh and return the updated API with all relations
            $api->load(['endpoints.parameters']);

            return response()->json([
                'message' => 'API updated successfully',
                'api' => $api
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update API',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
