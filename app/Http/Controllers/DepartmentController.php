<?php

namespace App\Http\Controllers;

use App\Models\Department;
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
        return Inertia::render('Departments/Show', [
            'department' => $department
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
}