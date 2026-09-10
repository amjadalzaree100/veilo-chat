<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\PublicIdNormalizer;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\UserBlock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateConversation
{
    public function __construct(private readonly PublicIdNormalizer $ids)
    {
    }

    public function handle(User $user, string $participantPublicId): Conversation
    {
        $participant = User::query()->where('public_id', $this->ids->canonical($participantPublicId))->first();

        if (! $participant || $participant->is($user)) {
            throw new MessagingException('The conversation participant is invalid.', 404);
        }

        if (UserBlock::query()
            ->where(function ($query) use ($user, $participant): void {
                $query->where(function ($query) use ($user, $participant): void {
                    $query->where('blocker_id', $user->getKey())->where('blocked_id', $participant->getKey());
                })->orWhere(function ($query) use ($user, $participant): void {
                    $query->where('blocker_id', $participant->getKey())->where('blocked_id', $user->getKey());
                });
            })->exists()) {
            throw new MessagingException('Conversation creation is not allowed.', 403);
        }

        [$lowId, $highId] = strcmp((string) $user->getKey(), (string) $participant->getKey()) < 0
            ? [$user->getKey(), $participant->getKey()]
            : [$participant->getKey(), $user->getKey()];

        try {
            return DB::transaction(function () use ($lowId, $highId): Conversation {
                return Conversation::query()->firstOrCreate(
                    ['user_low_id' => $lowId, 'user_high_id' => $highId],
                    ['id' => (string) Str::uuid()]
                );
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23505') {
                throw $exception;
            }

            return Conversation::query()
                ->where('user_low_id', $lowId)
                ->where('user_high_id', $highId)
                ->firstOrFail();
        }
    }

}
