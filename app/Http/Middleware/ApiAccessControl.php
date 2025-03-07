<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAccessControl
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $api = $request->route('api');

        Log::info('ApiAccessControl middleware started', [
            'user_id' => $user?->_id,
            'user_role' => $user?->role,
            'path' => $request->path(),
            'method' => $request->method(),
            'is_xhr' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'accept_header' => $request->header('Accept'),
            'is_inertia' => $request->header('X-Inertia') ? true : false
        ]);

        if (!$api) {
            Log::warning('No API found in route parameters');
            return $next($request);
        }

        try {
            // Check if user can access this API
            if (!$api->isAccessibleByUser($user)) {
                $errorMessage = $api->is_active 
                    ? 'You do not have access to this API. Access is limited based on your department assignments.'
                    : 'This API is currently inactive.';

                Log::warning('API access denied', [
                    'user_id' => $user?->_id,
                    'api_id' => $api->_id,
                    'reason' => $errorMessage
                ]);

                // Always pass error through request attributes
                $request->attributes->add(['api_access_error' => [
                    'status' => 403,
                    'message' => $errorMessage
                ]]);
                
                Log::info('Added api_access_error to request attributes', [
                    'attributes' => $request->attributes->all()
                ]);
                
                return $next($request);
            }

            Log::info('API access granted', [
                'user_id' => $user?->_id,
                'api_id' => $api->_id
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Exception in ApiAccessControl middleware', [
                'user_id' => $user?->_id,
                'api_id' => $api?->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Pass error through request attributes
            $request->attributes->add(['api_access_error' => [
                'status' => 500,
                'message' => 'An error occurred while checking API access.'
            ]]);
            
            return $next($request);
        }
    }
}