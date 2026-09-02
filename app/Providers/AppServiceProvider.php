<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('account-creation', function (Request $request) {
            $deviceIdentifier = (string) $request->input('device_identifier');

            return [
                Limit::perMinute(5)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('device:'.hash('sha256', $deviceIdentifier)),
            ];
        });
    }
}
