<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Authentication\Models\Device;
use App\Domain\Authentication\Models\RefreshToken;
use App\Domain\Authentication\Models\SecurityEvent;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RefreshDeviceSession
{
    public function __construct(private readonly IssueDeviceSession $sessions)
    {
    }

    /**
     * @return array{user: User, device: Device, access_token: string, refresh_token: string, expires_in: int}
     */
    public function handle(string $plainRefreshToken, string $ipAddress, ?string $userAgent): array
    {
        $result = DB::transaction(function () use ($plainRefreshToken, $ipAddress, $userAgent): ?array {
            $token = RefreshToken::query()
                ->where('token_hash', hash('sha256', $plainRefreshToken))
                ->lockForUpdate()
                ->first();

            if (! $token) {
                throw new RuntimeException('Invalid refresh token.');
            }

            $user = User::query()->find($token->user_id);
            $device = Device::query()->whereKey($token->device_id)->where('user_id', $token->user_id)->first();

            if (! $user || ! $device) {
                throw new RuntimeException('Invalid refresh token.');
            }

            if ($token->revoked_at !== null) {
                RefreshToken::query()
                    ->where('family_id', $token->family_id)
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now(), 'revoked_reason' => 'reuse_detected']);

                SecurityEvent::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->getKey(),
                    'device_id' => $device->getKey(),
                    'event_type' => 'refresh_token_reuse',
                    'metadata' => ['family_id' => $token->family_id],
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                ]);

                return null;
            }

            if ($device->revoked_at !== null) {
                throw new RuntimeException('Invalid refresh token.');
            }

            if ($token->expires_at->isPast()) {
                $token->update(['revoked_at' => now(), 'revoked_reason' => 'expired']);
                throw new RuntimeException('Refresh token expired.');
            }

            $newRefreshToken = Str::random(96);
            $newTokenId = (string) Str::uuid();
            $newToken = RefreshToken::create([
                'id' => $newTokenId,
                'user_id' => $user->getKey(),
                'device_id' => $device->getKey(),
                'family_id' => $token->family_id,
                'token_hash' => hash('sha256', $newRefreshToken),
                'expires_at' => now()->addDays(config('authentication.refresh_token_ttl_days')),
            ]);

            $token->update([
                'replaced_by' => $newToken->getKey(),
                'revoked_at' => now(),
                'revoked_reason' => 'rotated',
            ]);

            $session = $this->sessions->issueAccessToken($user, $device);

            return [
                'user' => $user,
                'device' => $device,
                ...$session,
                'refresh_token' => $newRefreshToken,
            ];
        });

        if ($result === null) {
            throw new RuntimeException('Invalid refresh token.');
        }

        return $result;
    }
}
