<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity;

use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'oauth_providers')]
#[ORM\UniqueConstraint(name: 'uq_oauth_provider', columns: ['provider', 'provider_user_id'])]
#[ORM\Index(name: 'idx_oauth_providers_user_id', columns: ['user_id'])]
class OAuthProvider
{
    use IdentifiableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $provider;

    #[ORM\Column(name: 'provider_user_id', type: 'string', length: 255)]
    private string $providerUserId;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $provider, string $providerUserId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->provider = $provider;
        $this->providerUserId = $providerUserId;
        $this->createdAt = new \DateTime();
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getProviderUserId(): string
    {
        return $this->providerUserId;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}