<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MobileIntegrationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Raíz: siempre muestra el login
Route::get('/', [LoginController::class, 'create'])->name('root.login');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->name('logout')
    ->middleware('auth');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class)
        ->middleware('can:users.view');

    Route::resource('projects', ProjectController::class)
        ->middleware('can:projects.view');

    Route::resource('mobile-integrations', MobileIntegrationController::class)
        ->parameters(['mobile-integrations' => 'mobileIntegration'])
        ->middleware('can:mobile_integrations.view');

    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index')
        ->middleware('can:logs.view');
});
