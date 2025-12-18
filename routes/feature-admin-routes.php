<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\AdminUserController;

/**
 * Feature Management Routes
 * All routes require super admin authentication
 */
Route::middleware(['auth:sanctum', 'super.admin'])->group(function () {
    
    // Feature Management Routes
    Route::prefix('features')->group(function () {
        Route::get('/', [FeatureController::class, 'index'])->name('features.index');
        Route::post('/', [FeatureController::class, 'store'])->name('features.store');
        Route::get('/{feature}', [FeatureController::class, 'show'])->name('features.show');
        Route::put('/{feature}', [FeatureController::class, 'update'])->name('features.update');
        Route::delete('/{feature}', [FeatureController::class, 'destroy'])->name('features.destroy');
        
        // Feature control routes
        Route::post('/{feature}/enable', [FeatureController::class, 'enable'])->name('features.enable');
        Route::post('/{feature}/disable', [FeatureController::class, 'disable'])->name('features.disable');
        Route::post('/{feature}/toggle', [FeatureController::class, 'toggle'])->name('features.toggle');
        
        // Feature logs
        Route::get('/{feature}/logs', [FeatureController::class, 'logs'])->name('features.logs');
        
        // Platform-specific routes
        Route::get('/platform/{platform}', [FeatureController::class, 'byPlatform'])->name('features.byPlatform');
    });

    // Admin Role Management Routes
    Route::prefix('admin-roles')->group(function () {
        Route::get('/', [AdminRoleController::class, 'index'])->name('admin-roles.index');
        Route::post('/', [AdminRoleController::class, 'store'])->name('admin-roles.store');
        Route::get('/{adminRole}', [AdminRoleController::class, 'show'])->name('admin-roles.show');
        Route::put('/{adminRole}', [AdminRoleController::class, 'update'])->name('admin-roles.update');
        Route::delete('/{adminRole}', [AdminRoleController::class, 'destroy'])->name('admin-roles.destroy');
        
        // Permission management
        Route::post('/{adminRole}/permissions/add', [AdminRoleController::class, 'addPermission'])->name('admin-roles.addPermission');
        Route::post('/{adminRole}/permissions/remove', [AdminRoleController::class, 'removePermission'])->name('admin-roles.removePermission');
        
        // Get users with role
        Route::get('/{adminRole}/users', [AdminRoleController::class, 'users'])->name('admin-roles.users');
    });

    // Admin User Management Routes
    Route::prefix('admin-users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('admin-users.index');
        Route::post('/', [AdminUserController::class, 'store'])->name('admin-users.store');
        Route::get('/{adminUser}', [AdminUserController::class, 'show'])->name('admin-users.show');
        Route::put('/{adminUser}', [AdminUserController::class, 'update'])->name('admin-users.update');
        Route::delete('/{adminUser}', [AdminUserController::class, 'destroy'])->name('admin-users.destroy');
        
        // Status management
        Route::post('/{adminUser}/activate', [AdminUserController::class, 'activate'])->name('admin-users.activate');
        Route::post('/{adminUser}/deactivate', [AdminUserController::class, 'deactivate'])->name('admin-users.deactivate');
        Route::post('/{adminUser}/suspend', [AdminUserController::class, 'suspend'])->name('admin-users.suspend');
        
        // Get by user ID
        Route::get('/user/{userId}', [AdminUserController::class, 'getByUserId'])->name('admin-users.getByUserId');
    });
});

/**
 * Public Feature Check Routes
 * These routes can be accessed by any authenticated user to check feature status
 */
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/features/check/{slug}', [FeatureController::class, 'isEnabled'])->name('features.isEnabled');
    Route::get('/admin-users/check/{userId}', [AdminUserController::class, 'isAdmin'])->name('admin-users.isAdmin');
});
