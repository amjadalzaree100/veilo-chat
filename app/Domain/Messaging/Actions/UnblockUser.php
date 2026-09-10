<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\PublicIdNormalizer;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\UserBlock;
use Illuminate\Support\Facades\DB;

class UnblockUser
{
    public function __construct(private readonly PublicIdNormalizer $ids)
    {
    }

    public function handle(User $user, string $blockedPublicId): void
    {
        $blocked = User::query()->where('public_id', $this->ids->canonical($blockedPublicId))->first();

        if (! $blocked || $blocked->is($user)) {
            throw new MessagingException('The user to unblock is invalid.', 404);
        }

        DB::transaction(function () use ($user, $blocked): void {
            UserBlock::query()
                ->where('blocker_id', $user->getKey())
                ->where('blocked_id', $blocked->getKey())
                ->delete();
        });
    }

}
