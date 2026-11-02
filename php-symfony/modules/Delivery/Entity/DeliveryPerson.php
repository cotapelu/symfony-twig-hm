<?php

declare(strict_types=1);

namespace App\Delivery\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'delivery_people')]
#[ORM\HasLifecycleCallbacks]
class DeliveryPerson
{
    public const TYPE_FOOD = 'FOOD';
    public const TYPE_PHARMACY = 'PHARMACY';
    public const TYPE_GROCERY = 'GROCERY';

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_BUSY = 'busy';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_INACTIVE = 'inactive';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $vehicleId = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_AVAILABLE;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $location = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(User $user, string $type)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->user = $user;
        $this->type = $type;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function getVehicleId()
    {
        return $this->vehicleId;
    }

    public function setVehicleId(?string $vehicleId)
    {
        $this->vehicleId = $vehicleId;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation(?array $location)
    {
        $this->location = $location;
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
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

    public function isAvailable()
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isBusy()
    {
        return $this->status === self::STATUS_BUSY;
    }

    public function isOffline()
    {
        return $this->status === self::STATUS_OFFLINE;
    }

    public function updateLocation(float $latitude, float $longitude)
    {
        $dt = new \DateTime();
        $this->location = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'lastUpdated' => $dt->format('Y-m-d H:i:s')
        ];
        return $this;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->user->getId(),
            'type' => $this->type,
            'vehicleId' => $this->vehicleId,
            'status' => $this->status,
            'location' => $this->location,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
