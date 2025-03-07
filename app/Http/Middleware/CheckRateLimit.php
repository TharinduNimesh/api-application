<?php

namespace App\Http\Middleware;

use App\Models\Department;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $api = $request->route('api');

        // Skip rate limiting for admin users
        if (in_array($user->role, ['admin'])) {
            return $next($request);
        }

        // Get all departments the user belongs to that are active
        $departments = Department::where('is_active', true)
            ->get()
            ->filter(function ($department) use ($user, $api) {
                // Check if user is assigned to this department
                $userAssigned = collect($department->user_assignments)->contains('userId', $user->_id);
                
                // Check if API is assigned to this department
                $apiAssigned = collect($department->api_assignments)->contains('id', $api->_id);
                
                return $userAssigned && $apiAssigned;
            });

        if ($departments->isEmpty()) {
            // If user doesn't have department access, they shouldn't access the API at all
            return response()->json([
                'status' => 403,
                'error' => 'You do not have access to this API.'
            ], 403);
        }

        // Find the highest rate limit among all departments
        $maxRateLimit = $departments->reduce(function ($carry, $department) use ($api) {
            $apiAssignment = collect($department->api_assignments)
                ->firstWhere('id', $api->_id);
                
            $rateLimit = $apiAssignment['permissions']['rate_limit'] ?? 0;
            
            return max($carry, $rateLimit);
        }, 0);

        // If no rate limit was found (shouldn't happen if API Access middleware is working)
        if ($maxRateLimit <= 0) {
            return response()->json([
                'status' => 403,
                'error' => 'No rate limit defined for your department.'
            ], 403);
        }

        $rateLimitTime = 60 * 60; // 1 hour
        $key = 'api:' . $api->id . ':user:' . $user->id;
        
        // Log for debugging
        Log::debug("Rate limit for user {$user->id} on API {$api->id}: {$maxRateLimit} requests/hour");
        
        if (RateLimiter::tooManyAttempts($key, $maxRateLimit)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'status' => 429,
                'error' => 'Rate limit exceeded. Try again in ' . $seconds . ' seconds.'
            ], 429);
        }

        RateLimiter::hit($key, $rateLimitTime);
        return $next($request);
    }
}
