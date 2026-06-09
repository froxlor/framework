<?php

use Froxlor\Core\Http\Middleware\EnsureIsInstalled;
use Froxlor\Ftp\Http\Controllers\Web;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', EnsureIsInstalled::class])->group(function () {
    Route::prefix('resources/nodes/{node}')->name('resources.nodes.')->group(function () {
        Route::get('ftp-service', [Web\Node\FtpServiceController::class, 'show'])
            ->name('ftp-service.show');
        Route::get('ftp-service/create', [Web\Node\FtpServiceController::class, 'create'])
            ->name('ftp-service.create');
        Route::get('ftp-service/edit', [Web\Node\FtpServiceController::class, 'edit'])
            ->name('ftp-service.edit');
        Route::post('ftp-service/install', [Web\Node\FtpServiceController::class, 'install'])
            ->name('ftp-service.install');
        Route::post('ftp-service/configure', [Web\Node\FtpServiceController::class, 'configure'])
            ->name('ftp-service.configure');
        Route::post('ftp-service/check', [Web\Node\FtpServiceController::class, 'check'])
            ->name('ftp-service.check');
    });
});
