<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Authentication\Models\Device;
use App\Domain\Authentication\Models\EmailOtp;
use App\Domain\Authentication\Models\SecurityEvent;
use App\Domain\Identity\Models\User;
use App\Mail\EmailOtpMail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class LinkEmail
{
    public function requestOtp(User $user, Device $device, string $email, ?string $ipAddress, ?string $userAgent): int
    {
        $email = $this->normalize($email);

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])
            ->where('id', '!=', $user->getKey())
            ->whereNull('deleted_at')
            ->exists()) {
            throw new RuntimeException('This email is already linked to another account.');
        }

        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(10);

        DB::transaction(function () use ($user, $device, $email, $code, $expiresAt, $ipAddress, $userAgent): void {
            EmailOtp::query()
                ->where('user_id', $user->getKey())
                ->where('purpose', 'link_email')
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            EmailOtp::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->getKey(),
                'email' => $email,
                'purpose' => 'link_email',
                'code_hash' => Hash::make($code),
                'max_attempts' => 5,
                'expires_at' => $expiresAt,
            ]);

            SecurityEvent::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->getKey(),
                'device_id' => $device->getKey(),
                'event_type' => 'email_otp_issued',
                'metadata' => ['email' => $email],
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        });

        Mail::to($email)->send(new EmailOtpMail($code));

        return 600;
    }

    public function verify(User $user, Device $device, string $email, string $code, ?string $ipAddress, ?string $userAgent): void
    {
        $email = $this->normalize($email);

        try {
            DB::transaction(function () use ($user, $device, $email, $code, $ipAddress, $userAgent): void {
                $otp = EmailOtp::query()
                    ->where('user_id', $user->getKey())
                    ->where('email', $email)
                    ->where('purpose', 'link_email')
                    ->whereNull('consumed_at')
                    ->latest('created_at')
                    ->lockForUpdate()
                    ->first();

                if ($otp === null || $otp->expires_at->isPast()) {
                    throw new RuntimeException('The verification code is invalid or expired.');
                }

                if ($otp->attempts >= $otp->max_attempts) {
                    throw new RuntimeException('The verification code has reached its attempt limit.');
                }

                if (! Hash::check($code, $otp->code_hash)) {
                    $otp->increment('attempts');
                    throw new RuntimeException('The verification code is invalid or expired.');
                }

                if (User::query()->whereRaw('LOWER(email) = ?', [$email])
                    ->where('id', '!=', $user->getKey())
                    ->whereNull('deleted_at')
                    ->exists()) {
                    throw new RuntimeException('This email is already linked to another account.');
                }

                $user->forceFill([
                    'email' => $email,
                    'email_verified_at' => now(),
                ])->save();

                $otp->forceFill(['consumed_at' => now()])->save();

                SecurityEvent::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->getKey(),
                    'device_id' => $device->getKey(),
                    'event_type' => 'email_linked',
                    'metadata' => ['email' => $email],
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                ]);
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505') {
                throw new RuntimeException('This email is already linked to another account.');
            }

            throw $exception;
        }
    }

    public function unlink(User $user, Device $device, ?string $ipAddress, ?string $userAgent): void
    {
        DB::transaction(function () use ($user, $device, $ipAddress, $userAgent): void {
            $user->refresh();

            if ($user->email === null) {
                throw new RuntimeException('No email is linked to this account.');
            }

            $email = $user->email;

            EmailOtp::query()
                ->where('user_id', $user->getKey())
                ->where('purpose', 'link_email')
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            $user->forceFill([
                'email' => null,
                'email_verified_at' => null,
            ])->save();

            SecurityEvent::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->getKey(),
                'device_id' => $device->getKey(),
                'event_type' => 'email_unlinked',
                'metadata' => ['email' => $email],
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        });
    }

    private function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }
}
