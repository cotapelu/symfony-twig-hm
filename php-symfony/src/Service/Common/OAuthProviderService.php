<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\OAuthProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing OAuth provider connections
 */
class OAuthProviderService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function linkProvider(
        string $userId,
        string $provider,
        string $providerUserId,
        ?string $accessToken = null,
        ?string $refreshToken = null,
        ?\DateTime $tokenExpiresAt = null,
        ?string $scope = null,
        ?array $metadata = null
    ) {
        $existing = $this->findExistingLink($userId, $provider, $providerUserId);
        if ($existing !== null) {
            $this->updateExistingLink($existing, $accessToken, $refreshToken, $tokenExpiresAt, $scope, $metadata);
            return $existing;
        }
        return $this->createNewLink($userId, $provider, $providerUserId, $accessToken, $refreshToken, $tokenExpiresAt, $scope, $metadata);
    }

    public function getProvider(string $id)
    {
        return $this->em->getRepository(OAuthProvider::class)->find($id);
    }

    public function getUserProvider(string $userId, string $provider)
    {
        return $this->em->getRepository(OAuthProvider::class)->findOneBy(['userId' => $userId, 'provider' => $provider]);
    }

    public function getUserProviders(string $userId)
    {
        return $this->em->getRepository(OAuthProvider::class)->findBy(['userId' => $userId]);
    }

    public function hasProvider(string $userId, string $provider)
    {
        return $this->getUserProvider($userId, $provider) !== null;
    }

    public function unlinkProvider(string $userId, string $provider)
    {
        $oauthProvider = $this->getUserProvider($userId, $provider);
        if ($oauthProvider === null) return false;
        $this->em->remove($oauthProvider);
        $this->em->flush();
        $this->logger->info('OAuth provider unlinked', ['user_id' => $userId, 'provider' => $provider]);
        return true;
    }

    public function updateAccessToken(
        string $userId,
        string $provider,
        string $accessToken,
        ?string $refreshToken = null,
        ?\DateTime $tokenExpiresAt = null
    ) {
        $oauthProvider = $this->getUserProvider($userId, $provider);
        if ($oauthProvider === null) return false;
        $oauthProvider->setAccessToken($accessToken);
        $oauthProvider->setRefreshToken($refreshToken);
        $oauthProvider->setTokenExpiresAt($tokenExpiresAt);
        $this->em->flush();
        $this->logger->info('OAuth access token updated', ['user_id' => $userId, 'provider' => $provider]);
        return true;
    }

    public function needsTokenRefresh(string $userId, string $provider)
    {
        $oauthProvider = $this->getUserProvider($userId, $provider);
        if ($oauthProvider === null) return false;
        return $oauthProvider->needsRefresh();
    }

    public function getProvidersNeedingRefresh(string $userId)
    {
        $providers = $this->getUserProviders($userId);
        $needsRefresh = [];
        foreach ($providers as $provider) {
            if ($provider->needsRefresh()) $needsRefresh[] = $provider;
        }
        return $needsRefresh;
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

    private function findExistingLink(string $userId, string $provider, string $providerUserId): ?OAuthProvider
    {
        return $this->em->getRepository(OAuthProvider::class)->findOneBy([
            'userId' => $userId,
            'provider' => $provider,
            'providerUserId' => $providerUserId,
        ]);
    }

    private function updateExistingLink(
        OAuthProvider $existing,
        ?string $accessToken,
        ?string $refreshToken,
        ?\DateTime $tokenExpiresAt,
        ?string $scope,
        ?array $metadata
    ): void {
        $existing->setAccessToken($accessToken);
        $existing->setRefreshToken($refreshToken);
        $existing->setTokenExpiresAt($tokenExpiresAt);
        $existing->setScope($scope);
        $existing->setMetadata($metadata);
        $this->em->flush();
        $this->logger->info('OAuth provider link updated', [
            'user_id' => $existing->getUserId(),
            'provider' => $existing->getProvider(),
        ]);
    }

    private function createNewLink(
        string $userId,
        string $provider,
        string $providerUserId,
        ?string $accessToken,
        ?string $refreshToken,
        ?\DateTime $tokenExpiresAt,
        ?string $scope,
        ?array $metadata
    ): OAuthProvider {
        $oauthProvider = $this->buildOAuthProviderEntity($userId, $provider, $providerUserId, $accessToken, $refreshToken, $tokenExpiresAt, $scope, $metadata);
        $this->em->persist($oauthProvider);
        $this->em->flush();
        $this->logger->info('OAuth provider linked', ['user_id' => $userId, 'provider' => $provider]);
        return $oauthProvider;
    }

    private function buildOAuthProviderEntity(
        string $userId,
        string $provider,
        string $providerUserId,
        ?string $accessToken,
        ?string $refreshToken,
        ?\DateTime $tokenExpiresAt,
        ?string $scope,
        ?array $metadata
    ): OAuthProvider {
        $oauthProvider = new OAuthProvider(
            $this->generateId(),
            $userId,
            $provider,
            $providerUserId
        );
        $this->applyOAuthProviderDetails($oauthProvider, $accessToken, $refreshToken, $tokenExpiresAt, $scope, $metadata);
        return $oauthProvider;
    }

    private function applyOAuthProviderDetails(
        OAuthProvider $provider,
        ?string $accessToken,
        ?string $refreshToken,
        ?\DateTime $tokenExpiresAt,
        ?string $scope,
        ?array $metadata
    ): void {
        $provider->setAccessToken($accessToken);
        $provider->setRefreshToken($refreshToken);
        $provider->setTokenExpiresAt($tokenExpiresAt);
        $provider->setScope($scope);
        $provider->setMetadata($metadata);
    }
}
