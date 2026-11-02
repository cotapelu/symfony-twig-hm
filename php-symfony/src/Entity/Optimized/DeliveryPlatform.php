<?php

declare(strict_types=1);

namespace App\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_stores')]
#[ORM\Index(name: 'idx_delivery_stores_owner', columns: ['owner_id'])]
#[ORM\Index(name: 'idx_delivery_stores_type', columns: ['type'])]
#[ORM\Index(name: 'idx_delivery_stores_status', columns: ['status'])]
#[HasLifecycleCallbacks]
class DeliveryStore
{
    public const TYPE_RESTAURANT = 'RESTAURANT';
    public const TYPE_PHARMACY = 'PHARMACY';
    public const TYPE_GROCERY = 'GROCERY';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'owner_id', type: 'string', length: 36)]
    private string $ownerId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $license = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $ownerId, string $type, string $name)
    {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->type = $type;
        $this->name = $name;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getOwnerId() { return $this->ownerId; }
    public function getType() { return $this->type; }
    public function getName() { return $this->name; }
    public function getLicense() { return $this->license; }
    public function getStatus() { return $this->status; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function setMetadata(?array $m) { $this->metadata = $m; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_products')]
#[ORM\Index(name: 'idx_delivery_products_store', columns: ['store_id'])]
#[ORM\Index(name: 'idx_delivery_products_category', columns: ['category'])]
#[HasLifecycleCallbacks]
class DeliveryProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'store_id', type: 'string', length: 36)]
    private string $storeId;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'price_cents', type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(string $id, string $storeId, string $name, int $priceCents)
    {
        $this->id = $id;
        $this->storeId = $storeId;
        $this->name = $name;
        $this->priceCents = $priceCents;
    }

    public function getId() { return $this->id; }
    public function getStoreId() { return $this->storeId; }
    public function getCategory() { return $this->category; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getPriceCents() { return $this->priceCents; }
    public function getStock() { return $this->stock; }
    public function isActive() { return $this->isActive; }
    public function getMetadata() { return $this->metadata; }

    public function setStock(int $stock) { $this->stock = $stock; return $this; }
    public function setIsActive(bool $active) { $this->isActive = $active; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_orders')]
#[ORM\Index(name: 'idx_delivery_orders_store', columns: ['store_id'])]
#[ORM\Index(name: 'idx_delivery_orders_customer', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_delivery_orders_status', columns: ['status'])]
#[ORM\Index(name: 'idx_delivery_orders_created', columns: ['created_at'])]
#[HasLifecycleCallbacks]
class DeliveryOrder
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_PREPARING = 'PREPARING';
    public const STATUS_READY = 'READY';
    public const STATUS_PICKED_UP = 'PICKED_UP';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'store_id', type: 'string', length: 36)]
    private string $storeId;

    #[ORM\Column(name: 'customer_id', type: 'string', length: 36)]
    private string $customerId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'delivery_address', type: 'text')]
    private string $deliveryAddress;

    #[ORM\Column(name: 'delivery_person_id', type: 'string', length: 36, nullable: true)]
    private ?string $deliveryPersonId = null;

    #[ORM\Column(name: 'total_cents', type: 'integer')]
    private int $totalCents;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $storeId, string $customerId, string $type, string $address, int $totalCents)
    {
        $this->id = $id;
        $this->storeId = $storeId;
        $this->customerId = $customerId;
        $this->type = $type;
        $this->deliveryAddress = $address;
        $this->totalCents = $totalCents;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getStoreId() { return $this->storeId; }
    public function getCustomerId() { return $this->customerId; }
    public function getType() { return $this->type; }
    public function getStatus() { return $this->status; }
    public function getDeliveryAddress() { return $this->deliveryAddress; }
    public function getDeliveryPersonId() { return $this->deliveryPersonId; }
    public function getTotalCents() { return $this->totalCents; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setStatus(string $status) { $this->status = $status; $this->updatedAt = new \DateTime(); return $this; }
    public function setDeliveryPersonId(?string $id) { $this->deliveryPersonId = $id; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_order_items')]
#[ORM\Index(name: 'idx_delivery_order_items_order', columns: ['order_id'])]
#[ORM\Index(name: 'idx_delivery_order_items_product', columns: ['product_id'])]
class DeliveryOrderItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'order_id', type: 'string', length: 36)]
    private string $orderId;

    #[ORM\Column(name: 'product_id', type: 'string', length: 36)]
    private string $productId;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'price_cents', type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct(string $id, string $orderId, string $productId, int $quantity, int $priceCents)
    {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->priceCents = $priceCents;
    }

    public function getId() { return $this->id; }
    public function getOrderId() { return $this->orderId; }
    public function getProductId() { return $this->productId; }
    public function getQuantity() { return $this->quantity; }
    public function getPriceCents() { return $this->priceCents; }
    public function getNotes() { return $this->notes; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_people')]
#[ORM\Index(name: 'idx_delivery_people_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_delivery_people_status', columns: ['status'])]
class DeliveryPerson
{
    public const STATUS_AVAILABLE = 'AVAILABLE';
    public const STATUS_BUSY = 'BUSY';
    public const STATUS_OFFLINE = 'OFFLINE';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'vehicle_id', type: 'string', length: 36, nullable: true)]
    private ?string $vehicleId = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_OFFLINE;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $location = null;

    public function __construct(string $id, string $userId, string $type)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function getStatus() { return $this->status; }
    public function getLocation() { return $this->location; }

    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function setLocation(?array $loc) { $this->location = $loc; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_vehicles')]
#[ORM\Index(name: 'idx_delivery_vehicles_driver', columns: ['driver_id'])]
class DeliveryVehicle
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'driver_id', type: 'string', length: 36)]
    private string $driverId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'license_plate', type: 'string', length: 20)]
    private string $licensePlate;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    public function __construct(string $id, string $driverId, string $type, string $licensePlate)
    {
        $this->id = $id;
        $this->driverId = $driverId;
        $this->type = $type;
        $this->licensePlate = $licensePlate;
    }

    public function getId() { return $this->id; }
    public function getDriverId() { return $this->driverId; }
    public function getType() { return $this->type; }
    public function getLicensePlate() { return $this->licensePlate; }
    public function getStatus() { return $this->status; }
}