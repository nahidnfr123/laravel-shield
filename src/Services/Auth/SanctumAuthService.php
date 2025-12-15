<?php

namespace NahidFerdous\Shield\Services\Auth;

use Exception;

class SanctumAuthService extends AuthService
{
    /**
     * @throws Exception
     */
    public function login(array $credentials): array
    {
        $user = $this->findUserByCredentials($credentials);
        $this->validateUser($user, $credentials['password']);

        $this->deletePreviousTokens($user);

        $roles = $this->getUserRoles($user);
        $tokenName = $this->getTokenName();

        if ($this->useMultiGuard) {
            // Store guard info in abilities
            $abilities = array_merge($roles, ["guard:{$this->guard}"]);
            $token = $user->createToken($tokenName, $abilities)->plainTextToken;
        } else {
            $token = $user->createToken($tokenName, $roles)->plainTextToken;
        }

        return $this->successResponse($user, $token);
    }

    public function logout($user): bool
    {
        if ($this->useMultiGuard) {
            // Delete tokens for this specific guard
            $user->tokens()->where('name', $this->getTokenName())->delete();
        } else {
            // Delete current token or all tokens based on config
            $currentToken = $user->currentAccessToken();
            if ($currentToken) {
                $currentToken->delete();
            } else {
                // Fallback: delete all tokens with our token name
                $user->tokens()->where('name', $this->getTokenName())->delete();
            }
        }

        return true;
    }

    public function refresh($user): array
    {
        $tokenName = $this->getTokenName();

        if ($this->useMultiGuard) {
            // Delete old tokens for this guard
            $user->tokens()->where('name', $tokenName)->delete();

            $roles = $this->getUserRoles($user);
            $abilities = array_merge($roles, ["guard:{$this->guard}"]);
            $token = $user->createToken($tokenName, $abilities)->plainTextToken;
        } else {
            // Delete previous tokens if configured
            $this->deletePreviousTokens($user);

            $roles = $this->getUserRoles($user);
            $token = $user->createToken($tokenName, $roles)->plainTextToken;
        }

        return $this->successResponse($user, $token);
    }

    public function validate(string $token): bool
    {
        return ! empty($token);
    }

    protected function getTokenType(): string
    {
        return 'Bearer';
    }

    /**
     * Get the appropriate token name based on multi-guard setting
     */
    protected function getTokenName(): string
    {
        return $this->useMultiGuard
            ? "shield-{$this->guard}-token"
            : 'shield-token';
    }
}
