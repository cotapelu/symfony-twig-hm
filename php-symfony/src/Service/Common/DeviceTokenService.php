<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\DeviceToken;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing device tokens for push notifications
 */
class DeviceTokenService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function registerToken(
        string $userId,
        string $token,
        string $platform,
        ?string $deviceName = null,
        ?string $deviceModel = null,
        ?string $osVersion = null,
        ?string $appVersion = null
    ) {
        $existing = $this->findExistingTokenByValue($token);
        if ($existing !== null) {
            $this->updateExistingToken($existing, $userId, $platform);
            return $existing;
        }
        return $this->createNewToken($userId, $token, $platform, $deviceName, $deviceModel, $osVersion, $appVersion);
    }

    public function getToken(string $tokenId)
    {
        return $this->em->getRepository(DeviceToken::class)->find($tokenId);
    }

    public function getTokenByValue(string $tokenValue)
    {
        return $this->em->getRepository(DeviceToken::class)->findOneBy(['token' => $tokenValue]);
    }

    public function getUserTokens(string $userId)
    {
        return $this->em->getRepository(DeviceToken::class)->findBy(['userId' => $userId, 'status' => 'ACTIVE']);
    }

    public function getTokensByPlatform(string $platform)
    {
        return $this->em->getRepository(DeviceToken::class)->findBy(['platform' => $platform, 'status' => 'ACTIVE']);
    }

    public function revokeToken(string $tokenId)
    {
        $token = $this->getToken($tokenId);
        if ($token === null) return false;
        $token->setStatus('REVOKED');
        $this->em->flush();
        $this->logger->info('Device token revoked', ['token_id' => $tokenId, 'user_id' => $token->getUserId()]);
        return true;
    }

    public function revokeTokenByValue(string $tokenValue)
    {
        $token = $this->getTokenByValue($tokenValue);
        if ($token === null) return false;
        $token->setStatus('REVOKED');
        $this->em->flush();
        $this->logger->info('Device token revoked by value', ['token' => substr($tokenValue, 0, 10) . '...', 'user_id' => $token->getUserId()]);
        return true;
    }

    public function revokeAllUserTokens(string $userId)
    {
        $tokens = $this->getUserTokens($userId);
        $count = count($tokens);
        foreach ($tokens as $token) $token->setStatus('REVOKED');
        $this->em->flush();
        $this->logger->info('All user device tokens revoked', ['user_id' => $userId, 'count' => $count]);
        return $count;
    }

    public function updateLastUsed(string $tokenId)
    {
        $token = $this->getToken($tokenId);
        if ($token === null || !$token->isActive()) return false;
        $token->setLastUsedAt(new \DateTime());
        $this->em->flush();
        return true;
    }

    public function countByPlatform(string $platform)
    {
        return $this->em->getRepository(DeviceToken::class)->count(['platform' => $platform, 'status' => 'ACTIVE']);
    }

    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    private function findExistingTokenByValue(string $token): ?DeviceToken
    {
        return $this->em->getRepository(DeviceToken::class)->findOneBy(['token' => $token]);
    }

    private function updateExistingToken(DeviceToken $token, string $userId, string $platform): void
    {
        $token->setLastUsedAt(new \DateTime());
        $this->em->flush();
        $this->logger->info('Device token updated', ['token_id' => $token->getId(), 'user_id' => $userId, 'platform' => $platform]);
    }

    private function createNewToken(
        string $userId,
        string $token,
        string $platform,
        ?string $deviceName,
        ?string $deviceModel,
        ?string $osVersion,
        ?string $appVersion
    ): DeviceToken {
        $deviceToken = $this->buildDeviceTokenEntity($userId, $token, $platform, $deviceName, $deviceModel, $osVersion, $appVersion);
        $this->em->persist($deviceToken);
        $this->em->flush();
        $this->logger->info('Device token registered', ['token_id' => $deviceToken->getId(), 'user_id' => $userId, 'platform' => $platform]);
        return $deviceToken;
    }

    private function buildDeviceTokenEntity(
        string $userId,
        string $token,
        string $platform,
        ?string $deviceName,
        ?string $deviceModel,
        ?string $osVersion,
        ?string $appVersion
    ): DeviceToken {
        $deviceToken = new DeviceToken($this->generateId(), $userId, $token, $platform);
        $deviceToken->setDeviceName($deviceName);
        $deviceToken->setDeviceModel($deviceModel);
        $deviceToken->setOsVersion($osVersion);
        $deviceToken->setAppVersion($appVersion);
        return $deviceToken;
    }
}
