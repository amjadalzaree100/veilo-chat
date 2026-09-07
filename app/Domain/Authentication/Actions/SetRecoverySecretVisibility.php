<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Authentication\Models\Device;
use App\Domain\Authentication\Models\SecurityEvent;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SetRecoverySecretVisibility
{
    public function handle(User $user, Device $device, bool $visible, ?string $ipAddress, ?string $userAgent): void
    {
        DB::transaction(function () use ($user, $device, $visible, $ipAddress, $userAgent): void {
            $user->refresh();

            if (! $visible && $user->recovery_secret_hidden_at !== null) {
                throw new RuntimeException('The recovery secret is already hidden.');
            }

            if ($visible && $user->recovery_secret_hidden_at === null) {
                throw new RuntimeException('The recovery secret is already visible.');
            }

            $user->forceFill([
                'recovery_secret_hidden_at' => $visible ? null : now(),
            ])->save();

            SecurityEvent::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->getKey(),
                'device_id' => $device->getKey(),
                'event_type' => $visible ? 'recovery_secret_shown' : 'recovery_secret_hidden',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        });
    }
}
