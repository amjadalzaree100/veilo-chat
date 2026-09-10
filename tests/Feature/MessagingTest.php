<?php

namespace Tests\Feature;

use App\Domain\Authentication\Actions\IssueDeviceSession;
use App\Domain\Authentication\Models\Device;
use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Infrastructure\Messaging\MessageCipher;
use Illuminate\Support\Str;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_create_one_conversation_and_duplicates_are_prevented(): void
    {
        [$user, $token] = $this->authenticatedUser('first');
        $other = $this->createUser('second');

        $first = $this->withToken($token)->postJson('/api/v1/conversations', [
            'participant_public_id' => $this->publicId($other),
        ]);

        $first->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.conversation.user_low_id', fn ($id): bool => is_string($id));

        $second = $this->withToken($token)->postJson('/api/v1/conversations', [
            'participant_public_id' => $this->publicId($other),
        ]);

        $second->assertOk()->assertJsonPath('message', 'Conversation already exists.');
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_blocking_prevents_new_conversations_and_message_sending(): void
    {
        [$user, $token] = $this->authenticatedUser('blocker');
        $other = $this->createUser('blocked');
        $conversation = Conversation::create([
            'id' => (string) Str::uuid(),
            'user_low_id' => min($user->getKey(), $other->getKey()),
            'user_high_id' => max($user->getKey(), $other->getKey()),
        ]);

        $this->withToken($token)->postJson('/api/v1/blocks/'.$this->publicId($other))->assertOk();

        $this->withToken($token)->postJson('/api/v1/conversations', [
            'participant_public_id' => $this->publicId($other),
        ])->assertForbidden();

        $this->withToken($token)->postJson('/api/v1/conversations/'.$conversation->getKey().'/messages', [
            'body' => 'This must be rejected.',
        ])->assertForbidden();
    }

    public function test_message_is_encrypted_reply_is_scoped_and_client_id_is_idempotent(): void
    {
        [$user, $token] = $this->authenticatedUser('sender');
        $other = $this->createUser('recipient');
        $third = $this->createUser('third');
        $conversation = $this->conversation($user, $other);
        $otherConversation = $this->conversation($user, $third);

        $sent = $this->withToken($token)->postJson('/api/v1/conversations/'.$conversation->getKey().'/messages', [
            'body' => 'Encrypted message',
            'client_message_id' => 'client-1',
        ]);

        $sent->assertCreated()->assertJsonPath('data.message.body', 'Encrypted message');
        $message = Message::query()->firstOrFail();
        $this->assertNotSame('Encrypted message', $message->body_ciphertext);
        $this->assertSame('Encrypted message', app(MessageCipher::class)->decrypt($message->body_ciphertext, $message->encryption_key_version));

        $duplicate = $this->withToken($token)->postJson('/api/v1/conversations/'.$conversation->getKey().'/messages', [
            'body' => 'A different retry body is ignored.',
            'client_message_id' => 'client-1',
        ]);

        $duplicate->assertOk()->assertJsonPath('message', 'Existing message returned.');
        $this->assertDatabaseCount('messages', 1);

        $this->withToken($token)->postJson('/api/v1/conversations/'.$otherConversation->getKey().'/messages', [
            'body' => 'Another conversation message',
            'reply_to_message_id' => $message->getKey(),
        ])->assertStatus(422);
    }

    public function test_messages_are_returned_with_cursor_pagination(): void
    {
        [$user, $token] = $this->authenticatedUser('pager');
        $conversation = $this->conversation($user, $this->createUser('page-recipient'));

        foreach (['one', 'two', 'three'] as $body) {
            $this->withToken($token)->postJson('/api/v1/conversations/'.$conversation->getKey().'/messages', [
                'body' => $body,
            ])->assertCreated();
        }

        $firstPage = $this->withToken($token)->getJson('/api/v1/conversations/'.$conversation->getKey().'/messages?per_page=2');
        $firstPage->assertOk()->assertJsonCount(2, 'data.messages');
        $cursor = $firstPage->json('data.pagination.next_cursor');
        $this->assertNotEmpty($cursor);

        $this->withToken($token)->getJson('/api/v1/conversations/'.$conversation->getKey().'/messages?per_page=2&cursor='.urlencode($cursor))
            ->assertOk()
            ->assertJsonCount(1, 'data.messages');
    }

    public function test_only_the_sender_can_edit_or_delete_a_message(): void
    {
        [$sender, $senderToken] = $this->authenticatedUser('owner');
        [$other, $otherToken] = $this->authenticatedUser('reader');
        $conversation = $this->conversation($sender, $other);
        $message = Message::create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation->getKey(),
            'sender_id' => $sender->getKey(),
            'sequence_no' => 1,
            'body_ciphertext' => app(MessageCipher::class)->encrypt('original'),
            'encryption_key_version' => 1,
        ]);

        $this->withToken($otherToken)->patchJson('/api/v1/messages/'.$message->getKey(), ['body' => 'hijack'])
            ->assertForbidden();
        $this->withToken($otherToken)->deleteJson('/api/v1/messages/'.$message->getKey())
            ->assertForbidden();

        $this->withToken($senderToken)->patchJson('/api/v1/messages/'.$message->getKey(), ['body' => 'edited'])
            ->assertOk()
            ->assertJsonPath('data.message.body', 'edited');
        $this->withToken($senderToken)->deleteJson('/api/v1/messages/'.$message->getKey())
            ->assertOk()
            ->assertJsonPath('data.message.body', null);
    }

    private function createUser(string $username): User
    {
        return User::create([
            'id' => (string) Str::uuid(),
            'public_id' => (string) Str::uuid(),
            'username' => $username.'_'.random_int(1000, 999999),
            'recovery_secret_encrypted' => 'test-recovery-secret',
            'privacy' => 'public',
        ]);
    }

    /** @return array{0: User, 1: string} */
    private function authenticatedUser(string $username): array
    {
        $user = $this->createUser($username);
        $device = Device::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'device_identifier' => 'test-device-'.$user->getKey(),
            'platform' => 'web',
            'is_primary' => true,
            'last_active_at' => now(),
        ]);

        $session = app(IssueDeviceSession::class)->handle($user, $device);

        return [$user, $session['access_token']];
    }

    private function conversation(User $first, User $second): Conversation
    {
        [$low, $high] = strcmp($first->getKey(), $second->getKey()) < 0
            ? [$first->getKey(), $second->getKey()]
            : [$second->getKey(), $first->getKey()];

        return Conversation::create([
            'id' => (string) Str::uuid(),
            'user_low_id' => $low,
            'user_high_id' => $high,
        ]);
    }

    private function publicId(User $user): string
    {
        return str_replace('-', '', $user->public_id);
    }

    private function withToken(string $token): self
    {
        return $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json');
    }
}
