<?php

use Froxlor\Core\Http\Controllers\Web;
use Froxlor\Core\Http\Middleware\EnsureIsInstalled;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', EnsureIsInstalled::class])->group(function () {
    Route::get('/', [Web\PageController::class, 'index'])
        ->name('overview');

    Route::resource('audit-log', Web\AuditLogController::class);

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::resource('users', Web\UserController::class);
        Route::delete('api-keys/bulk-destroy', [Web\ApiKeyController::class, 'bulkDestroy'])
            ->name('api-keys.bulk-destroy');
        Route::resource('api-keys', Web\ApiKeyController::class)->only(['index', 'create', 'show', 'destroy']);
        Route::resource('roles', Web\RoleController::class);
    });

    Route::prefix('resources')->name('resources.')->group(function () {
        Route::resource('plans', Web\PlanController::class);
        Route::resource('nodes', Web\NodeController::class);
        Route::resource('tenants', Web\TenantController::class);
    });

    Route::get('settings', [Web\SettingController::class, 'index'])
        ->name('settings.index');

    Route::get('tenants/{tenant}', [Web\TenantController::class, 'show'])
        ->name('tenants.show');
    Route::resource('tenants.environments', Web\Tenant\EnvironmentController::class);
    Route::resource('tenants.plans', Web\Tenant\PlanController::class);
    Route::resource('tenants.roles', Web\Tenant\RoleController::class);
    Route::resource('tenants.users', Web\Tenant\UserController::class);
    Route::resource('tenants.audit-log', Web\Tenant\AuditLogController::class)->only(['index']);
});

require __DIR__ . '/init.php';
