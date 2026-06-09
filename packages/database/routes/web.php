<?php

use Froxlor\Core\Http\Middleware\EnsureIsInstalled;
use Froxlor\Database\Http\Controllers\Web;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', EnsureIsInstalled::class])->group(function () {
    Route::prefix('resources/nodes/{node}')->name('resources.nodes.')->group(function () {
        Route::get('database-service', [Web\Node\DatabaseServiceController::class, 'show'])
            ->name('database-service.show');
        Route::get('database-service/create', [Web\Node\DatabaseServiceController::class, 'create'])
            ->name('database-service.create');
        Route::get('database-service/edit', [Web\Node\DatabaseServiceController::class, 'edit'])
            ->name('database-service.edit');
        Route::post('database-service/install', [Web\Node\DatabaseServiceController::class, 'install'])
            ->name('database-service.install');
        Route::post('database-service/configure', [Web\Node\DatabaseServiceController::class, 'configure'])
            ->name('database-service.configure');
        Route::post('database-service/check', [Web\Node\DatabaseServiceController::class, 'check'])
            ->name('database-service.check');
    });

    Route::resource('tenants.environments.databases', Web\Tenant\Environment\DatabaseController::class)
        ->scoped()
        ->only(['index', 'create', 'show', 'edit'])
        ->names('tenants.environments.databases');
});
