<?php

use App\Http\Controllers\Api\AccountController;
use Illuminate\Support\Facades\Route;

Route::post('/accounts', [AccountController::class, 'store'])
    ->middleware('throttle:account-creation');
