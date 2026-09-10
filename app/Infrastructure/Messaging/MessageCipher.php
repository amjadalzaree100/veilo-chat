<?php

namespace App\Infrastructure\Messaging;

use Illuminate\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

final class MessageCipher
{
    /** @var array<int, Encrypter> */
    private readonly array $encrypters;

    private readonly int $currentVersion;

    public function __construct()
    {
        $this->currentVersion = (int) config('messages.encryption_key_version');
        $this->encrypters = [
            $this->currentVersion => $this->createEncrypter(config('messages.encryption_key')),
        ];

        foreach (array_filter(explode(',', (string) config('messages.previous_encryption_keys'))) as $definition) {
            [$version, $key] = array_pad(explode('=', $definition, 2), 2, null);

            if ($version === null || $key === null || ! ctype_digit(trim($version))) {
                throw new RuntimeException('MESSAGE_ENCRYPTION_PREVIOUS_KEYS has an invalid entry.');
            }

            $this->encrypters[(int) trim($version)] = $this->createEncrypter(trim($key));
        }
    }

    public function encrypt(string $plaintext): string
    {
        return $this->encrypters[$this->currentVersion]->encryptString($plaintext);
    }

    public function decrypt(string $ciphertext, int $version): string
    {
        $encrypter = $this->encrypters[$version] ?? null;

        if ($encrypter === null) {
            throw new RuntimeException('Message encryption key version is unavailable.');
        }

        try {
            return $encrypter->decryptString($ciphertext);
        } catch (DecryptException $exception) {
            report($exception);
            throw new RuntimeException('Message content could not be decrypted.', 0, $exception);
        }
    }

    private function createEncrypter(mixed $configuredKey): Encrypter
    {
        $key = (string) $configuredKey;
        $key = str_starts_with($key, 'base64:')
            ? base64_decode(substr($key, 7), true)
            : $key;

        if (! is_string($key) || strlen($key) !== 32) {
            throw new RuntimeException('Message encryption keys must be 32-byte keys.');
        }

        return new Encrypter($key, 'AES-256-CBC');
    }
}
