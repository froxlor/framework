<?php

use Froxlor\Core\Http\Middleware\EnsureIsInstalled;
use Froxlor\UI\Http\Controllers\Web;

Route::middleware(['web', 'auth', EnsureIsInstalled::class])->name('ui.')->group(function () {
    Route::resource('ui/appearance', Web\AppearanceController::class)
        ->names('appearance')
        ->only(['index']);
});
