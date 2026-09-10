<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Domain\Messaging\Models\UserBlock;
use App\Infrastructure\Messaging\MessageCipher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendMessage
{
    public function __construct(private readonly MessageCipher $cipher)
    {
    }

    /**
     * @return array{message: Message, created: bool}
     */
    public function handle(User $user, Conversation $conversation, string $body, ?string $clientMessageId, ?string $replyToMessageId): array
    {
        return DB::transaction(function () use ($user, $conversation, $body, $clientMessageId, $replyToMessageId): array {
            $conversation = Conversation::query()->whereKey($conversation->getKey())->lockForUpdate()->first();

            if (! $conversation || ! $conversation->containsUser((string) $user->getKey())) {
                throw new MessagingException('You are not a participant in this conversation.', 403);
            }

            if (UserBlock::query()
                ->where(function ($query) use ($user, $conversation): void {
                    $otherUserId = $conversation->otherUserId((string) $user->getKey());
                    $query->where(function ($query) use ($user, $otherUserId): void {
                        $query->where('blocker_id', $user->getKey())->where('blocked_id', $otherUserId);
                    })->orWhere(function ($query) use ($user, $otherUserId): void {
                        $query->where('blocker_id', $otherUserId)->where('blocked_id', $user->getKey());
                    });
                })->exists()) {
                throw new MessagingException('Messaging is not allowed in this conversation.', 403);
            }

            if ($clientMessageId !== null) {
                $existing = Message::query()
                    ->where('conversation_id', $conversation->getKey())
                    ->where('sender_id', $user->getKey())
                    ->where('client_message_id', $clientMessageId)
                    ->first();

                if ($existing) {
                    return ['message' => $existing, 'created' => false];
                }
            }

            $reply = null;
            if ($replyToMessageId !== null) {
                $reply = Message::query()
                    ->whereKey($replyToMessageId)
                    ->where('conversation_id', $conversation->getKey())
                    ->first();

                if (! $reply) {
                    throw new MessagingException('The reply target must belong to this conversation.', 422);
                }
            }

            $message = Message::create([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation->getKey(),
                'sender_id' => $user->getKey(),
                'sequence_no' => ((int) Message::query()->where('conversation_id', $conversation->getKey())->max('sequence_no')) + 1,
                'client_message_id' => $clientMessageId,
                'reply_to_message_id' => $reply?->getKey(),
                'body_ciphertext' => $this->cipher->encrypt($body),
                'encryption_key_version' => config('messages.encryption_key_version'),
            ]);

            $conversation->touch();

            return ['message' => $message, 'created' => true];
        });
    }
}
