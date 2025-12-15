<?php

namespace NahidFerdous\Shield\Services\Auth;

use NahidFerdous\Shield\Models\EmailVerificationToken;
use NahidFerdous\Shield\Support\ShieldAuthContext;

class ShieldVerificationService
{
    public function verifyEmail(string $token, ShieldAuthContext $ctx): bool
    {
        $verification = EmailVerificationToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $verification) {
            return false;
        }

        $user = $ctx->modelClass::find($verification->user_id);

        if (! $user) {
            return false;
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $verification->delete();

        return true;
    }

    public function resend(string $email, ShieldAuthContext $ctx): bool
    {
        $user = $ctx->modelClass::where('email', $email)->first();

        if (! $user || $user->email_verified_at) {
            return false;
        }

        app(AuthServiceFactory::class)
            ->make()
            ->sendVerificationEmail($user);

        return true;
    }
}
