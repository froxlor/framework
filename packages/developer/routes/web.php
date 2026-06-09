<?php

use Froxlor\Core\Http\Middleware\EnsureIsInstalled;
use Froxlor\Developer\Http\Controllers\Web;

Route::middleware(['web', 'auth', EnsureIsInstalled::class])->name('developers.')->prefix('developers')->group(function () {
    Route::get('', [Web\DeveloperController::class, 'index'])
        ->name('index');

    Route::get('docs/{folder}/{page}', [Web\DeveloperController::class, 'docs'])
        ->name('docs');
});
