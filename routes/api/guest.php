<?php

// Public auth routes (login, register, etc.)
use NahidFerdous\Shield\Http\Controllers\AuthController;
use NahidFerdous\Shield\Http\Controllers\SocialAuthController;

$throttle = config('shield.auth.throttle_attempts', 6);

Route::prefix('auth')->group(function () use ($throttle) {
    $guards = config('shield.available_guards', []);

    if (config('shield.generate_separate_route_for_guards', true)) {
        foreach ($guards as $guard => $defaultGuard) {
            Route::prefix($defaultGuard)
                ->name("$defaultGuard.")
                ->middleware(["throttle:$throttle"])
                ->group(function () {
                    Route::post('login', [AuthController::class, 'login'])->name('login');
                    Route::post('register', [AuthController::class, 'register'])->name('register');
                    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
                    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
                });
        }
    } else {
        Route::middleware(["throttle:$throttle"])
            ->group(function () {
                Route::post('login', [AuthController::class, 'login'])->name('login');
                Route::post('register', [AuthController::class, 'register'])->name('register');
                Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
                Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
            });
    }
});

// Email verification route
if (config('shield.auth.check_verified', false)) {
    Route::get('verify-email/{token}', [AuthController::class, 'verifyEmail'])
        ->middleware(["throttle:{$throttle},1"])
        ->name('verify-email');
    Route::middleware(["throttle:{$throttle},1"])->group(function () {
        Route::post('resend-email-verification-link', [AuthController::class, 'resendEmailVerificationLink'])
            ->name('resend-email-verification-link');
    });
}

// Social authentication routes
if (config('shield.social.enabled', false)) {
    Route::prefix('auth')->group(function () {
        Route::get('/providers', [SocialAuthController::class, 'providers'])->name('social.providers');
        Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
        Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
    });
}
