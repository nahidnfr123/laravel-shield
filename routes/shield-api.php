<?php

use Illuminate\Support\Facades\Route;
use NahidFerdous\Shield\Http\Controllers\PermissionController;
use NahidFerdous\Shield\Http\Controllers\RoleController;
use NahidFerdous\Shield\Http\Controllers\UserController;

Route::middleware(['shield.guard'])->group(function () {
    $driver = config('shield.auth_driver', 'sanctum');
    $multiGuard = config('shield.multi-guard', false);
    $defaultGuard = config('shield.default_guard', 'api');
    $guards = config('shield.available_guards', []);

    require __DIR__.'/api/guest.php';

    // Protected routes - Multi-guard support
    if ($multiGuard && config('shield.generate_separate_route_for_guards', true)) {
        // Create separate route groups for each guard
        foreach ($guards as $guard => $prefix) {
            $authMiddleware = match ($driver) {
                // 'sanctum' => "auth:sanctum,{$guard}",
                'passport', 'jwt' => "auth:{$guard}",
                default => "auth:{$guard}",
            };

            Route::prefix($prefix)
                ->name("{$prefix}.")
                ->middleware([$authMiddleware])
                ->group(function () {
                    require __DIR__.'/api/auth.php';
                });
        }
    } else {
        // Single guard mode or multi-guard without separate routes
        $authMiddleware = match ($driver) {
            'passport', 'jwt' => "auth:{$defaultGuard}",
            default => 'auth:sanctum',
        };

        Route::middleware([$authMiddleware])->group(function () {
            require __DIR__.'/api/auth.php';
        });
    }
});

Route::apiResource('users', UserController::class);
Route::apiResource('roles', RoleController::class);
Route::apiResource('permissions', PermissionController::class)->only('index');
Route::post('assign-permission-to-role', [PermissionController::class, 'assignPermissionToRole']);
Route::post('assign-permission-to-user', [PermissionController::class, 'assignPermissionToUser']);
