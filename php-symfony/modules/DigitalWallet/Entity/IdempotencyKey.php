<?php

namespace App\DigitalWallet\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'idempotency_keys')]
#[ORM\UniqueConstraint(name: 'uniq_key', columns: ['idempotency_key'])]
#[Module(name: 'DigitalWallet', dependencies: ['DigitalWallet'])]
class IdempotencyKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'idempotency_key', type: 'string', length: 255, unique: true)]
    private string $key;

    #[ORM\Column(name: 'entity_type', type: 'string', length: 100)]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', type: 'string', length: 255)]
    private string $entityId;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: true)]
    private ?\DateTime $expiresAt = null;

    public function __construct(string $key, string $entityType, string $entityId, ?\DateTime $expiresAt = null)
    {
        $this->key = $key;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->createdAt = new \DateTime();
        $this->expiresAt = $expiresAt;
    }

    public function getId() { return $this->id; }
    public function getKey() { return $this->key; }
    public function getEntityType() { return $this->entityType; }
    public function getEntityId() { return $this->entityId; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getExpiresAt() { return $this->expiresAt; }

    public function isExpired()
    {
        if ($this->expiresAt === null) {
            return false;
        }
        return $this->expiresAt < new \DateTime();
    }
}
