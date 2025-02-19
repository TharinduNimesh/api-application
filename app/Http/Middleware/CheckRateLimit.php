<?php

namespace App\Http\Middleware;

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

        $rateLimitTime = 60 * 60; // 1 hour
        $key = 'api:' . $api->id . ':user:' . $user->id;
        
        if (RateLimiter::tooManyAttempts($key, $api->rateLimit)) {
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
