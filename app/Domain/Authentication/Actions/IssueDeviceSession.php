<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Authentication\Models\Device;
use App\Domain\Authentication\Models\RefreshToken;
use App\Domain\Identity\Models\User;
use App\Infrastructure\Jwt\JwtTokenService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class IssueDeviceSession
{
    public function __construct(private readonly JwtTokenService $jwtTokens)
    {
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int, device_secret: string|null}
     */
    public function handle(User $user, Device $device): array
    {
        $deviceSecret = null;

        if ($device->device_secret_hash === null) {
            $deviceSecret = bin2hex(random_bytes(64));
            $device->forceFill([
                'device_secret_hash' => Hash::make($deviceSecret),
            ])->save();
        }

        $refreshToken = Str::random(96);

        RefreshToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'device_id' => $device->getKey(),
            'family_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $refreshToken),
            'expires_at' => now()->addDays(config('authentication.refresh_token_ttl_days')),
        ]);

        return [
            ...$this->issueAccessToken($user, $device),
            'refresh_token' => $refreshToken,
            'device_secret' => $deviceSecret,
        ];
    }

    /**
     * @return array{access_token: string, expires_in: int}
     */
    public function issueAccessToken(User $user, Device $device): array
    {
        $accessToken = $this->jwtTokens->issueAccessToken($user, $device);

        return [
            'access_token' => $this->jwtTokens->serialize($accessToken),
            'expires_in' => config('authentication.access_token_ttl_minutes') * 60,
        ];
    }
}
