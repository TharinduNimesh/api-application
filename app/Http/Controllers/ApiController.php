<?php

namespace App\Http\Controllers;

use App\Models\Api;
use App\Http\Requests\CreateApiRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
}
