<?php

namespace NahidFerdous\Shield\Services\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use NahidFerdous\Shield\Support\ShieldAuthContext;

class ShieldPasswordService
{
    public function sendResetLink(Request $request, ShieldAuthContext $ctx): string
    {
        // Do NOT leak user existence
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        return Password::broker($ctx->broker)->sendResetLink(
            $request->only('email')
        );
    }

    public function resetPassword(Request $request, ShieldAuthContext $ctx): string
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        return Password::broker($ctx->broker)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );
    }
}
