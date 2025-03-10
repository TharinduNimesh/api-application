<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use MongoDB\Laravel\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use App\Models\Department;

class Api extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'baseUrl',
        'is_active',
        'rateLimit',
        'created_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, null, 'api_ids', 'department_ids');
    }

    /**
     * Check if the API is accessible by a specific user based on their departments
     *
     * @param User $user
     * @return bool
     */
    public function isAccessibleByUser(User $user): bool
    {
        // Admin users have access to all APIs
        if ($user->role === 'admin') {
            return true;
        }
        
        // API must be active
        if (!$this->is_active) {
            Log::debug("API access denied - API is inactive", [
                'user_id' => $user->_id,
                'api_id' => $this->_id
            ]);
            return false;
        }

        // Get active departments where user is assigned
        $userDepartments = Department::where('is_active', true)
            ->whereRaw(['user_assignments' => ['$elemMatch' => ['userId' => $user->_id]]])
            ->get();
        
        if ($userDepartments->isEmpty()) {
            Log::debug("User has no active department assignments", [
                'user_id' => $user->_id
            ]);
            return false;
        }
        
        // Check if any of these departments have this API assigned
        foreach ($userDepartments as $department) {
            if (isset($department->api_assignments) && is_array($department->api_assignments)) {
                foreach ($department->api_assignments as $assignment) {
                    if (isset($assignment['id']) && $assignment['id'] == $this->_id) {
                        Log::debug("API access granted through active department assignment", [
                            'user_id' => $user->_id,
                            'api_id' => $this->_id,
                            'department_id' => $department->_id,
                            'department_name' => $department->name
                        ]);
                        return true;
                    }
                }
            }
        }
        
        Log::debug("API access denied - No matching active department API assignment", [
            'user_id' => $user->_id,
            'api_id' => $this->_id,
            'user_departments' => $userDepartments->pluck('name')->toArray()
        ]);
        
        return false;
    }
}
