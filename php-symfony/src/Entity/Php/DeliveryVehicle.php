<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_vehicles')]
#[ORM\Index(name: 'idx_delivery_vehicles_type', columns: ['type'])]
#[ORM\Index(name: 'idx_delivery_vehicles_status', columns: ['status'])]
class DeliveryVehicle
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 100)]
    private string $model;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $plateNumber = null;

    #[ORM\Column(type: 'integer')]
    private int $capacity;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $type, string $model, int $capacity)
    {
        $this->id = $id;
        $this->type = $type;
        $this->model = $model;
        $this->capacity = $capacity;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setModel(string $model)
    {
        $this->model = $model;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPlateNumber()
    {
        return $this->plateNumber;
    }

    public function setPlateNumber(?string $plateNumber)
    {
        $this->plateNumber = $plateNumber;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCapacity()
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity)
    {
        $this->capacity = $capacity;
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
}
