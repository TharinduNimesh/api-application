<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiAccess
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

        // Check if user role is admin
        if ($user->role === 'admin') {
            return $next($request);
        }

        // For PAID APIs, check trial status
        if ($api->type === 'PAID') {
            if (!$user->isOnTrial()) {
                return response()->json([
                    'status' => 403,
                    'error' => 'Trial period has expired. Please subscribe to access this API.',
                    'trialEndsAt' => $user->created_at->addDays(15)->format('Y-m-d')
                ], 403);
            }
        }

        return $next($request);
    }
}
