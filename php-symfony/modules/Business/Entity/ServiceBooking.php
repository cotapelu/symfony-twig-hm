<?php

declare(strict_types=1);

namespace App\Business\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'service_bookings')]
#[ORM\HasLifecycleCallbacks]
class ServiceBooking
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $provider;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $customer;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $serviceType = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $scheduledAt = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(User $provider, User $customer)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->provider = $provider;
        $this->customer = $customer;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function setProvider(User $provider)
    {
        $this->provider = $provider;
        return $this;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer(User $customer)
    {
        $this->customer = $customer;
        return $this;
    }

    public function getServiceType()
    {
        return $this->serviceType;
    }

    public function setServiceType(?string $serviceType)
    {
        $this->serviceType = $serviceType;
        return $this;
    }

    public function getScheduledAt()
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTime $scheduledAt)
    {
        $this->scheduledAt = $scheduledAt;
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

    public function getData()
    {
        return $this->data;
    }

    public function setData(?array $data)
    {
        $this->data = $data;
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

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
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
            'providerId' => $this->provider->getId(),
            'customerId' => $this->customer->getId(),
            'serviceType' => $this->serviceType,
            'scheduledAt' => $this->scheduledAt?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'data' => $this->data,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
