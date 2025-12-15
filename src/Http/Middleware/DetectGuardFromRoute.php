<?php

namespace NahidFerdous\Shield\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectGuardFromRoute
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null)
    {
        // Priority 1: Guard passed to middleware
        if ($guard) {
            $request->attributes->set('shield_guard', $guard);

            return $next($request);
        }

        // Priority 2: Guard from query parameter
        if ($request->has('guard')) {
            $guardParam = $request->input('guard');
            $availableGuards = array_keys(config('shield.available_guards', []));

            // Validate the guard exists
            if (in_array($guardParam, $availableGuards, true)) {
                $request->attributes->set('shield_guard', $guardParam);

                return $next($request);
            }
        }

        // Priority 3: Detect from route
        $detectedGuard = $this->detectGuardFromRoute($request);

        if ($detectedGuard) {
            $request->attributes->set('shield_guard', $detectedGuard);
        } else {
            // Priority 4: Fallback to default guard
            $request->attributes->set('shield_guard', config('shield.default_guard', 'api'));
        }

        return $next($request);
    }

    /**
     * Detect guard from route prefix or name
     */
    protected function detectGuardFromRoute(Request $request): ?string
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

        // Check URL path (e.g., "/api/auth/admin/login")
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
