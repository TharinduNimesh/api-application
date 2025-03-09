<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        if (file_exists($manifest = public_path('build/manifest.json'))) {
            return md5_file($manifest);
        }

        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'url' => config('app.url'),
            ],
        ]);
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, \Closure $next)
    {
        if ($request->header('X-Inertia') && $request->method() === 'GET') {
            $request->headers->set('X-Inertia-Version', $this->version($request));
        }

        if ($request->attributes->has('api_access_error')) {
            $error = $request->attributes->get('api_access_error');
            return Inertia::render('Error/Forbidden', [
                'status' => $error['status'],
                'message' => $error['message']
            ])->toResponse($request);
        }

        return parent::handle($request, $next);
    }
}
