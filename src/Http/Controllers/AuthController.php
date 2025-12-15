<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NahidFerdous\Shield\Events\ShieldUserRegisterEvent;
use NahidFerdous\Shield\Http\Requests\ShieldLoginRequest;
use NahidFerdous\Shield\Http\Requests\ShieldUserCreateRequest;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;
use NahidFerdous\Shield\Support\ShieldAuthContext;
use NahidFerdous\Shield\Traits\ApiResponseTrait;
use NahidFerdous\Shield\Traits\HandlesAuthErrors;

class AuthController extends Controller
{
    use ApiResponseTrait, HandlesAuthErrors;

    protected ShieldAuthContext $ctx;

    public function __construct()
    {
        $this->ctx = app(ShieldAuthContext::class);
    }

    /**
     * Handle user login
     */
    public function login(ShieldLoginRequest $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            return $this->success('Login successful', shield($this->ctx->guard)->login($request->validated()));
        } catch (\Exception $e) {
            return $this->handleAuthException($e);
        }
    }

    public function register(ShieldUserCreateRequest $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            $guard = requestGuardResolver();

            $userClass = resolveAuthenticatableClass($guard);
            $model = new $userClass;
            // Use validated() if it's NOT the default CreateUserRequest
            $validatedData = $request->validated();
            if (get_class($request) !== ShieldUserCreateRequest::class) {
                $userData = array_intersect_key($validatedData, array_flip($model->getFillable()));
            } else {
                $userData = $request->only($model->getFillable());
            }

            $user = shield()->register($userData);

            // Fire the UserRegistered event so, package user can listen to the event and perform other actions
            // Fire the UserRegistered event (handles email verification)
            event(new ShieldUserRegisterEvent(user: $user, requestData: $validatedData));
            // laravel default email verification can be sent using this event
            // (new Registered($user));

            return $this->success('Login successful', [
                'user' => $user,
                'guard' => $guard,
            ]);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        shield()->logout($request->user($this->ctx->guard));

        return $this->success('Logout successful');
    }

    /**
     * Refresh authentication token
     */
    public function refresh(Request $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            return $this->success('Token refreshed successfully', shield()->refresh($request->user()));
        } catch (\Exception $e) {
            return $this->failure(
                $e->getMessage() ?: 'Token refresh failed',
                $e->getCode() ?: 401
            );
        }
    }

    /**
     * Get authenticated user information
     */
    public function me(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user($this->ctx->guard);

        return $user
            ? $this->success('me', $user)
            : $this->failure('Unauthenticated', 401);
    }

    /**
     * Verify user email with token
     */
    public function verifyEmail(string $token): \Illuminate\Http\JsonResponse
    {
        $verified = AuthServiceFactory::verification()
            ->verifyEmail($token, $this->ctx);

        return $verified
            ? $this->success('Email verified successfully')
            : $this->failure('Invalid or expired verification token', 400);
    }

    /**
     * Verify user email with token
     */
    public function resendEmailVerificationLink(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $sent = AuthServiceFactory::verification()
            ->resend($request->email, $this->ctx);

        return $this->success(
            'If the email exists, a verification link has been sent'
        );
    }

    public function forgotPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        AuthServiceFactory::password()->sendResetLink($request, $this->ctx);

        return $this->success('If the email exists, a password reset link has been sent');
    }

    public function resetPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $status = AuthServiceFactory::password()->resetPassword($request, $this->ctx);

        return $status === \Password::PASSWORD_RESET
            ? $this->success('Password reset successfully')
            : $this->failure(__($status), 400);
    }
}
