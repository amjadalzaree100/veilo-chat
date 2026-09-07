<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Authentication\Models\Device;
use App\Domain\Authentication\Models\SecurityEvent;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RegenerateRecoverySecret
{
    public function handle(User $user, Device $device, ?string $ipAddress, ?string $userAgent): string
    {
        $secret = Str::random(48);

        DB::transaction(function () use ($user, $device, $secret, $ipAddress, $userAgent): void {
            $user->refresh();

            if ($user->recovery_secret_hidden_at !== null) {
                throw new RuntimeException('The recovery secret is hidden.');
            }

            $user->forceFill([
                'recovery_secret_encrypted' => $secret,
            ])->save();

            SecurityEvent::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->getKey(),
                'device_id' => $device->getKey(),
                'event_type' => 'recovery_secret_changed',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        });

        return $secret;
    }
}
