<?php

use App\Http\Controllers\Api\V1\Auth\RegistrationController;
use App\Http\Controllers\Api\V1\Auth\RecoveryController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Account\EmailController;
use App\Http\Controllers\Api\V1\IdentityController;
use App\Http\Controllers\Api\V1\Messaging\BlockController;
use App\Http\Controllers\Api\V1\Messaging\ConversationController;
use App\Http\Controllers\Api\V1\Messaging\MessageController;
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
        Route::post('/auth/logout', [SessionController::class, 'logout'])->middleware('throttle:authentication');
        Route::post('/auth/logout-all', [SessionController::class, 'logoutAll'])->middleware('throttle:authentication');
        Route::get('/auth/recovery-secret', [RecoveryController::class, 'show']);
        Route::post('/auth/recovery-secret/visibility', [RecoveryController::class, 'setVisibility']);
        Route::post('/auth/recovery-secret/regenerate', [RecoveryController::class, 'regenerate']);
        Route::post('/account/email/otp', [EmailController::class, 'requestOtp'])->middleware('throttle:email-otp-issue');
        Route::post('/account/email/verify', [EmailController::class, 'verify'])->middleware('throttle:email-otp-verify');
        Route::patch('/account/privacy', [IdentityController::class, 'setPrivacy']);
        Route::post('/conversations', [ConversationController::class, 'store'])->middleware('throttle:messaging');
        Route::get('/conversations', [ConversationController::class, 'index'])->middleware('throttle:messaging');
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->middleware('throttle:messaging');
        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->middleware('throttle:messaging');
        Route::patch('/messages/{message}', [MessageController::class, 'update'])->middleware('throttle:messaging');
        Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->middleware('throttle:messaging');
        Route::post('/blocks/{blockedPublicId}', [BlockController::class, 'store'])->where('blockedPublicId', '[0-9a-fA-F]{32}')->middleware('throttle:messaging');
        Route::delete('/blocks/{blockedPublicId}', [BlockController::class, 'destroy'])->where('blockedPublicId', '[0-9a-fA-F]{32}')->middleware('throttle:messaging');
    });
});
