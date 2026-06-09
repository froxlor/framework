<?php

use Froxlor\Auth\Http\Controllers\Web;
use Froxlor\Core\Http\Middleware\EnsureIsInstalled;

Route::middleware(['web', EnsureIsInstalled::class])->group(function () {
    Route::middleware(['guest'])->group(function () {
        Route::get('login', [Web\AuthenticatedSessionController::class, 'create'])
            ->name('login');

        Route::post('login', [Web\AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [Web\PasswordResetLinkController::class, 'create'])
            ->name('password.request');

        Route::post('forgot-password', [Web\PasswordResetLinkController::class, 'store'])
            ->name('password.email');

        Route::get('reset-password/{token}', [Web\NewPasswordController::class, 'create'])
            ->name('password.reset');

        Route::post('reset-password', [Web\NewPasswordController::class, 'store'])
            ->name('password.store');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('verify-email', Web\EmailVerificationPromptController::class)
            ->name('verification.notice');

        Route::get('verify-email/{id}/{hash}', Web\VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::post('email/verification-notification', [Web\EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::get('confirm-password', [Web\ConfirmablePasswordController::class, 'show'])
            ->name('password.confirm');

        Route::post('confirm-password', [Web\ConfirmablePasswordController::class, 'store']);

        Route::put('password', [Web\PasswordController::class, 'update'])->name('password.update');

        Route::any('logout', [Web\AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });
});
