<?php

namespace Froxlor\Auth\Http\Controllers\Web;

use Froxlor\Core\Providers\FroxlorCoreServiceProvider;
use Froxlor\Auth\Http\Controllers\Controller;
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
            : view('froxlor-auth::verify-email');
    }
}
