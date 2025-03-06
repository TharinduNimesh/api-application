<?php

namespace App\Http\Middleware;

use App\Models\Department;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

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

        // Get user's department and its API rate limit if exists
        $department = Department::whereRaw([
            'api_assignments.id' => $api->_id,
            'user_assignments.userId' => $user->_id
        ])->first();

        $rateLimitTime = 60 * 60; // 1 hour
        $key = 'api:' . $api->id . ':user:' . $user->id;
        
        // Use department rate limit if exists, otherwise use API's default rate limit
        $rateLimit = $department 
            ? collect($department->api_assignments)
                ->firstWhere('id', $api->_id)['permissions']['rate_limit'] ?? $api->rateLimit
            : $api->rateLimit;
        
        if (RateLimiter::tooManyAttempts($key, $rateLimit)) {
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
