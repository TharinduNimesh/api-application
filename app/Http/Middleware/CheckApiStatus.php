<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        $api = $request->route('api');

        // If the API is inactive and the user is not an admin, 
        // return a 403 response

        if (!$api->is_active && $request->user()->role !== 'admin') {
            return response()->json([
                'status' => 403,
                'error' => 'This API is currently inactive'
            ], 403);
        }

        return $next($request);
    }
}
