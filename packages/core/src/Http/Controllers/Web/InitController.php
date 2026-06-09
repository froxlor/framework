<?php

namespace Froxlor\Core\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\User;
use Froxlor\Core\Providers\FroxlorCoreServiceProvider;
use Froxlor\Core\Services\Bootstrap\BootstrapService;
use Froxlor\Core\Support\Setting;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class InitController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (Setting::get('core.initialized')) {
            return redirect()->route('login');
        }

        return view('froxlor-core::init.index');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, BootstrapService $bootstrapService): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $bootstrapService->initRootTenant($request->email, $request->first_name, $request->last_name, $request->password);

        event(new Registered($user));

        Auth::login($user);

        return redirect(FroxlorCoreServiceProvider::HOME);
    }
}
