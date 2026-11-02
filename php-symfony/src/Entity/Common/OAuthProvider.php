<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity]
#[ORM\Table(name: 'common.oauth_providers')]
#[ORM\Index(name: 'idx_oauth_providers_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_oauth_providers_provider', columns: ['provider'])]
#[ORM\UniqueConstraint(name: 'uq_oauth_provider_user_provider', columns: ['user_id', 'provider', 'provider_user_id'])]
class OAuthProvider
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $provider;

    #[ORM\Column(type: 'string', length: 255)]
    private string $providerUserId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $tokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $scope = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $userId, string $provider, string $providerUserId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->provider = $provider;
        $this->providerUserId = $providerUserId;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function getProviderUserId()
    {
        return $this->providerUserId;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken)
    {
        $this->accessToken = $accessToken;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken)
    {
        $this->refreshToken = $refreshToken;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTokenExpiresAt()
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?\DateTime $tokenExpiresAt)
    {
        $this->tokenExpiresAt = $tokenExpiresAt;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setScope(?string $scope)
    {
        $this->scope = $scope;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isTokenExpired()
    {
        return $this->tokenExpiresAt !== null && new \DateTime() > $this->tokenExpiresAt;
    }

    public function needsRefresh()
    {
        return $this->refreshToken !== null && $this->isTokenExpired();
    }
}
