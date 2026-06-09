<?php

use Froxlor\UI\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->name('api.')->group(function () {
    Route::apiResource('ui/appearance', Api\AppearanceController::class)
        ->names('ui.appearance')
        ->only(['index', 'store']);
});
