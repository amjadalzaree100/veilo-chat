<?php

use App\Http\Controllers\Api\V1\Auth\RegistrationController;
use App\Http\Controllers\Api\V1\Auth\RecoveryController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\IdentityController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', RegistrationController::class)
        ->middleware('throttle:registration');
    Route::post('/auth/refresh', [SessionController::class, 'refresh'])
        ->middleware('throttle:refresh');
    Route::post('/auth/session/restore', [SessionController::class, 'restore'])
        ->middleware('throttle:device-secret-restore');
    Route::post('/auth/recover', [RecoveryController::class, 'recover'])
        ->middleware('throttle:recovery');

    Route::middleware('jwt')->group(function (): void {
        Route::post('/auth/logout', [SessionController::class, 'logout'])
            ->middleware('throttle:authentication');
        Route::post('/auth/logout-all', [SessionController::class, 'logoutAll'])
            ->middleware('throttle:authentication');
        Route::get('/auth/recovery-secret', [RecoveryController::class, 'show']);
        Route::post('/auth/recovery-secret/visibility', [RecoveryController::class, 'setVisibility']);
        Route::post('/auth/recovery-secret/regenerate', [RecoveryController::class, 'regenerate']);
        Route::patch('/account/privacy', [IdentityController::class, 'setPrivacy']);
    });
});
