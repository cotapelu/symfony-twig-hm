<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity;

use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use App\OptimizedSchema\Entity\Trait\TimestampableTrait;
use App\OptimizedSchema\Entity\Trait\StatusAwareTrait;
use App\OptimizedSchema\Entity\Trait\OwnedEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'delivery_stores')]
#[ORM\Index(name: 'idx_delivery_stores_owner_id', columns: ['owner_id'])]
#[ORM\Index(name: 'idx_delivery_stores_type', columns: ['type'])]
#[ORM\Index(name: 'idx_delivery_stores_status', columns: ['status'])]
class DeliveryStore
{
    use IdentifiableTrait;
    use TimestampableTrait;
    use StatusAwareTrait;
    use OwnedEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'owner_id', type: 'uuid')]
    private string $ownerId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $license;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public const TYPE_RESTAURANT = 'RESTAURANT';
    public const TYPE_PHARMACY = 'PHARMACY';
    public const TYPE_GROCERY = 'GROCERY';

    public function __construct(string $id, string $ownerId, string $type, string $name)
    {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->type = $type;
        $this->name = $name;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->status = 'active';
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLicense(): ?string
    {
        return $this->license;
    }

    public function setLicense(?string $license): self
    {
        $this->license = $license;
        return $this;
    }
}