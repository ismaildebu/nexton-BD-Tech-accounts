<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;

Route::middleware(['auth', 'verified'])->prefix('system')->name('system.')->group(function (): void {
    
    // Users
    Route::middleware('can-permission:users.manage')
        ->group(function (): void {
            Route::resource('users', UserController::class);
            Route::get('/users/{user}/roles', [UserController::class, 'roleAssignment'])
                ->name('users.roles');
            Route::put('/users/{user}/roles', [UserController::class, 'updateRole'])
                ->name('users.roles.update');
        });

    // Roles
    Route::middleware('can-permission:roles.manage')
        ->group(function (): void {
            Route::resource('roles', RoleController::class);
        });

    // Permissions
    Route::middleware('can-permission:permissions.manage')
        ->group(function (): void {
            Route::get('/permissions', [PermissionController::class, 'index'])
                ->name('permissions.index');
            Route::get('/permissions/create', [PermissionController::class, 'create'])
                ->name('permissions.create');
            Route::post('/permissions', [PermissionController::class, 'store'])
                ->name('permissions.store');
        });
});