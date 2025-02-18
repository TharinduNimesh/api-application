<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ProfileController;
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

    // Users Route
    Route::get('/users', function () {
        return Inertia::render('Users/Index', [
            'users' => User::select('id', 'name', 'email', 'role', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
        ]);
    })->middleware('isAdmin')->name('users.index');

    // API Routes
    Route::prefix('api')->name('api.')
        ->group(function () {
            Route::get('/list', [ApiController::class, 'index'])->name('list');
            
            Route::middleware(['isAdmin'])->group(function () {
                Route::get('/create', function () {
                    return Inertia::render('Api/Create');
                })->name('create');
                Route::post('/apis', [ApiController::class, 'create'])->name('store');
                Route::patch('/{api}/archive', [ApiController::class, 'archive'])->name('archive');
                Route::patch('/{api}/activate', [ApiController::class, 'activate'])->name('activate');
                Route::delete('/{api}', [ApiController::class, 'destroy'])->name('destroy');
                Route::delete('/{api}/endpoints/{endpoint}', [ApiController::class, 'deleteEndpoint'])
                    ->name('endpoints.delete');
            });
            Route::get('/{api}', [ApiController::class, 'show'])->name('show');
            
            // New route to call external endpoints via the backend
            Route::post('/{api}/call-endpoint', [ApiController::class, 'callEndpoint'])->name('call-endpoint');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__ . '/auth.php';
