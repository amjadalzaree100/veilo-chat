<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Authentication\Models\Device;
use App\Domain\Identity\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterAccount
{
    public function __construct(private readonly IssueDeviceSession $sessions)
    {
    }

    /**
     * @param array{username: string, display_name?: string|null, device_identifier: string, device_name?: string|null, platform: string, push_token?: string|null} $data
     * @return array{user: User, device: Device, recovery_secret: string, session: array{access_token: string, refresh_token: string, expires_in: int}}
     */
    public function handle(array $data): array
    {
        $recoverySecret = Str::random(48);

        try {
            return DB::transaction(function () use ($data, $recoverySecret): array {
                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'public_id' => (string) Str::uuid(),
                    'username' => strtolower($data['username']),
                    'display_name' => $data['display_name'] ?? null,
                    'recovery_secret_encrypted' => $recoverySecret,
                    'privacy' => 'public',
                ]);

                $device = Device::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->getKey(),
                    'device_identifier' => $data['device_identifier'],
                    'device_name' => $data['device_name'] ?? null,
                    'platform' => $data['platform'],
                    'push_token' => $data['push_token'] ?? null,
                    'last_active_at' => now(),
                    'is_primary' => true,
                ]);

                return [
                    'user' => $user,
                    'device' => $device,
                    'recovery_secret' => $recoverySecret,
                    'session' => $this->sessions->handle($user, $device),
                ];
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505') {
                throw ValidationException::withMessages([
                    'username' => 'This username is already in use.',
                ]);
            }

            throw $exception;
        }
    }
}
