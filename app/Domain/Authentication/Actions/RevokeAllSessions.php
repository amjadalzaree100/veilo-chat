<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Authentication\Models\RefreshToken;
use App\Domain\Authentication\Models\SecurityEvent;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RevokeAllSessions
{
    public function handle(User $user, ?string $ipAddress, ?string $userAgent): void
    {
        DB::transaction(function () use ($user, $ipAddress, $userAgent): void {
            RefreshToken::query()
                ->where('user_id', $user->getKey())
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'revoked_reason' => 'logout_all']);

            $user->devices()->whereNull('revoked_at')->update(['revoked_at' => now()]);

            SecurityEvent::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->getKey(),
                'event_type' => 'logout_all',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        });
    }
}
