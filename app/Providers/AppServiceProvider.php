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
        RateLimiter::for('registration', function (Request $request): array {
            $deviceIdentifier = (string) $request->input('device_identifier');

            return [
                Limit::perMinute(5)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('device:'.hash('sha256', $deviceIdentifier)),
            ];
        });

        RateLimiter::for('refresh', fn (Request $request): Limit =>
            Limit::perMinute(10)->by('ip:'.$request->ip())
        );

        RateLimiter::for('recovery', function (Request $request): array {
            return [
                Limit::perMinute(5)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('public_id:'.$request->input('public_id')),
            ];
        });

        RateLimiter::for('authentication', fn (Request $request): Limit =>
            Limit::perMinute(30)->by('ip:'.$request->ip())
        );

        RateLimiter::for('email-otp-issue', function (Request $request): array {
            $user = $request->user();

            return [
                Limit::perMinute(3)->by('email-otp-issue-ip:'.$request->ip()),
                Limit::perMinute(5)->by('email-otp-issue-user:'.($user?->getAuthIdentifier() ?? $request->ip())),
            ];
        });

        RateLimiter::for('email-otp-verify', function (Request $request): array {
            $user = $request->user();

            return [
                Limit::perMinute(10)->by('email-otp-verify-ip:'.$request->ip()),
                Limit::perMinute(10)->by('email-otp-verify-user:'.($user?->getAuthIdentifier() ?? $request->ip())),
            ];
        });

        RateLimiter::for('device-secret-restore', function (Request $request): array {
            $identifier = (string) $request->input('device_identifier');

            return [
                Limit::perMinute(5)->by('device-secret-ip:'.$request->ip()),
                Limit::perMinute(5)->by('device-secret-identifier:'.hash('sha256', $identifier)),
                Limit::perMinute(5)->by('device-secret-failure:'.hash('sha256', $identifier)),
            ];
        });

        RateLimiter::for('messaging', function (Request $request): array {
            $user = $request->user();

            return [
                Limit::perMinute(120)->by('messaging-ip:'.$request->ip()),
                Limit::perMinute(300)->by('messaging-user:'.($user?->getAuthIdentifier() ?? $request->ip())),
            ];
        });
    }
}
