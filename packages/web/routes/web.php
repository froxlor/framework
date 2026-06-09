<?php

use Froxlor\Core\Http\Middleware\EnsureIsInstalled;

Route::middleware(['web', 'auth', EnsureIsInstalled::class])->group(function () {

});
