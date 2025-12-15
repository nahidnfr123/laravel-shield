<?php

namespace NahidFerdous\Shield\Services\Auth;

class PassportAuthService extends AuthService
{
    /**
     * @throws \Exception
     *                    Note: For Passport with scopes, you need to define them in AuthServiceProvider:
     *
     * public function boot()
     * {
     *     $this->registerPolicies();
     *
     *     Passport::tokensCan([
     *         'admin' => 'Admin access',
     *         'user' => 'User access',
     *         'moderator' => 'Moderator access',
     *     ]);
     *
     *     Passport::setDefaultScope(['user']);
     * }
     */
    public function login(array $credentials): array
    {
        $user = $this->findUserByCredentials($credentials);
        $this->validateUser($user, $credentials['password']);

        $this->deletePreviousTokens($user);

        $tokenName = $this->getTokenName();

        // Create personal access token WITHOUT scopes
        // Passport doesn't support custom metadata like Sanctum abilities
        $tokenResult = $user->createToken($tokenName);
        $token = $tokenResult->accessToken;

        return $this->successResponse($user, $token, [
            'expires_at' => $tokenResult->token->expires_at,
        ]);
    }

    public function logout($user): bool
    {
        if ($this->useMultiGuard) {
            // Revoke tokens with this guard's name
            $user->tokens()
                ->where('name', $this->getTokenName())
                ->each(fn ($token) => $token->revoke());

            return true;
        }

        $token = $user->token();
        if ($token) {
            $token->revoke();

            return true;
        }

        return false;
    }

    public function refresh($user): array
    {
        $tokenName = $this->getTokenName();

        if ($this->useMultiGuard) {
            // Revoke old tokens for this guard
            $user->tokens()
                ->where('name', $tokenName)
                ->each(fn ($token) => $token->revoke());
        } else {
            // Revoke all tokens
            $user->tokens()->each(fn ($token) => $token->revoke());
        }

        // Create new token
        $tokenResult = $user->createToken($tokenName);
        $token = $tokenResult->accessToken;

        return $this->successResponse($user, $token, [
            'expires_at' => $tokenResult->token->expires_at,
        ]);
    }

    public function validate(string $token): bool
    {
        $tokenModel = Laravel\Passport\Token::where('id', $token)->first();

        if (! $tokenModel) {
            return false;
        }

        return ! $tokenModel->revoked && $tokenModel->expires_at->isFuture();
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
