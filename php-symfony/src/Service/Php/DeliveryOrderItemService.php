<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\DeliveryOrderItem;
use App\Repository\Php\DeliveryOrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryOrderItemService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DeliveryOrderItemRepository $repository
    ) {}

    public function createOrderItem(
        string $orderId,
        string $productId,
        int $quantity,
        int $priceCents,
        ?string $notes = null
    ) {
        if (empty(trim($orderId))) throw new \InvalidArgumentException('Order ID is required');
        if (empty(trim($productId))) throw new \InvalidArgumentException('Product ID is required');
        if ($quantity <= 0) throw new \InvalidArgumentException('Quantity must be positive');
        if ($priceCents <= 0) throw new \InvalidArgumentException('Price must be positive');

        $id = bin2hex(random_bytes(16));
        $item = new DeliveryOrderItem($id, $orderId, $productId, $quantity, $priceCents);
        $item->setNotes($notes);
        $this->em->persist($item);
        $this->em->flush();
        return $item;
    }

    public function getOrderItem(string $id)
    {
        $item = $this->repository->find($id);
        if (!$item) throw new \RuntimeException('DeliveryOrderItem not found');
        return $item;
    }

    public function updateOrderItem(string $id, array $data)
    {
        $item = $this->getOrderItem($id);
        isset($data['orderId']) && $this->applyOrderIdUpdate($item, $data['orderId']);
        isset($data['productId']) && $this->applyProductIdUpdate($item, $data['productId']);
        isset($data['quantity']) && $this->applyQuantityUpdate($item, $data['quantity']);
        isset($data['priceCents']) && $this->applyPriceCentsUpdate($item, $data['priceCents']);
        array_key_exists('notes', $data) && $item->setNotes($data['notes']);
        $this->em->flush();
        return $item;
    }

    public function deleteOrderItem(string $id)
    {
        $item = $this->getOrderItem($id);
        $this->em->remove($item);
        $this->em->flush();
    }

    public function findByOrderId(string $orderId)
    {
        return $this->repository->findBy(['orderId' => $orderId]);
    }

    public function findByProductId(string $productId)
    {
        return $this->repository->findBy(['productId' => $productId]);
    }

    private function applyOrderIdUpdate(DeliveryOrderItem $item, mixed $orderId): void
    {
        $orderId = trim($orderId);
        if ($orderId === '') throw new \InvalidArgumentException('Order ID cannot be empty');
        $item->setOrderId($orderId);
    }

    private function applyProductIdUpdate(DeliveryOrderItem $item, mixed $productId): void
    {
        $productId = trim($productId);
        if ($productId === '') throw new \InvalidArgumentException('Product ID cannot be empty');
        $item->setProductId($productId);
    }

    private function applyQuantityUpdate(DeliveryOrderItem $item, mixed $quantity): void
    {
        $qty = (int)$quantity;
        if ($qty <= 0) throw new \InvalidArgumentException('Quantity must be positive');
        $item->setQuantity($qty);
    }

    private function applyPriceCentsUpdate(DeliveryOrderItem $item, mixed $priceCents): void
    {
        $price = (int)$priceCents;
        if ($price <= 0) throw new \InvalidArgumentException('Price must be positive');
        $item->setPriceCents($price);
    }
}
