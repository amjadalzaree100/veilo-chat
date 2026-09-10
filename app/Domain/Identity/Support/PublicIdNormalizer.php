<?php

namespace App\Domain\Identity\Support;

final class PublicIdNormalizer
{
    public function canonical(string $publicId): string
    {
        if (preg_match('/^[0-9a-fA-F]{32}$/', $publicId) !== 1) {
            return $publicId;
        }

        return strtolower(substr($publicId, 0, 8).'-'.substr($publicId, 8, 4).'-'.substr($publicId, 12, 4).'-'.substr($publicId, 16, 4).'-'.substr($publicId, 20));
    }

    public function external(?string $id): ?string
    {
        return $id === null ? null : str_replace('-', '', $id);
    }
}
