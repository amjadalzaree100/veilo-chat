<?php

namespace App\Infrastructure\Jwt;

use App\Domain\Authentication\Models\Device;
use App\Domain\Identity\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Str;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;

class JwtTokenService
{
    private Configuration $configuration;

    public function __construct()
    {
        $key = (string) config('authentication.jwt_signing_key');
        $key = Str::startsWith($key, 'base64:')
            ? base64_decode(Str::after($key, 'base64:'), true)
            : $key;

        if (! is_string($key) || strlen($key) < 32) {
            throw new \RuntimeException('JWT_SIGNING_KEY must be a valid key of at least 32 bytes.');
        }

        $this->configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($key),
        );
    }

    public function issueAccessToken(User $user, Device $device): Token\Plain
    {
        $now = new DateTimeImmutable();
        $expiresAt = $now->modify(sprintf('+%d minutes', config('authentication.access_token_ttl_minutes')));

        return $this->configuration->builder()
            ->issuedBy((string) config('app.url'))
            ->identifiedBy((string) Str::uuid())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($expiresAt)
            ->relatedTo((string) $user->getKey())
            ->withClaim('device_id', (string) $device->getKey())
            ->getToken($this->configuration->signer(), $this->configuration->signingKey());
    }

    public function parseAndValidate(string $value): Token
    {
        $token = $this->configuration->parser()->parse($value);
        $this->configuration->validator()->assert(
            $token,
            new SignedWith($this->configuration->signer(), $this->configuration->verificationKey()),
            new StrictValidAt(new SystemClock(new DateTimeZone('UTC'))),
        );

        return $token;
    }

    public function serialize(Token\Plain $token): string
    {
        return $token->toString();
    }
}
