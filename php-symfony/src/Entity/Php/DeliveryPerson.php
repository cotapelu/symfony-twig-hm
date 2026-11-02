<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_people')]
#[ORM\Index(name: 'idx_delivery_people_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_delivery_people_status', columns: ['status'])]
#[ORM\Index(name: 'idx_delivery_people_vehicle_id', columns: ['vehicle_id'])]
class DeliveryPerson
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20)]
    private string $phone;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $vehicleId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $currentLocation = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $userId, string $name, string $phone)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->phone = $phone;
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

    public function setUserId(string $userId)
    {
        $this->userId = $userId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone(string $phone)
    {
        $this->phone = $phone;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getVehicleId()
    {
        return $this->vehicleId;
    }

    public function setVehicleId(?string $vehicleId)
    {
        $this->vehicleId = $vehicleId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCurrentLocation()
    {
        return $this->currentLocation;
    }

    public function setCurrentLocation(?array $currentLocation)
    {
        $this->currentLocation = $currentLocation;
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

    public function isActive()
    {
        return $this->status === 'ACTIVE';
    }

    public function isOffline()
    {
        return $this->status === 'OFFLINE';
    }

    public function isBusy()
    {
        return $this->status === 'BUSY';
    }

    public function isSuspended()
    {
        return $this->status === 'SUSPENDED';
    }

    public function hasVehicle()
    {
        return $this->vehicleId !== null;
    }

    public function hasCurrentLocation()
    {
        return $this->currentLocation !== null;
    }
}
