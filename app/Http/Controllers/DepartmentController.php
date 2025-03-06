<?php

namespace App\Http\Controllers;

use App\Models\Api;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::all();
        return Inertia::render('Departments/Index', [
            'departments' => $departments
        ]);
    }

    public function store(Request $request)
    {
        // Transform incoming apiAssignments to api_assignments format
        $apiAssignments = collect($request->input('apiAssignments', []))->map(function ($assignment) {
            return [
                'id' => $assignment['apiId'],
                'permissions' => ['rate_limit' => (int) $assignment['rateLimit']]
            ];
        })->all();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string'
        ]);

        $department = Department::create([
            ...$validated,
            'user_assignments' => [], // Initialize with empty array
            'api_assignments' => $apiAssignments, // Store transformed assignments
            'is_active' => true,
            'created_by' => Auth::id()
        ]);

        return response()->json([
            'message' => 'Department created successfully',
            'department' => $department
        ], 201);
    }

    public function show(Department $department)
    {
        // Get all active APIs that are not already assigned to this department
        $assignedApiIds = collect($department->api_assignments)->pluck('apiId')->toArray();
        
        $availableApis = Api::where('is_active', true)
            ->whereNotIn('_id', $assignedApiIds)
            ->get()
            ->map(function ($api) {
                return [
                    'id' => $api->_id,
                    'name' => $api->name,
                    'type' => $api->type,
                    'description' => $api->description
                ];
            });

        // Get all non-admin users not already assigned to this department
        $assignedUserIds = collect($department->user_assignments)->pluck('userId')->toArray();
        
        $availableUsers = User::whereNotIn('_id', $assignedUserIds)
            ->where('role', '!=', 'admin')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->_id,
                    'name' => $user->name,
                    'email' => $user->email
                ];
            });

        // Format department data
        $formattedDepartment = [
            'id' => $department->_id,
            'name' => $department->name,
            'description' => $department->description,
            'is_active' => $department->is_active,
            'api_assignments' => collect($department->api_assignments)->map(function ($assignment) {
                $api = Api::find($assignment['id']);
                return [
                    'apiId' => $assignment['id'],
                    'api' => [
                        'id' => $api?->_id,
                        'name' => $api?->name,
                        'type' => $api?->type,
                        'description' => $api?->description
                    ],
                    'rateLimit' => $assignment['permissions']['rate_limit'] ?? 100
                ];
            })->values()->all(),
            'user_assignments' => collect($department->user_assignments)->map(function ($assignment) {
                $user = User::find($assignment['userId']);
                return [
                    'userId' => $assignment['userId'],
                    'user' => [
                        'id' => $user?->_id,
                        'name' => $user?->name,
                        'email' => $user?->email
                    ]
                ];
            })->values()->all(),
            'created_at' => $department->created_at,
            'created_by' => $department->created_by
        ];

        return Inertia::render('Departments/Show', [
            'department' => $formattedDepartment,
            'available_apis' => $availableApis,
            'available_users' => $availableUsers
        ]);
    }

    public function toggleStatus(Department $department)
    {
        $department->is_active = !$department->is_active;
        $department->save();

        return response()->json([
            'message' => $department->is_active ? 'Department activated successfully' : 'Department deactivated successfully',
            'department' => $department
        ]);
    }

    public function updateRateLimit(Request $request, Department $department, string $apiId)
    {
        try {
            if (!$department->is_active) {
                return response()->json([
                    'message' => 'Cannot update rate limit for inactive department'
                ], 403);
            }

            $request->validate([
                'rateLimit' => 'required|integer|min:1|max:1000000'
            ]);

            $assignments = collect($department->api_assignments)->toArray();
            $assignmentIndex = array_search($apiId, array_column($assignments, 'id'));

            if ($assignmentIndex === false) {
                return response()->json([
                    'message' => 'API assignment not found'
                ], 404);
            }

            // Check if API exists and is active
            $api = Api::find($apiId);
            if (!$api || !$api->is_active) {
                return response()->json([
                    'message' => 'API not found or inactive'
                ], 404);
            }

            $assignments[$assignmentIndex]['permissions']['rate_limit'] = $request->rateLimit;
            $department->api_assignments = $assignments;
            $department->save();

            Log::info('Rate limit updated', [
                'department' => $department->id,
                'api' => $apiId,
                'new_limit' => $request->rateLimit
            ]);

            return response()->json([
                'message' => 'Rate limit updated successfully',
                'assignment' => [
                    'apiId' => $apiId,
                    'api' => [
                        'id' => $api->_id,
                        'name' => $api->name,
                        'type' => $api->type,
                        'description' => $api->description
                    ],
                    'rateLimit' => $request->rateLimit
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update rate limit', [
                'error' => $e->getMessage(),
                'department' => $department->id,
                'api' => $apiId
            ]);

            return response()->json([
                'message' => 'Failed to update rate limit'
            ], 500);
        }
    }

    public function assignUser(Request $request, Department $department)
    {
        try {
            if (!$department->is_active) {
                return response()->json([
                    'message' => 'Cannot assign users to inactive department'
                ], 403);
            }

            $request->validate([
                'userId' => 'required|string|exists:users,_id'
            ]);

            $user = User::where('_id', $request->userId)
                ->where('role', '!=', 'admin')
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Invalid user or admin users cannot be assigned'
                ], 400);
            }

            $assignments = $department->user_assignments ?? [];

            if (collect($assignments)->contains('userId', $request->userId)) {
                return response()->json([
                    'message' => 'User is already assigned to this department'
                ], 400);
            }

            $assignments[] = [
                'userId' => $request->userId,
                'assigned_at' => now()
            ];

            $department->user_assignments = $assignments;
            $department->save();

            Log::info('User assigned to department', [
                'department' => $department->id,
                'user' => $user->id
            ]);

            return response()->json([
                'message' => 'User assigned successfully',
                'assignment' => [
                    'userId' => $user->_id,
                    'user' => [
                        'id' => $user->_id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to assign user', [
                'error' => $e->getMessage(),
                'department' => $department->id,
                'user' => $request->userId
            ]);

            return response()->json([
                'message' => 'Failed to assign user'
            ], 500);
        }
    }

    public function assignApi(Request $request, Department $department)
    {
        try {
            if (!$department->is_active) {
                return response()->json([
                    'message' => 'Cannot assign APIs to inactive department'
                ], 403);
            }

            $request->validate([
                'apiId' => 'required|string|exists:apis,_id',
                'rateLimit' => 'required|integer|min:1|max:1000000'
            ]);

            $api = Api::where('_id', $request->apiId)
                ->where('is_active', true)
                ->first();

            if (!$api) {
                return response()->json([
                    'message' => 'API not found or is inactive'
                ], 404);
            }

            $assignments = $department->api_assignments ?? [];

            if (collect($assignments)->contains('id', $request->apiId)) {
                return response()->json([
                    'message' => 'API is already assigned to this department'
                ], 400);
            }

            $assignments[] = [
                'id' => $request->apiId,
                'permissions' => ['rate_limit' => (int) $request->rateLimit],
                'assigned_at' => now()
            ];

            $department->api_assignments = $assignments;
            $department->save();

            Log::info('API assigned to department', [
                'department' => $department->id,
                'api' => $api->id
            ]);

            return response()->json([
                'message' => 'API assigned successfully',
                'assignment' => [
                    'apiId' => $api->_id,
                    'api' => [
                        'id' => $api->_id,
                        'name' => $api->name,
                        'type' => $api->type,
                        'description' => $api->description
                    ],
                    'rateLimit' => $request->rateLimit
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to assign API', [
                'error' => $e->getMessage(),
                'department' => $department->id,
                'api' => $request->apiId ?? null
            ]);

            return response()->json([
                'message' => 'Failed to assign API'
            ], 500);
        }
    }

    public function removeUser(Department $department, string $userId)
    {
        try {
            if (!$department->is_active) {
                return response()->json([
                    'message' => 'Cannot modify inactive department'
                ], 403);
            }

            $assignments = collect($department->user_assignments ?? []);

            if (!$assignments->contains('userId', $userId)) {
                return response()->json([
                    'message' => 'User is not assigned to this department'
                ], 404);
            }

            $updatedAssignments = $assignments->reject(function ($assignment) use ($userId) {
                return $assignment['userId'] === $userId;
            })->values()->all();

            $department->user_assignments = $updatedAssignments;
            $department->save();

            Log::info('User removed from department', [
                'department' => $department->id,
                'user' => $userId
            ]);

            return response()->json([
                'message' => 'User removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove user', [
                'error' => $e->getMessage(),
                'department' => $department->id,
                'user' => $userId
            ]);

            return response()->json([
                'message' => 'Failed to remove user'
            ], 500);
        }
    }

    public function removeApi(Department $department, string $apiId)
    {
        try {
            if (!$department->is_active) {
                return response()->json([
                    'message' => 'Cannot modify inactive department'
                ], 403);
            }

            $assignments = collect($department->api_assignments ?? []);
            $assignmentIndex = array_search($apiId, array_column($assignments->toArray(), 'id'));

            if ($assignmentIndex === false) {
                return response()->json([
                    'message' => 'API assignment not found'
                ], 404);
            }

            // Remove the API assignment
            $updatedAssignments = $assignments->filter(function ($assignment) use ($apiId) {
                return $assignment['id'] !== $apiId;
            })->values()->all();

            $department->api_assignments = $updatedAssignments;
            $department->save();

            Log::info('API removed from department', [
                'department' => $department->id,
                'api' => $apiId
            ]);

            return response()->json([
                'message' => 'API removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove API', [
                'error' => $e->getMessage(),
                'department' => $department->id,
                'api' => $apiId
            ]);

            return response()->json([
                'message' => 'Failed to remove API'
            ], 500);
        }
    }
}