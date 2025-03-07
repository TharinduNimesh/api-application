<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->trustProxies(at: [
            '*',
        ]);

        $middleware->alias([
            'isAdmin' => \App\Http\Middleware\IsAdmin::class,
            'api.access' => \App\Http\Middleware\CheckApiAccess::class,
            'api.status' => \App\Http\Middleware\CheckApiStatus::class,
            'api.rateLimit' => \App\Http\Middleware\CheckRateLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (!app()->environment(['local', 'testing'])) {
                $statusCode = $response->getStatusCode();
                
                if ($statusCode === 404) {
                    return Inertia::render('Error/NotFound', [
                        'status' => $statusCode,
                        'message' => $exception->getMessage() ?: "Sorry, we couldn't find the page you're looking for."
                    ])->toResponse($request)
                    ->setStatusCode($statusCode);
                }

                if ($statusCode === 403) {
                    return Inertia::render('Error/Forbidden', [
                        'status' => $statusCode,
                        'message' => $exception->getMessage() ?: 'You do not have permission to access this resource.'
                    ])->toResponse($request)
                    ->setStatusCode($statusCode);
                }
            }

            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => 'The page expired, please try again.',
                ]);
            }

            return $response;
        });
    })->create();
