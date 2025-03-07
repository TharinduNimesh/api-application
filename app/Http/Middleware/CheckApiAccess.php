<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckApiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $api = $request->route('api');

        Log::info('CheckApiAccess middleware started', [
            'user_id' => $user?->_id,
            'user_role' => $user?->role,
            'path' => $request->path(),
            'method' => $request->method(),
            'is_xhr' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'accept_header' => $request->header('Accept'),
            'is_inertia' => $request->header('X-Inertia') ? true : false
        ]);

        // Check if user role is admin
        if ($user->role === 'admin') {
            Log::info('Admin access granted', [
                'user_id' => $user->_id
            ]);
            return $next($request);
        }

        // Check if user has access through an active department
        if (!$api->isAccessibleByUser($user)) {
            $errorMessage = 'You do not have access to this API. Access is limited based on your active department assignments.';
            
            Log::warning('API access denied', [
                'user_id' => $user->_id,
                'api_id' => $api->_id,
                'reason' => $errorMessage
            ]);

            // Pass error through request attributes
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
            'user_id' => $user->_id,
            'api_id' => $api->_id
        ]);
        
        return $next($request);
    }
}
