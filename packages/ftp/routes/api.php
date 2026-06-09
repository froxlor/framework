<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->name('api.')->group(function () {
    Route::get('nodes/{node}/ftp-service', [Api\Node\FtpServiceController::class, 'show'])
        ->name('nodes.ftp-service.show');
    Route::post('nodes/{node}/ftp-service', [Api\Node\FtpServiceController::class, 'store'])
        ->name('nodes.ftp-service.store');
    Route::put('nodes/{node}/ftp-service', [Api\Node\FtpServiceController::class, 'update'])
        ->name('nodes.ftp-service.update');
    Route::patch('nodes/{node}/ftp-service', [Api\Node\FtpServiceController::class, 'update'])
        ->name('nodes.ftp-service.patch');
    Route::post('nodes/{node}/ftp-service/install', [Api\Node\FtpServiceController::class, 'install'])
        ->name('nodes.ftp-service.install');
    Route::post('nodes/{node}/ftp-service/configure', [Api\Node\FtpServiceController::class, 'configure'])
        ->name('nodes.ftp-service.configure');
    Route::post('nodes/{node}/ftp-service/check', [Api\Node\FtpServiceController::class, 'check'])
        ->name('nodes.ftp-service.check');
    Route::delete('nodes/{node}/ftp-service', [Api\Node\FtpServiceController::class, 'destroy'])
        ->name('nodes.ftp-service.destroy');
});
