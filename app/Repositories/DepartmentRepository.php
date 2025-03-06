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
}