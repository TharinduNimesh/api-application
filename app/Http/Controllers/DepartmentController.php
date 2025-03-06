<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\DepartmentService;
use App\Http\Requests\CreateDepartmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function __construct(
        private DepartmentService $departmentService
    ) {}

    public function index()
    {
        $departments = $this->departmentService->getAll();
        return Inertia::render('Departments/Index', [
            'departments' => $departments
        ]);
    }

    public function store(CreateDepartmentRequest $request)
    {
        $department = $this->departmentService->createDepartment(
            $request->validated(),
            $request->input('apiAssignments', [])
        );

        return response()->json([
            'message' => 'Department created successfully',
            'department' => $department
        ], 201);
    }

    public function show(Department $department)
    {
        $data = $this->departmentService->getDepartmentData($department);
        return Inertia::render('Departments/Show', $data);
    }

    public function toggleStatus(Department $department)
    {
        $department = $this->departmentService->toggleDepartmentStatus($department);

        return response()->json([
            'message' => $department->is_active ? 'Department activated successfully' : 'Department deactivated successfully',
            'department' => $department
        ]);
    }

    public function updateRateLimit(Request $request, Department $department, string $apiId)
    {
        try {
            $request->validate([
                'rateLimit' => 'required|integer|min:1|max:1000000'
            ]);

            $assignment = $this->departmentService->updateApiRateLimit(
                $department, 
                $apiId, 
                $request->rateLimit
            );

            return response()->json([
                'message' => 'Rate limit updated successfully',
                'assignment' => $assignment
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update rate limit', [
                'error' => $e->getMessage(),
                'department' => $department->id,
                'api' => $apiId
            ]);

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function assignUser(Request $request, Department $department)
    {
        try {
            $request->validate([
                'userId' => 'required|string|exists:users,_id'
            ]);

            $assignment = $this->departmentService->assignUser(
                $department,
                $request->userId
            );

            return response()->json([
                'message' => 'User assigned successfully',
                'assignment' => $assignment
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to assign user', [
                'error' => $e->getMessage(),
                'department' => $department->id,
                'user' => $request->userId
            ]);

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function assignApi(Request $request, Department $department)
    {
        try {
            $request->validate([
                'apiId' => 'required|string|exists:apis,_id',
                'rateLimit' => 'required|integer|min:1|max:1000000'
            ]);

            $assignment = $this->departmentService->assignApi(
                $department,
                $request->apiId,
                $request->rateLimit
            );

            return response()->json([
                'message' => 'API assigned successfully',
                'assignment' => $assignment
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to assign API', [
                'error' => $e->getMessage(),
                'department' => $department->id,
                'api' => $request->apiId ?? null
            ]);

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function removeUser(Department $department, string $userId)
    {
        try {
            $this->departmentService->removeUser($department, $userId);

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
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function removeApi(Department $department, string $apiId)
    {
        try {
            $this->departmentService->removeApi($department, $apiId);

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
                'message' => $e->getMessage()
            ], 500);
        }
    }
}