<?php

namespace NahidFerdous\Shield\Services\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use NahidFerdous\Shield\Mail\VerifyEmailMail;
use NahidFerdous\Shield\Models\EmailVerificationToken;
use NahidFerdous\Shield\Models\Role;

abstract class AuthService
{
    protected string $userClass;

    protected bool $useMultiGuard;

    public string $guard;

    public function __construct(string $guard)
    {
        $this->guard = $guard;
        $this->useMultiGuard = config('shield.multi-guard', false);
        $this->userClass = resolveAuthenticatableClass($guard);
    }

    /**
     * Authenticate user and return token/credentials
     */
    abstract public function login(array $credentials): array;

    /**
     * Register a new user
     */
    public function register(array $data)
    {
        $userClass = $this->userClass;

        if ($userClass::where('email', $data['email'])->exists()) {
            return response(['error' => 1, 'message' => 'User already exists'], 409);
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (config('shield.auth.check_verified', false)) {
            $data['email_verified_at'] = null;
        }

        $user = $userClass::create($data);

        if ($data['roles'] || $data['role']) {
            $roles = $data['roles'] ?? $data['role'];
            $roles = is_array($roles) ? $roles : [$roles];

            $existingRoles = Role::whereIn('id', $roles)->pluck('id')->toArray();
            if (! empty($existingRoles)) {
                $user->roles()->sync($existingRoles);
            }
        } else {
            $defaultRoleSlug = config('shield.default_user_role_slug', 'user');
            $role = Role::where('slug', $defaultRoleSlug)->first();

            if ($role) {
                $user->roles()->attach($role);
            }
        }

        return $user;
    }

    /**
     * Logout user
     */
    abstract public function logout($user): bool;

    /**
     * Refresh token
     */
    abstract public function refresh($user): array;

    /**
     * Validate token
     */
    abstract public function validate(string $token): bool;

    /**
     * Find user by credentials
     */
    protected function findUserByCredentials(array $credentials)
    {
        $credentialField = config('shield.auth.login.credential_field', 'email');

        if (str_contains($credentialField, '|')) {
            $fields = explode('|', $credentialField);
            $user = null;

            foreach ($fields as $field) {
                if (isset($credentials[$field])) {
                    $user = $this->userClass::where($field, $credentials[$field])->first();
                    if ($user) {
                        break;
                    }
                }
            }

            if (! $user && isset($credentials['login'])) {
                foreach ($fields as $field) {
                    $user = $this->userClass::where($field, $credentials['login'])->first();
                    if ($user) {
                        break;
                    }
                }
            }
        } else {
            $loginValue = $credentials[$credentialField] ?? $credentials['login'] ?? null;
            $user = $loginValue ? $this->userClass::where($credentialField, $loginValue)->first() : null;
        }

        return $user;
    }

    /**
     * Validate user credentials
     */
    protected function validateUser($user, string $password): void
    {
        if (! $this->validateCredentials($user, $password)) {
            throw new \RuntimeException('Invalid credentials', 401);
        }

        if (! $this->userIsVerified($user)) {
            throw new \RuntimeException('Account not verified', 403);
        }
    }

    /**
     * Validate credentials
     */
    protected function validateCredentials($user, string $password): bool
    {
        if (! $user) {
            return false;
        }

        return Hash::check($password, $user->password);
    }

    /**
     * Check if user is verified
     */
    protected function userIsVerified($user): bool
    {
        if (! config('shield.auth.check_verified', false)) {
            return true;
        }

        $verificationField = config('shield.auth.verification_field', 'email_verified_at');

        return (bool) ($user->{$verificationField} ?? false);
    }

    /**
     * Get user roles
     */
    protected function getUserRoles($user): array
    {
        return $user->roles->pluck('slug')->all();
    }

    /**
     * Delete previous tokens if configured
     */
    protected function deletePreviousTokens($user): void
    {
        if (config('shield.delete_previous_access_tokens_on_login', false)) {
            $user->tokens()->delete();
        }
    }

    /**
     * Format success response
     */
    protected function successResponse($user, string $token, array $extra = []): array
    {
        return array_merge([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'token' => $token,
            'token_type' => $this->getTokenType(),
        ], $extra);
    }

    /**
     * Get token type
     */
    abstract protected function getTokenType(): string;

    /**
     * Send verification email to user
     */
    public function sendVerificationEmail($user): void
    {
        // Delete any existing tokens for this user
        EmailVerificationToken::where('user_id', $user->id)->delete();

        // Generate new token
        $token = Str::random(64);
        $expiresAt = now()->addHours(config('shield.emails.verify_email.expiration', 24));

        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        // Generate verification URL
        $url = (string) config('shield.emails.verify_email.redirect_url', url(config('shield.route_prefix').'/verify-email'));
        $redirectUrl = $url.'?token='.$token;

        // Send email
        Mail::to($user->email)->send(new VerifyEmailMail($user, $redirectUrl));
    }
}
