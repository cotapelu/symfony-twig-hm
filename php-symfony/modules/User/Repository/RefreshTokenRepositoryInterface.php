<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function findByToken(string $token): ?RefreshToken;
    public function findValidToken(string $userId, string $deviceId): ?RefreshToken;
    public function save(RefreshToken $token): void;
    public function revokeByUserId(string $userId): void;
    public function revokeByUserIdAndDeviceId(string $userId, string $deviceId): void;
    public function revokeExpiredTokens(): int;
}
