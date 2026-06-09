<?php

use Froxlor\Core\Http\Controllers\Web\InitController;

Route::middleware(['web', 'guest'])->group(function () {
    Route::resource('init', InitController::class)
        ->only(['index', 'store']);
});
