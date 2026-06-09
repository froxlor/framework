<?php

use Froxlor\Packages\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->name('api.')->group(function () {
    Route::apiResource('discovery', Api\DiscoverController::class);
    Route::apiResource('packages', Api\PackageController::class);
    Route::apiResource('repositories', Api\RepositoryController::class);
});
