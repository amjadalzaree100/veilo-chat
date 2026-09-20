<?php

namespace Tests\Feature;

use App\Domain\Authentication\Actions\IssueDeviceSession;
use App\Domain\Authentication\Models\Device;
use App\Domain\Authentication\Models\EmailOtp;
use App\Domain\Identity\Models\User;
use App\Mail\EmailOtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailLinkingTest extends TestCase
{
    use RefreshDatabase;

    private string $otpCode;

    public function test_email_is_linked_only_after_otp_verification(): void
    {
        Mail::fake();
        [$user, $token] = $this->authenticatedUser();

        $request = $this->withToken($token)->postJson('/api/v1/account/email/otp', [
            'email' => 'User@example.com',
        ]);

        $request->assertAccepted()->assertJsonPath('data.expires_in', 600);
        $this->assertNull($user->fresh()->email);
        Mail::assertSent(EmailOtpMail::class, function (EmailOtpMail $mail): bool {
            $this->otpCode = $mail->code;

            return true;
        });

        $this->withToken($token)->postJson('/api/v1/account/email/verify', [
            'email' => 'USER@example.com',
            'code' => $this->otpCode,
        ])->assertOk()->assertJsonPath('data.email', 'user@example.com');

        $this->assertSame('user@example.com', $user->fresh()->email);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNotNull(EmailOtp::query()->first()->consumed_at);
    }

    public function test_email_cannot_be_linked_to_two_active_accounts(): void
    {
        Mail::fake();
        [$firstUser] = $this->authenticatedUser('first');
        [, $secondToken] = $this->authenticatedUser('second');
        $firstUser->forceFill([
            'email' => 'same@example.com',
            'email_verified_at' => now(),
        ])->save();

        $this->withToken($secondToken)->postJson('/api/v1/account/email/otp', [
            'email' => 'SAME@example.com',
        ])->assertStatus(409);

        $this->assertSame('same@example.com', $firstUser->fresh()->email);
    }

    /** @return array{0: User, 1: string} */
    private function authenticatedUser(string $name = 'user'): array
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'public_id' => (string) Str::uuid(),
            'username' => $name.'_'.random_int(1000, 999999),
            'recovery_secret_encrypted' => 'test-recovery-secret',
            'privacy' => 'public',
        ]);
        $device = Device::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'device_identifier' => 'test-device-'.Str::uuid(),
            'platform' => 'web',
            'is_primary' => true,
            'last_active_at' => now(),
        ]);

        return [$user, app(IssueDeviceSession::class)->handle($user, $device)['access_token']];
    }

    private function withToken(string $token): self
    {
        return $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json');
    }
}
