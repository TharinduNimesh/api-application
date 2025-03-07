<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckApiStatus
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

        Log::info('CheckApiStatus middleware started', [
            'user_id' => $user?->_id,
            'user_role' => $user?->role,
            'path' => $request->path(),
            'method' => $request->method(),
            'is_xhr' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'accept_header' => $request->header('Accept'),
            'is_inertia' => $request->header('X-Inertia') ? true : false,
            'api_active' => $api?->is_active
        ]);

        // If the API is inactive and the user is not an admin
        if (!$api->is_active && $user->role !== 'admin') {
            Log::warning('Inactive API access attempt', [
                'user_id' => $user?->_id,
                'api_id' => $api->_id
            ]);

            // Pass error through request attributes
            $request->attributes->add(['api_access_error' => [
                'status' => 403,
                'message' => 'This API is currently inactive'
            ]]);

            Log::info('Added api_access_error to request attributes in CheckApiStatus', [
                'attributes' => $request->attributes->all()
            ]);
            
            return $next($request);
        }

        Log::info('API status check passed', [
            'user_id' => $user?->_id,
            'api_id' => $api->_id
        ]);

        return $next($request);
    }
}
