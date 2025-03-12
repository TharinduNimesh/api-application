<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ApiImportController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApiStatsController;
use App\Models\Api;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Admin only routes
    Route::middleware(['isAdmin'])->group(function () {
        // Users Route
        Route::get('/users', function () {
            return Inertia::render('Users/Index', [
                'users' => User::select('id', 'name', 'email', 'role', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->get()
            ]);
        })->name('users.index');

        // Department Routes
        Route::group(['prefix' => 'departments', 'as' => 'departments.'], function () {
            Route::get('/', [DepartmentController::class, 'index'])->name('index');
            Route::post('/', [DepartmentController::class, 'store'])->name('store');
            Route::get('/{department}', [DepartmentController::class, 'show'])->name('show');
            Route::patch('/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('toggle-status');
            Route::patch('/{department}/api/{apiId}/rate-limit', [DepartmentController::class, 'updateRateLimit'])->name('update-rate-limit');
            Route::post('/{department}/assign-user', [DepartmentController::class, 'assignUser'])->name('assign-user');
            Route::delete('/{department}/user/{userId}', [DepartmentController::class, 'removeUser'])->name('remove-user');
            Route::post('/{department}/assign-api', [DepartmentController::class, 'assignApi'])->name('assign-api');
            Route::delete('/{department}/api/{apiId}', [DepartmentController::class, 'removeApi'])->name('remove-api');
        });
    });

    // API Routes
    Route::prefix('api')->name('api.')
        ->group(function () {
            Route::get('/list', [ApiController::class, 'index'])->name('list');

            Route::middleware(['isAdmin'])->group(function () {
                Route::get('/create', function () {
                    return Inertia::render('Api/Create');
                })->name('create');

                // Update edit route to include endpoints
                Route::get('/{api}/edit', function (Api $api) {
                    return Inertia::render('Api/Edit', [
                        'api' => $api->load(['endpoints', 'endpoints.parameters']) // Load the endpoints relationship
                    ]);
                })->name('edit');

                Route::post('/apis', [ApiController::class, 'create'])->name('store');
                Route::patch('/{api}/archive', [ApiController::class, 'archive'])->name('archive');
                Route::patch('/{api}/activate', [ApiController::class, 'activate'])->name('activate');
                Route::patch('/{api}', [ApiController::class, 'update'])->name('update');
                Route::delete('/{api}', [ApiController::class, 'destroy'])->name('destroy');
                Route::delete('/{api}/endpoints/{endpoint}', [ApiController::class, 'deleteEndpoint'])
                    ->name('endpoints.delete');
                Route::patch('/{api}/endpoints/{endpoint}', [ApiController::class, 'updateEndpoint'])
                    ->name('endpoints.update');
            });

            // Add api.access middleware to protect the show route
            Route::get('/{api}', [ApiController::class, 'show'])
                ->middleware(['web', 'api.status', 'api.access'])
                ->name('show');

            // New route to call external endpoints via the backend
            Route::post('/{api}/call-endpoint', [RequestController::class, 'callEndpoint'])
                ->middleware(['api.status', 'api.access', 'api.rateLimit'])
                ->name('call-endpoint');
                
            // New route for testing draft (unsaved) endpoints
            Route::post('/test-draft-endpoint', [RequestController::class, 'testDraftEndpoint'])
                ->name('test-draft-endpoint');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
    Route::post('/api/import', [ApiImportController::class, 'import'])->name('api.import');
    Route::get('/api/stats/{apiId}', [ApiStatsController::class, 'getStats'])->name('api.stats');
});

require __DIR__ . '/auth.php';
