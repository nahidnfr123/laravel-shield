<?php

namespace NahidFerdous\Shield\Services;

class UserService
{
    public function store($data, $guard = null)
    {
        $userClass = resolveAuthenticatableClass($guard);

        // Check if a user exists
        $existing = $userClass::query()->where('email', $data['email'])->first();
        if ($existing) {
            return response(['error' => 1, 'message' => 'User already exists'], 409);
        }
    }
}
