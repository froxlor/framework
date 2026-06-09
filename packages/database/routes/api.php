<?php

use Froxlor\Database\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->name('api.')->group(function () {
    Route::get('nodes/{node}/database-service', [Api\Node\DatabaseServiceController::class, 'show'])
        ->name('nodes.database-service.show');
    Route::post('nodes/{node}/database-service', [Api\Node\DatabaseServiceController::class, 'store'])
        ->name('nodes.database-service.store');
    Route::put('nodes/{node}/database-service', [Api\Node\DatabaseServiceController::class, 'update'])
        ->name('nodes.database-service.update');
    Route::patch('nodes/{node}/database-service', [Api\Node\DatabaseServiceController::class, 'update'])
        ->name('nodes.database-service.patch');
    Route::post('nodes/{node}/database-service/install', [Api\Node\DatabaseServiceController::class, 'install'])
        ->name('nodes.database-service.install');
    Route::post('nodes/{node}/database-service/configure', [Api\Node\DatabaseServiceController::class, 'configure'])
        ->name('nodes.database-service.configure');
    Route::post('nodes/{node}/database-service/check', [Api\Node\DatabaseServiceController::class, 'check'])
        ->name('nodes.database-service.check');
    Route::delete('nodes/{node}/database-service', [Api\Node\DatabaseServiceController::class, 'destroy'])
        ->name('nodes.database-service.destroy');

    Route::apiResource('tenants.environments.databases', Api\Tenant\Environment\DatabaseController::class)
        ->scoped()
        ->names('tenants.environments.databases');
});
