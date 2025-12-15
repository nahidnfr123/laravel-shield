<?php

namespace NahidFerdous\Shield\Services\Auth;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTAuthService extends AuthService
{
    protected $ttl;

    protected $refreshTtl;

    public function __construct(string $guard)
    {
        parent::__construct($guard);

        $this->ttl = (int) config('shield.jwt.ttl', 60);
        $this->refreshTtl = (int) config('shield.jwt.refresh_ttl', 20160);
    }

    public function login(array $credentials): array
    {
        $user = $this->findUserByCredentials($credentials);
        $this->validateUser($user, $credentials['password']);

        // Build claims
        $claims = [
            'roles' => $this->getUserRoles($user),
            'email' => $user->email,
            'name' => $user->name,
        ];

        // Add guard info if using multi-guard
        if ($this->useMultiGuard) {
            $claims['guard'] = $this->guard;
        }

        $token = auth($this->guard)->claims($claims)->attempt($credentials);

        if (! $token) {
            throw new \RuntimeException('Could not create token', 500);
        }

        $user = auth($this->guard)->user();

        $response = [
            'expires_in' => $this->ttl * 60, // in seconds
        ];

        // Only include guard in response if using multi-guard
        if ($this->useMultiGuard) {
            $payload = JWTAuth::setToken($token)->getPayload();
            $response['guard'] = $payload->get('guard');
        }

        return $this->successResponse($user, $token, $response);
    }

    public function logout($user): bool
    {
        try {
            // Invalidate the token (adds to blacklist)
            JWTAuth::invalidate(JWTAuth::getToken());

            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    public function refresh($user): array
    {
        try {
            // Refresh the token - JWT maintains the same claims
            $token = JWTAuth::refresh(JWTAuth::getToken());

            $response = [
                'expires_in' => $this->ttl * 60,
            ];

            // Include guard info if using multi-guard
            if ($this->useMultiGuard) {
                $payload = JWTAuth::setToken($token)->getPayload();
                $response['guard'] = $payload->get('guard');
            }

            return $this->successResponse($user, $token, $response);
        } catch (TokenExpiredException $e) {
            throw new \RuntimeException('Token has expired and cannot be refreshed', 401);
        } catch (JWTException $e) {
            throw new \RuntimeException('Could not refresh token', 500);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }

    public function validate(string $token): bool
    {
        try {
            $user = JWTAuth::setToken($token)->authenticate();

            // If using multi-guard, validate the guard matches
            if ($this->useMultiGuard) {
                $payload = JWTAuth::setToken($token)->getPayload();
                $tokenGuard = $payload->get('guard');

                return $tokenGuard === $this->guard;
            }

            return $user !== null;
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * Generate JWT token with custom claims
     */
    protected function generateToken($user): string
    {
        // Ensure user implements JWTSubject
        if (! $user instanceof \Tymon\JWTAuth\Contracts\JWTSubject) {
            throw new \RuntimeException('User model must implement JWTSubject interface');
        }

        $customClaims = [
            'roles' => $this->getUserRoles($user),
            'email' => $user->email,
            'name' => $user->name,
        ];

        if ($this->useMultiGuard) {
            $customClaims['guard'] = $this->guard;
        }

        return JWTAuth::customClaims($customClaims)->fromUser($user);
    }

    /**
     * Generate refresh token
     */
    protected function generateRefreshToken($user): string
    {
        // Ensure user implements JWTSubject
        if (! $user instanceof \Tymon\JWTAuth\Contracts\JWTSubject) {
            throw new \RuntimeException('User model must implement JWTSubject interface');
        }

        $customClaims = [
            'type' => 'refresh',
        ];

        if ($this->useMultiGuard) {
            $customClaims['guard'] = $this->guard;
        }

        return JWTAuth::customClaims($customClaims)->fromUser($user);
    }

    /**
     * Decode token without validation
     */
    public function decodeToken(string $token)
    {
        try {
            return JWTAuth::setToken($token)->getPayload();
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Get the authenticated user from a token
     */
    public function getUserFromToken(string $token)
    {
        try {
            return JWTAuth::setToken($token)->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Check if the token is blacklisted
     */
    public function isBlacklisted(string $token): bool
    {
        try {
            JWTAuth::setToken($token)->checkOrFail();

            return false;
        } catch (TokenInvalidException $e) {
            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    protected function getTokenType(): string
    {
        return 'Bearer';
    }
}
