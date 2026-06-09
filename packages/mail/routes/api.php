<?php

use Froxlor\Mail\Http\Controllers\Api;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->name('api.')->group(function () {
    Route::apiResource('tenants.environments.domains.mail', Api\Tenant\Environment\MailController::class);
    Route::apiResource('tenants.environments.domains.mail.account', Api\Tenant\Environment\MailAccountController::class)->except(['index']);
});
