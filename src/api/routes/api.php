<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TenancyController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Every route here is prefixed with /api/v1 (bootstrap/app.php).
// Permissions use the `recurso.accion` convention and are seeded by
// RolePermissionSeeder; guard routes with `permission:<name>`.

// Which context the SPA runs in (tenant / central); public.
Route::get('/tenancy', TenancyController::class);

// --- Auth (guest) ---
Route::middleware('guest')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1');
    Route::post('/auth/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');
});

Route::middleware('auth:sanctum')->group(function () {
    // --- Auth (session) ---
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'changePassword']);

    // --- Users & roles ---
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
    });
    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/users', [UserController::class, 'store']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::put('/users/{user}/roles', [UserController::class, 'syncRoles']);
        Route::put('/users/{user}/password', [UserController::class, 'setPassword']);
    });

    // --- Attachments (whitelisted parents + permissions in config/attachments.php) ---
    Route::get('/attachments/{type}/{id}', [AttachmentController::class, 'index'])->whereNumber('id');
    Route::post('/attachments/{type}/{id}', [AttachmentController::class, 'store'])->whereNumber('id');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download']);
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);

    // --- Platform: organizations (super admins, central domain only) ---
    Route::middleware('super-admin')->group(function () {
        Route::get('/tenants', [TenantController::class, 'index']);
        Route::post('/tenants', [TenantController::class, 'store']);
        Route::get('/tenants/{tenant}', [TenantController::class, 'show']);
        Route::patch('/tenants/{tenant}', [TenantController::class, 'update']);
    });

    // --- System: background activity, schedule, commands ---
    Route::middleware('permission:system.view')->prefix('system')->group(function () {
        Route::get('/activity', [ActivityController::class, 'index']);
        Route::get('/activity/stats', [ActivityController::class, 'stats']);
        Route::get('/activity/{run}', [ActivityController::class, 'show'])->whereNumber('run');
        Route::get('/schedule', [ActivityController::class, 'schedule']);
        Route::get('/commands', [ActivityController::class, 'commands']);
    });
});
