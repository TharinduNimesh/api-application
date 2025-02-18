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
            'baseUrl' => $api->baseUrl,
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

        // Retrieve the endpoint associated with the API and load its parameters
        $endpoint = $api->endpoints()->where('_id', $endpointId)->first();
        if (!$endpoint) {
            return response()->json(['error' => 'Endpoint not found'], 404);
        }

        // Replace path parameters placeholders with actual data
        $path = $endpoint->path;
        // Ensure parameters relationship is loaded
        $endpoint->load('parameters');
        foreach ($endpoint->parameters as $param) {
            // Check if parameter location is "path"
            if (isset($param->location) && $param->location === 'path') {
                if (!isset($data[$param->name])) {
                    return response()->json(['error' => "Missing path parameter: {$param->name}"], 422);
                }
                // replace placeholder e.g. {id} with actual value
                $path = str_replace('{' . $param->name . '}', $data[$param->name], $path);
                // Remove the path parameter from data to avoid sending it as query/json
                unset($data[$param->name]);
            }
        }

        // Construct the full external URL
        $url = rtrim($api->baseUrl, '/') . '/' . ltrim($path, '/');

        $client = new Client();
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
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
