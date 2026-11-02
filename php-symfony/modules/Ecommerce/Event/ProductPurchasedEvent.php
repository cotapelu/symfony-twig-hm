<?php

declare(strict_types=1);

namespace App\Ecommerce\Event;

use App\Shared\DomainEvent\DomainEvent;
use DateTimeImmutable;

class ProductPurchasedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $userId,
        public readonly string $productId,
        public readonly int $quantity,
        public readonly float $totalPrice,
        public readonly ?DateTimeImmutable $createdAt = null,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'product.purchased';
    }

    protected function eventData(): array
    {
        return [
            'orderId' => $this->orderId,
            'userId' => $this->userId,
            'productId' => $this->productId,
            'quantity' => $this->quantity,
            'totalPrice' => $this->totalPrice,
        ];
    }
}
