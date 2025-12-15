<?php

// src/helpers.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NahidFerdous\Shield\Services\Auth\AuthService;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;

if (! function_exists('shield')) {
    /**
     * Get Shield auth service for a specific guard
     */
    function shield(?string $guard = null): AuthService
    {
        return AuthServiceFactory::make(guard: $guard);
    }
}

if (! function_exists('shieldDefaultGuard')) {
    function shieldDefaultGuard(): string
    {
        $defaultGuard = config('shield.default_guard');
        if ($defaultGuard) {
            return $defaultGuard;
        }

        $driver = config('shield.auth_driver', 'sanctum');

        return match ($driver) {
            'jwt', 'passport' => 'api',
            default => 'web',
        };
    }
}

// if (! function_exists('resolveAuthenticatableClass')) {
//    function resolveAuthenticatableClass($guard = null): string
//    {
//        $guard = $guard ?? shieldDefaultGuard();
//
//        if ($guard) {
//            $provider = config("auth.guards.{$guard}.provider");
//            if ($provider) {
//                return config("auth.providers.{$provider}.model", 'App\\Models\\User');
//            }
//            info('No provider found for guard: '.$guard.'. Please check your config/auth.php file.');
//        }
//
//        return config('auth.providers.users.model', 'App\\Models\\User');
//    }
// }

// if (! function_exists('requestGuardResolver')) {
//    function requestGuardResolver(): string
//    {
//        if (! config('shield.multi-guard', false)) {
//            return shieldDefaultGuard();
//        }
//
//        // Get the requested guard from header or request parameter
//        $requestedGuard = request()->header('x-guard') ?? request('guard');
//
//        // Validate if the guard exists in available_guards
//        if ($requestedGuard) {
//            if (array_key_exists($requestedGuard, config('shield.available_guards', []))) {
//                return $requestedGuard;
//            }
//
//            Log::error("Requested guard '$requestedGuard' not found in available_guards config.");
//        }
//
//        // Fall back to default guard
//        return shieldDefaultGuard();
//    }
// }

// function getUserGuard($user = null): int|string|null
// {
//    $user ??= auth(resolveAuthenticatableClass())->user();
//
//    if ($user) {
//        foreach (config('shield.available_guards') as $guard => $defaultGuard) {
//            if (Auth::guard($guard)->check() && Auth::guard($guard)->user()->id === $user->id) {
//                return $guard;
//            }
//        }
//    }
//
//    return null; // no guard found
// }

if (! function_exists('requestGuardResolver')) {
    /**
     * Resolve the guard from the current request
     */
    function requestGuardResolver(?Request $request = null): string
    {
        $request = $request ?? request();

        // Priority 1: Check if guard was set by middleware in request attributes
        $guard = $request->attributes->get('shield_guard');
        if ($guard) {
            return $guard;
        }

        // Priority 2: Check query parameter
        if ($request->has('guard')) {
            $guardParam = $request->input('guard');
            $availableGuards = array_keys(config('shield.available_guards', []));

            if (in_array($guardParam, $availableGuards, true)) {
                $request->attributes->set('shield_guard', $guardParam);

                return $guardParam;
            }
        }

        // Check if multi-guard is enabled
        if (! config('shield.multi-guard', false)) {
            $defaultGuard = config('shield.default_guard', 'api');
            $request->attributes->set('shield_guard', $defaultGuard);

            return $defaultGuard;
        }

        // Priority 3: Try to detect from route
        $guard = detectGuardFromRoute($request);
        if ($guard) {
            // Cache it for this request
            $request->attributes->set('shield_guard', $guard);

            return $guard;
        }

        // Priority 4: Fallback to default
        $defaultGuard = config('shield.default_guard', 'api');
        $request->attributes->set('shield_guard', $defaultGuard);

        return $defaultGuard;
    }
}

if (! function_exists('detectGuardFromRoute')) {
    /**
     * Detect guard from route prefix or name
     */
    function detectGuardFromRoute(Request $request): ?string
    {
        $availableGuards = config('shield.available_guards', []);

        if (empty($availableGuards)) {
            return null;
        }

        // Check route name first (e.g., "admin.login")
        $routeName = $request->route()?->getName();
        if ($routeName) {
            foreach ($availableGuards as $guard => $prefix) {
                if (str_starts_with($routeName, "{$prefix}.")) {
                    return $guard;
                }
            }
        }

        // Check URL path (e.g., "/auth/admin/login")
        $path = $request->path();
        foreach ($availableGuards as $guard => $prefix) {
            // Match patterns like "auth/admin/login", "admin/logout", or just "admin"
            if (str_contains($path, "/{$prefix}/") ||
                str_starts_with($path, "{$prefix}/") ||
                str_ends_with($path, "/{$prefix}") ||
                $path === $prefix) {
                return $guard;
            }
        }

        // Try to detect from authenticated guard
        return array_find(array_keys($availableGuards), fn ($guard) => $request->user($guard));
    }
}

if (! function_exists('resolveAuthenticatableClass')) {
    /**
     * Resolve the authenticatable model class for a given guard
     */
    function resolveAuthenticatableClass(?string $guard = null): string
    {
        if (! $guard) {
            $guard = requestGuardResolver();
        }
        $provider = config("auth.guards.{$guard}.provider");

        if (! $provider) {
            throw new \InvalidArgumentException("Guard [{$guard}] is not defined at auth.php.");
        }

        $model = config("auth.providers.{$provider}.model");

        if (! $model) {
            throw new \InvalidArgumentException("Provider [{$provider}] does not have a model defined at auth.php.");
        }

        return $model;
    }
}

if (! function_exists('currentShieldGuard')) {
    /**
     * Get the current Shield guard name
     */
    function currentShieldGuard(): ?string
    {
        return request()->attributes->get('shield_guard');
    }
}

if (! function_exists('isGuardRoute')) {
    /**
     * Check if the current route belongs to a specific guard
     */
    function isGuardRoute(string $guard): bool
    {
        return requestGuardResolver() === $guard;
    }
}

if (! function_exists('debugGuardDetection')) {
    /**
     * Debug helper to see guard detection info
     */
    function debugGuardDetection(?Request $request = null): array
    {
        $request = $request ?? request();

        return [
            'detected_guard' => requestGuardResolver($request),
            'from_attributes' => $request->attributes->get('shield_guard'),
            'route_name' => $request->route()?->getName(),
            'path' => $request->path(),
            'available_guards' => config('shield.available_guards'),
            'multi_guard_enabled' => config('shield.multi-guard', false),
            'default_guard' => config('shield.default_guard', 'api'),
        ];
    }
}
