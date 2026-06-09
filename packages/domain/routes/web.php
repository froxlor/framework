<?php

use Froxlor\Core\Http\Middleware\EnsureIsInstalled;
use Froxlor\Domain\Http\Controllers\Web;

Route::middleware(['web', 'auth', EnsureIsInstalled::class])->group(function () {
    Route::prefix('resources')->name('resources.')->group(function () {
        Route::resource('domains', Web\DomainController::class);
    });
    Route::resource('tenants.domains', Web\Tenant\DomainController::class)->only(['index', 'create', 'show', 'edit']);
    Route::resource('tenants.environments.domains', Web\Tenant\Environment\DomainController::class)->only(['show', 'edit']);
});
