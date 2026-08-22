<?php

use Froxlor\Packages\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->name('api.')->group(function () {
    // Must be registered before the packages apiResource below — otherwise its wildcard
    // {package} routes shadow these literal ones.
    Route::get('packages/marketplace-credentials', [Api\MarketplaceCredentialsController::class, 'show'])
        ->name('packages.marketplace-credentials.show');
    Route::put('packages/marketplace-credentials', [Api\MarketplaceCredentialsController::class, 'update'])
        ->name('packages.marketplace-credentials.update');

    Route::apiResource('discovery', Api\DiscoverController::class);
    Route::apiResource('packages', Api\PackageController::class);
    Route::apiResource('repositories', Api\RepositoryController::class);
});
