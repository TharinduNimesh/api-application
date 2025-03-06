<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiAccessControl
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $api = $request->route('api');

        if (!$api) {
            return $next($request);
        }

        try {
            // Check if user can access this API
            if (!$api->isAccessibleByUser($user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => $api->is_active 
                        ? 'You do not have access to this API. Please check your subscription plan or department assignments.'
                        : 'This API is currently inactive.'
                ], 403);
            }

            // Skip rate limiting for admins
            if ($user->role === 'admin') {
                return $next($request);
            }

            // Apply rate limiting based on user's limit
            $key = sprintf('api:%s:user:%s', $api->_id, $user->_id);
            $rateLimit = $api->getUserRateLimit($user);

            if (RateLimiter::tooManyAttempts($key, $rateLimit)) {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'status' => 'error',
                    'message' => "Rate limit exceeded. Please try again in {$seconds} seconds.",
                    'retryAfter' => $seconds
                ], 429);
            }

            RateLimiter::hit($key, 60 * 60); // Key expires in 1 hour
            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while checking API access.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}