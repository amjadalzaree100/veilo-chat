<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Authentication\Models\Device;
use App\Domain\Authentication\Models\RefreshToken;
use App\Domain\Authentication\Models\SecurityEvent;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RevokeDeviceSession
{
    public function handle(User $user, Device $device, ?string $ipAddress, ?string $userAgent): void
    {
        DB::transaction(function () use ($user, $device, $ipAddress, $userAgent): void {
            RefreshToken::query()
                ->where('user_id', $user->getKey())
                ->where('device_id', $device->getKey())
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'revoked_reason' => 'logout']);

            SecurityEvent::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->getKey(),
                'device_id' => $device->getKey(),
                'event_type' => 'logout_device',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        });
    }
}
