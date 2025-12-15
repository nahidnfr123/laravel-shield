<?php

use NahidFerdous\Shield\Http\Controllers\AuthController;
use NahidFerdous\Shield\Http\Controllers\UserController;

Route::get('me', [AuthController::class, 'me'])->name('me');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

if (config('shield.auth_driver') !== 'sanctum') {
    Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
}

Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('change-password', [UserController::class, 'changePassword'])
        ->name('change.password');
});
