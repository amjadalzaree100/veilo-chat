<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Authentication\Models\Device;
use App\Domain\Authentication\Models\SecurityEvent;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

class RestoreSessionByDeviceSecret
{
    public function __construct(private readonly IssueDeviceSession $sessions)
    {
    }

    /**
     * @return array{user: User, device: Device, access_token: string, refresh_token: string, expires_in: int, device_secret: string|null}
     */
    public function handle(string $deviceIdentifier, string $deviceSecret, ?string $ipAddress, ?string $userAgent): array
    {
        $candidates = Device::query()
            ->where('device_identifier', $deviceIdentifier)
            ->whereNull('revoked_at')
            ->get();

        $device = $candidates->first(
            fn (Device $candidate): bool => $candidate->device_secret_hash !== null
                && Hash::check($deviceSecret, $candidate->device_secret_hash)
        );

        if (! $device) {
            $this->logFailure($deviceIdentifier, $candidates->first(), $ipAddress, $userAgent);
            throw new RuntimeException('Session restoration failed.');
        }

        try {
            $session = DB::transaction(function () use ($device, $deviceSecret, $ipAddress, $userAgent): array {
                $lockedDevice = Device::query()->whereKey($device->getKey())->lockForUpdate()->first();
                $user = $lockedDevice?->user()->first();

                if (! $lockedDevice || $lockedDevice->revoked_at !== null || ! $user || $lockedDevice->device_secret_hash === null || ! Hash::check($deviceSecret, $lockedDevice->device_secret_hash)) {
                    throw new RuntimeException('Session restoration failed.');
                }

                $lockedDevice->forceFill(['last_active_at' => now()])->save();
                $issued = $this->sessions->handle($user, $lockedDevice);

                SecurityEvent::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->getKey(),
                    'device_id' => $lockedDevice->getKey(),
                    'event_type' => 'device_session_restored',
                    'metadata' => ['method' => 'device_secret'],
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                ]);

                return [
                    'user' => $user,
                    'device' => $lockedDevice,
                    ...$issued,
                ];
            });
        } catch (RuntimeException $exception) {
            $this->logFailure($deviceIdentifier, $device, $ipAddress, $userAgent);
            throw $exception;
        }

        return $session;
    }

    private function logFailure(string $deviceIdentifier, ?Device $device, ?string $ipAddress, ?string $userAgent): void
    {
        RateLimiter::hit('device-secret-failure:'.hash('sha256', $deviceIdentifier));

        SecurityEvent::create([
            'id' => (string) Str::uuid(),
            'user_id' => $device?->user_id,
            'device_id' => $device?->getKey(),
            'event_type' => 'device_session_restore_failed',
            'metadata' => ['method' => 'device_secret'],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
