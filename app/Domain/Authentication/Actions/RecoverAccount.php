<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Authentication\Models\Device;
use App\Domain\Authentication\Models\SecurityEvent;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RecoverAccount
{
    public function __construct(private readonly IssueDeviceSession $sessions)
    {
    }

    /**
     * @param array{public_id: string, recovery_secret: string, device_identifier: string, device_name?: string|null, platform: string, push_token?: string|null} $data
     * @return array{user: User, device: Device, session: array{access_token: string, refresh_token: string, expires_in: int}}
     */
    public function handle(array $data, ?string $ipAddress, ?string $userAgent): array
    {
        return DB::transaction(function () use ($data, $ipAddress, $userAgent): array {
            $user = User::query()->where('public_id', $data['public_id'])->first();

            if (! $user || ! hash_equals((string) $user->recovery_secret_encrypted, $data['recovery_secret'])) {
                if ($user) {
                    SecurityEvent::create([
                        'id' => (string) Str::uuid(),
                        'user_id' => $user->getKey(),
                        'event_type' => 'recovery_failed',
                        'ip_address' => $ipAddress,
                        'user_agent' => $userAgent,
                    ]);
                }

                throw new RuntimeException('Recovery failed.');
            }

            $device = Device::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->getKey(),
                'device_identifier' => $data['device_identifier'],
                'device_name' => $data['device_name'] ?? null,
                'platform' => $data['platform'],
                'push_token' => $data['push_token'] ?? null,
                'last_active_at' => now(),
                'is_primary' => false,
            ]);

            SecurityEvent::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->getKey(),
                'device_id' => $device->getKey(),
                'event_type' => 'recovery_succeeded',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return [
                'user' => $user,
                'device' => $device,
                'session' => $this->sessions->handle($user, $device),
            ];
        });
    }
}
