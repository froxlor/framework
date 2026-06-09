<?php

use Froxlor\Domain\Http\Controllers\Api;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->name('api.')->group(function () {
    Route::apiResource('domains', Api\DomainController::class);
    Route::apiResource('tenants.domains', Api\Tenant\DomainController::class);
    Route::apiResource('tenants.environments.domains', Api\Tenant\Environment\DomainController::class);
});
