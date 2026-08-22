<?php

namespace Froxlor\Core\Http\Controllers\Web\Auth;

use Froxlor\Core\Providers\FroxlorCoreServiceProvider;
use Froxlor\Core\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(FroxlorCoreServiceProvider::HOME)
            : view('froxlor-core::auth.verify-email');
    }
}
