<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use NahidFerdous\Shield\Events\ShieldUserRegisterEvent;
use NahidFerdous\Shield\Http\Requests\ShieldUserCreateRequest;
use NahidFerdous\Shield\Http\Requests\ShieldLoginRequest;
use NahidFerdous\Shield\Models\EmailVerificationToken;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;
use NahidFerdous\Shield\Traits\ApiResponseTrait;
use NahidFerdous\Shield\Traits\HandlesAuthErrors;

class AuthController extends Controller
{
    use ApiResponseTrait, HandlesAuthErrors;

    /**
     * Handle user login
     */
    public function login(ShieldLoginRequest $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            $result = shield()->login($request->validated());

            return $this->success('Login successful', $result);
        } catch (\Exception $e) {
            return $this->handleAuthException($e);
            // return $this->failure($e->getMessage(), $e->getCode());
        }
    }

    public function register(ShieldLoginRequest $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            $guard = requestGuardResolver();

            $userClass = resolveAuthenticatableClass($guard);
            $model = new $userClass;
            // Use validated() if it's NOT the default CreateUserRequest
            if (get_class($request) !== ShieldUserCreateRequest::class) {
                $validatedData = $request->validated();
                $userData = array_intersect_key($validatedData, array_flip($model->getFillable()));
            } else {
                $userData = $request->only($model->getFillable());
            }

            $user = shield()->register($userData);

            // Fire the UserRegistered event so, package user can listen to the event and perform other actions
            // Fire the UserRegistered event (handles email verification)
            // event(new ShieldUserRegisterEvent($user, $request->only(['name', 'email', 'ip' => $request->ip()])));
            event(new ShieldUserRegisterEvent($user, [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'ip' => $request->ip(),
            ]));
            // laravel default email verification can be sent using this event
            // (new Registered($user));

            $user->guard = $guard;

            return $this->success('Login successful', $user);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            shield()->logout($request->user());

            return $this->success('Logout successful');
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Refresh authentication token
     */
    public function refresh(Request $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            return $this->success('Token refreshed successfully', shield()->refresh($request->user()));
        } catch (\Exception $e) {
            return $this->failure('Token refresh failed' || $e->getMessage(), 401 || $e->getCode());
        }
    }

    /**
     * Get authenticated user information
     */
    public function me(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user(requestGuardResolver());
        if (! $user) {
            return $this->failure('Unauthenticated', 401);
        }

        return $this->success('me', auth(requestGuardResolver())->user());
    }

    /**
     * Verify user email with token
     */
    public function verifyEmail(string $token): \Illuminate\Http\JsonResponse
    {
        $verification = EmailVerificationToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $verification) {
            return $this->failure('Invalid or expired verification token', 400);
        }

        $userClass = resolveAuthenticatableClass();
        $user = $userClass::find($verification->user_id);

        if (! $user) {
            return $this->failure('User not found', 404);
        }

        if ($user->email_verified_at) {
            $verification->delete();

            return $this->success('Email already verified');
        }
        $user->email_verified_at = now();
        $user->save();
        $verification->delete();

        return $this->success('Email verified successfully');
    }

    /**
     * Verify user email with token
     */
    public function resendEmailVerificationLink(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $userClass = resolveAuthenticatableClass();
        $user = $userClass::where('email', $request->email)->firstOrFail();

        if ($user->email_verified_at) {
            return $this->failure('Email already verified', 400);
        }

        $authService = AuthServiceFactory::make();
        $authService->sendVerificationEmail($user);

        return $this->success('Verification link sent to your email');
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return $this->success('Password reset link sent to your email');
            }

            return $this->failure('Unable to send reset link', 400);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage() ?? 'Unable to send reset link', 400);
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success('Password reset successfully');
        }

        return $this->failure(__($status), 400);
    }
}
