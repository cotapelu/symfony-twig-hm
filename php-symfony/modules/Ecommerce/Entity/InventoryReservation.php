<?php

declare(strict_types=1);

namespace App\Ecommerce\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_reservations')]
class InventoryReservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $productId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $sessionId;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $reservedAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $expiresAt;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    public function __construct(
        string $productId,
        string $sessionId,
        int $quantity,
        int $reservationMinutes = 15
    ) {
        $this->productId = $productId;
        $this->sessionId = $sessionId;
        $this->quantity = $quantity;
        $this->reservedAt = new \DateTime();
        $this->expiresAt = (new \DateTime())->modify("+{$reservationMinutes} minutes");
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function getSessionId()
    {
        return $this->sessionId;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getReservedAt()
    {
        return $this->reservedAt;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isExpired()
    {
        return new \DateTime() > $this->expiresAt;
    }

    public function confirm()
    {
        $this->status = 'confirmed';
    }

    public function cancel()
    {
        $this->status = 'cancelled';
    }

    public function expire()
    {
        $this->status = 'expired';
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'session_id' => $this->sessionId,
            'quantity' => $this->quantity,
            'reserved_at' => $this->reservedAt->format('Y-m-d H:i:s'),
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'is_expired' => $this->isExpired(),
        ];
    }
}