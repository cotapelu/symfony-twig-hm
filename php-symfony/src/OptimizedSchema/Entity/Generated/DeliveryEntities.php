<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity\Generated;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'delivery_order_items')]
#[ORM\Index(name: 'idx_delivery_order_items_order_id', columns: ['order_id'])]
#[ORM\Index(name: 'idx_delivery_order_items_product_id', columns: ['product_id'])]
class DeliveryOrderItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'order_id', type: 'uuid')]
    private string $orderId;

    #[ORM\Column(name: 'product_id', type: 'uuid', nullable: true)]
    private ?string $productId;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'integer')]
    private int $price;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $orderId, int $quantity, int $price)
    {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->quantity = $quantity;
        $this->price = $price;
        $this->createdAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getOrderId(): string { return $this->orderId; }
    public function getProductId(): ?string { return $this->productId; }
    public function setProductId(?string $productId): self { $this->productId = $productId; return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = $quantity; return $this; }
    public function getPrice(): int { return $this->price; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): self { $this->notes = $notes; return $this; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'delivery_people')]
#[ORM\Index(name: 'idx_delivery_people_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_delivery_people_status', columns: ['status'])]
class DeliveryPerson
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'vehicle_id', type: 'uuid', nullable: true)]
    private ?string $vehicleId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'offline';

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $location = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $userId, string $type)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getType(): string { return $this->type; }
    public function getVehicleId(): ?string { return $this->vehicleId; }
    public function setVehicleId(?string $vehicleId): self { $this->vehicleId = $vehicleId; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getLocation(): array { return $this->location; }
    public function setLocation(array $location): self { $this->location = $location; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'delivery_vehicles')]
#[ORM\Index(name: 'idx_delivery_vehicles_driver_id', columns: ['driver_id'])]
class DeliveryVehicle
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'driver_id', type: 'uuid', nullable: true)]
    private ?string $driverId;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $type;

    #[ORM\Column(name: 'license_plate', type: 'string', length: 20, nullable: true)]
    private ?string $licensePlate;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $model;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->createdAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getDriverId(): ?string { return $this->driverId; }
    public function setDriverId(?string $driverId): self { $this->driverId = $driverId; return $this; }
    public function getLicensePlate(): ?string { return $this->licensePlate; }
    public function setLicensePlate(?string $licensePlate): self { $this->licensePlate = $licensePlate; return $this; }
    public function getModel(): ?string { return $this->model; }
    public function setModel(?string $model): self { $this->model = $model; return $this; }
    public function getStatus(): string { return $this->status; }
}