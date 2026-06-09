<?php

use Froxlor\Core\Http\Middleware\EnsureIsInstalled;
use Froxlor\Packages\Http\Controllers\Web;

Route::middleware(['web', 'auth', EnsureIsInstalled::class])->group(function () {
    Route::post('packages/{package}/install', [Web\PackageController::class, 'install'])
        ->name('packages.install');

    Route::get('packages/{package}/uninstall', [Web\PackageController::class, 'uninstall'])
        ->name('packages.uninstall');

    Route::post('packages/package/upgrade', [Web\PackageController::class, 'upgrade'])
        ->name('packages.packages.upgrade');
    Route::resource('packages', Web\PackageController::class)
        ->names('packages')
        ->only(['index', 'create', 'edit']);

    Route::resource('packages/discovery', Web\DiscoveryController::class)
        ->names('packages.discovery')
        ->only(['index', 'create']);

    Route::resource('packages/updater', Web\UpdaterController::class)
        ->names('packages.updater')
        ->only(['index', 'create']);

    Route::post('packages/repositories/switch', [Web\RepositoryController::class, 'switch'])
        ->name('packages.repositories.switch');
    Route::post('packages/repositories/update', [Web\RepositoryController::class, 'update'])
        ->name('packages.repositories.update');
    Route::resource('packages/repositories', Web\RepositoryController::class)
        ->names('packages.repositories')
        ->only(['index', 'create', 'edit', 'destroy']);
});
