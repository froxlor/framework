<?php

use Froxlor\Core\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->name('api.')->group(function () {
    Route::apiResource('audit-log', Api\AuditLogController::class)->only(['index']);

    Route::apiResource('nodes', Api\NodeController::class);
    Route::apiResource('users', Api\UserController::class);
    Route::apiResource('api-keys', Api\ApiKeyController::class)->only(['index', 'store', 'show', 'destroy']);

    Route::get('settings/{resource?}/{resource_id?}', [Api\SettingsController::class, 'index'])
        ->whereUlid('resource_id')
        ->name('settings.index');
    Route::post('settings/{resource?}/{resource_id?}', [Api\SettingsController::class, 'store'])
        ->whereUlid('resource_id')
        ->name('settings.store');

    Route::apiResource('tenants', Api\TenantController::class);
    Route::apiResource('tenants.audit-log', Api\Tenant\AuditLogController::class)->only(['index']);
    Route::apiResource('tenants.nodes', Api\Tenant\NodeController::class);
    Route::apiResource('tenants.environments', Api\Tenant\EnvironmentController::class);
    Route::apiResource('tenants.environments.audit-log', Api\Tenant\Environment\AuditLogController::class)->only(['index']);
    Route::apiResource('tenants.environments.users', Api\Tenant\Environment\UserController::class);
    Route::apiResource('tenants.environments.plans', Api\Tenant\Environment\PlansController::class);
    Route::apiResource('tenants.users', Api\Tenant\UserController::class);
    Route::apiResource('tenants.plans', Api\Tenant\PlanController::class);
    Route::apiResource('tenants.plans.resources', Api\Tenant\Plan\PlanResourceController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('tenants.roles', Api\Tenant\RoleController::class);
    Route::apiResource('tenants.roles.permissions', Api\Tenant\Role\RolePermissionController::class)->only(['index', 'store', 'destroy']);

    Route::apiResource('plans', Api\PlanController::class);
    Route::apiResource('plans.resources', Api\Plan\PlanResourceController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('roles/permissions', Api\PermissionController::class)->only(['index'])->names([
        'index' => 'roles.permissions.available',
    ]);
    Route::apiResource('roles', Api\RoleController::class);
    Route::apiResource('roles.permissions', Api\Role\RolePermissionController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('roles.users', Api\Role\UserController::class)->only(['index']);

    Route::apiResource('resources', Api\ResourceController::class);

//    Route::get('user', [Api\UserController::class, 'showCurrent'])->name('users.show-current');
//    Route::put('user', [Api\UserController::class, 'updateCurrent'])->name('users.update-current');
//    Route::apiResource('users', Api\UserController::class);
});
