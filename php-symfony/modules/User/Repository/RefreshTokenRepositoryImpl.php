<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;

class RefreshTokenRepositoryImpl implements RefreshTokenRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findByToken(string $token): ?RefreshToken
    {
        return $this->entityManager->getRepository(RefreshToken::class)->findOneBy([
            'token' => $token,
        ]);
    }

    public function findValidToken(string $userId, string $deviceId): ?RefreshToken
    {
        return $this->entityManager->getRepository(RefreshToken::class)->findOneBy([
            'userId' => $userId,
            'deviceId' => $deviceId,
            'revokedAt' => null,
        ]);
    }

    public function save(RefreshToken $token): void
    {
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    public function revokeByUserId(string $userId): void
    {
        $tokens = $this->entityManager->getRepository(RefreshToken::class)->findBy([
            'userId' => $userId,
            'revokedAt' => null,
        ]);

        foreach ($tokens as $token) {
            $token->revoke();
        }

        $this->entityManager->flush();
    }

    public function revokeByUserIdAndDeviceId(string $userId, string $deviceId): void
    {
        $token = $this->entityManager->getRepository(RefreshToken::class)->findOneBy([
            'userId' => $userId,
            'deviceId' => $deviceId,
            'revokedAt' => null,
        ]);

        if ($token) {
            $token->revoke();
            $this->entityManager->flush();
        }
    }

    public function revokeExpiredTokens(): int
    {
        $now = new \DateTime();
        $tokens = $this->entityManager->getRepository(RefreshToken::class)->findBy([
            'revokedAt' => null,
        ]);

        $revokedCount = 0;
        foreach ($tokens as $token) {
            if ($token->isExpired()) {
                $token->revoke();
                $revokedCount++;
            }
        }

        if ($revokedCount > 0) {
            $this->entityManager->flush();
        }

        return $revokedCount;
    }
}
