<?php

namespace App\Repositories;

use App\Models\Department;
use App\Models\Api;
use App\Models\User;
use Illuminate\Support\Collection;

class DepartmentRepository
{
    public function getAll(): Collection
    {
        return Department::where('is_active', true)->get();
    }

    public function getAllWithInactive(): Collection
    {
        return Department::all();
    }

    public function create(array $data): Department
    {
        return Department::create($data);
    }

    public function getAvailableApis(array $excludeApiIds): Collection
    {
        return Api::where('is_active', true)
            ->whereNotIn('_id', $excludeApiIds)
            ->get();
    }

    public function getAvailableUsers(array $excludeUserIds): Collection
    {
        return User::whereNotIn('_id', $excludeUserIds)
            ->where('role', '!=', 'admin')
            ->get();
    }

    public function findActiveApi(string $apiId): ?Api
    {
        return Api::where('_id', $apiId)
            ->where('is_active', true)
            ->first();
    }

    public function findNonAdminUser(string $userId): ?User
    {
        return User::where('_id', $userId)
            ->where('role', '!=', 'admin')
            ->first();
    }

    public function updateDepartment(Department $department): bool
    {
        return $department->save();
    }

    public function findActiveDepartmentByUser(string $userId): ?Department
    {
        return Department::where('is_active', true)
            ->whereRaw(['user_assignments.userId' => $userId])
            ->first();
    }

    public function getActiveApisForDepartment(Department $department): Collection
    {
        if (!$department->is_active) {
            return collect([]);
        }
        
        $assignedApiIds = collect($department->api_assignments)->pluck('id')->toArray();
        return Api::whereIn('_id', $assignedApiIds)
            ->where('is_active', true)
            ->get();
    }
}