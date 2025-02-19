<?php

namespace App\Http\Controllers;

use App\Models\Api;
use App\Http\Requests\CreateApiRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use GuzzleHttp\Client; // make sure to install guzzlehttp/guzzle via Composer

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

        // If not admin, only show active APIs
        if ($user->role !== 'admin') {
            $query->where('is_active', true);
        }

        $apis = $query->get()->map(function($api) {
            return [
                'id' => $api->_id,
                'name' => $api->name,
                'description' => $api->description,
                'type' => $api->type,
                'status' => $api->is_active ? 'ACTIVE' : 'INACTIVE',
                'endpointCount' => $api->endpoints->count(),
                'createdAt' => $api->created_at->format('Y-m-d')
            ];
        });

        return response()->json($apis);
    }

    public function show(Api $api)
    {
        $api->load(['createdBy', 'endpoints.parameters']);

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

    // New method to call external API endpoints
    public function callEndpoint(Request $request, Api $api)
    {
        $request->validate([
            'endpoint_id' => 'required|string',
            'data' => 'nullable|array'
        ]);

        $endpointId = $request->input('endpoint_id');
        $data = $request->input('data', []);

        // Retrieve the endpoint and load its parameters
        $endpoint = $api->endpoints()->where('_id', $endpointId)->first();
        if (!$endpoint) {
            return response()->json([
                'status' => 404,
                'error' => 'Endpoint not found'
            ], 200);
        }

        // Replace path parameter placeholders with actual data
        $path = $endpoint->path;
        $endpoint->load('parameters');
        foreach ($endpoint->parameters as $param) {
            if (isset($param->location) && $param->location === 'path') {
                if (!isset($data[$param->name])) {
                    return response()->json([
                        'status' => 422,
                        'error' => "Missing path parameter: {$param->name}"
                    ], 200);
                }
                $path = str_replace('{' . $param->name . '}', $data[$param->name], $path);
                unset($data[$param->name]);
            }
        }

        $url = rtrim($api->baseUrl, '/') . '/' . ltrim($path, '/');

        $client = new \GuzzleHttp\Client();
        try {
            $options = [];
            if (in_array($endpoint->method, ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $data;
            } elseif ($endpoint->method === 'GET') {
                $options['query'] = $data;
            }
            $response = $client->request($endpoint->method, $url, $options);
            $content = $response->getBody()->getContents();
            $decoded = json_decode($content, true);
            return response()->json([
                'status' => $response->getStatusCode(),
                'data' => $decoded ?? $content,
            ], 200);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $statusCode = $e->getResponse()->getStatusCode();
                return response()->json([
                    'status' => $statusCode,
                    'error' => 'Request failed',
                    'response' => json_decode($responseBody, true) ?? $responseBody,
                ], 200);
            }
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 200);
        }
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
}
