<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\PublicIdNormalizer;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\UserBlock;
use Illuminate\Support\Facades\DB;

class BlockUser
{
    public function __construct(private readonly PublicIdNormalizer $ids)
    {
    }

    public function handle(User $user, string $blockedPublicId): UserBlock
    {
        $blocked = User::query()->where('public_id', $this->ids->canonical($blockedPublicId))->first();

        if (! $blocked || $blocked->is($user)) {
            throw new MessagingException('The user to block is invalid.', 404);
        }

        return DB::transaction(fn (): UserBlock => UserBlock::query()->firstOrCreate([
            'blocker_id' => $user->getKey(),
            'blocked_id' => $blocked->getKey(),
        ]));
    }

}
