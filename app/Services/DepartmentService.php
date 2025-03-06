<?php

namespace App\Services;

use App\Models\Api;
use App\Models\User;
use App\Models\Department;
use App\Repositories\DepartmentRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DepartmentService
{
    public function __construct(
        private DepartmentRepository $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function createDepartment(array $validated, array $apiAssignments): Department
    {
        $formattedApiAssignments = collect($apiAssignments)->map(function ($assignment) {
            return [
                'id' => $assignment['apiId'],
                'permissions' => ['rate_limit' => (int) $assignment['rateLimit']]
            ];
        })->all();

        return $this->repository->create([
            ...$validated,
            'user_assignments' => [], // Make sure it's an empty array, not a JSON string
            'api_assignments' => $formattedApiAssignments, // Store as actual array
            'is_active' => true,
            'created_by' => Auth::id()
        ]);
    }

    public function getDepartmentData(Department $department): array
    {
        $assignedApiIds = collect($department->api_assignments)->pluck('id')->toArray();
        $assignedUserIds = collect($department->user_assignments)->pluck('userId')->toArray();

        $availableApis = $this->repository->getAvailableApis($assignedApiIds)
            ->map(fn($api) => [
                'id' => $api->_id,
                'name' => $api->name,
                'type' => $api->type,
                'description' => $api->description
            ]);

        $availableUsers = $this->repository->getAvailableUsers($assignedUserIds)
            ->map(fn($user) => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email
            ]);

        return [
            'department' => $this->formatDepartmentData($department),
            'available_apis' => $availableApis,
            'available_users' => $availableUsers
        ];
    }

    public function toggleDepartmentStatus(Department $department): Department
    {
        $department->is_active = !$department->is_active;
        $this->repository->updateDepartment($department);
        return $department;
    }

    public function updateApiRateLimit(Department $department, string $apiId, int $rateLimit): array
    {
        if (!$department->is_active) {
            throw new \Exception('Cannot update rate limit for inactive department');
        }

        $assignments = collect($department->api_assignments)->toArray();
        $assignmentIndex = array_search($apiId, array_column($assignments, 'id'));

        if ($assignmentIndex === false) {
            throw new \Exception('API assignment not found');
        }

        $api = $this->repository->findActiveApi($apiId);
        if (!$api) {
            throw new \Exception('API not found or inactive');
        }

        $assignments[$assignmentIndex]['permissions']['rate_limit'] = $rateLimit;
        $department->api_assignments = $assignments;
        
        $this->repository->updateDepartment($department);

        Log::info('Rate limit updated', [
            'department' => $department->id,
            'api' => $apiId,
            'new_limit' => $rateLimit
        ]);

        return [
            'apiId' => $apiId,
            'api' => [
                'id' => $api->_id,
                'name' => $api->name,
                'type' => $api->type,
                'description' => $api->description
            ],
            'rateLimit' => $rateLimit
        ];
    }

    public function assignUser(Department $department, string $userId): array
    {
        if (!$department->is_active) {
            throw new \Exception('Cannot assign users to inactive department');
        }

        $user = $this->repository->findNonAdminUser($userId);
        if (!$user) {
            throw new \Exception('Invalid user or admin users cannot be assigned');
        }

        $assignments = $department->user_assignments ?? [];
        if (collect($assignments)->contains('userId', $userId)) {
            throw new \Exception('User is already assigned to this department');
        }

        $assignments[] = [
            'userId' => $userId,
            'assigned_at' => now()
        ];

        $department->user_assignments = $assignments;
        $this->repository->updateDepartment($department);

        Log::info('User assigned to department', [
            'department' => $department->id,
            'user' => $user->id
        ]);

        return [
            'userId' => $user->_id,
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ];
    }

    public function assignApi(Department $department, string $apiId, int $rateLimit): array
    {
        if (!$department->is_active) {
            throw new \Exception('Cannot assign APIs to inactive department');
        }

        $api = $this->repository->findActiveApi($apiId);
        if (!$api) {
            throw new \Exception('API not found or is inactive');
        }

        $assignments = $department->api_assignments ?? [];
        if (collect($assignments)->contains('id', $apiId)) {
            throw new \Exception('API is already assigned to this department');
        }

        $assignments[] = [
            'id' => $apiId,
            'permissions' => ['rate_limit' => (int) $rateLimit],
            'assigned_at' => now()
        ];

        $department->api_assignments = $assignments;
        $this->repository->updateDepartment($department);

        Log::info('API assigned to department', [
            'department' => $department->id,
            'api' => $api->id
        ]);

        return [
            'apiId' => $api->_id,
            'api' => [
                'id' => $api->_id,
                'name' => $api->name,
                'type' => $api->type,
                'description' => $api->description
            ],
            'rateLimit' => $rateLimit
        ];
    }

    public function removeUser(Department $department, string $userId): void
    {
        if (!$department->is_active) {
            throw new \Exception('Cannot modify inactive department');
        }

        $assignments = collect($department->user_assignments ?? []);
        if (!$assignments->contains('userId', $userId)) {
            throw new \Exception('User is not assigned to this department');
        }

        $updatedAssignments = $assignments->reject(fn($assignment) => 
            $assignment['userId'] === $userId
        )->values()->all();

        $department->user_assignments = $updatedAssignments;
        $this->repository->updateDepartment($department);

        Log::info('User removed from department', [
            'department' => $department->id,
            'user' => $userId
        ]);
    }

    public function removeApi(Department $department, string $apiId): void
    {
        if (!$department->is_active) {
            throw new \Exception('Cannot modify inactive department');
        }

        $assignments = collect($department->api_assignments ?? []);
        $assignmentIndex = array_search($apiId, array_column($assignments->toArray(), 'id'));

        if ($assignmentIndex === false) {
            throw new \Exception('API assignment not found');
        }

        $updatedAssignments = $assignments->filter(fn($assignment) => 
            $assignment['id'] !== $apiId
        )->values()->all();

        $department->api_assignments = $updatedAssignments;
        $this->repository->updateDepartment($department);

        Log::info('API removed from department', [
            'department' => $department->id,
            'api' => $apiId
        ]);
    }

    private function formatDepartmentData(Department $department): array
    {
        return [
            'id' => $department->_id,
            'name' => $department->name,
            'description' => $department->description,
            'is_active' => $department->is_active,
            'api_assignments' => $this->formatApiAssignments($department),
            'user_assignments' => $this->formatUserAssignments($department),
            'created_at' => $department->created_at,
            'created_by' => $department->created_by
        ];
    }

    private function formatApiAssignments(Department $department): array
    {
        return collect($department->api_assignments)->map(function ($assignment) {
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
        })->values()->all();
    }

    private function formatUserAssignments(Department $department): array
    {
        return collect($department->user_assignments)->map(function ($assignment) {
            $user = User::find($assignment['userId']);
            return [
                'userId' => $assignment['userId'],
                'user' => [
                    'id' => $user?->_id,
                    'name' => $user?->name,
                    'email' => $user?->email
                ]
            ];
        })->values()->all();
    }
}