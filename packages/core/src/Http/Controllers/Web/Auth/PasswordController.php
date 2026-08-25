<?php

namespace Froxlor\Core\Http\Controllers\Web\Auth;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        Audit::info('user "' . $user->email . '" password updated', $user->tenants()->first(), context: [
            'user_id' => $user->id,
        ]);

        return back()->with('status', 'password-updated');
    }
}
