<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\DeliveryOrder;
use App\Repository\Php\DeliveryOrderRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryOrderService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DeliveryOrderRepository $repository
    ) {}

    public function createDeliveryOrder(
        string $storeId,
        string $customerId,
        string $type,
        int $totalCents,
        ?array $deliveryAddress = null,
        ?string $deliveryPersonId = null,
        ?string $status = 'PENDING',
        ?array $metadata = null
    ) {
        $this->validateCreateInput($storeId, $customerId, $type, $totalCents);
        $order = $this->buildOrderEntity($storeId, $customerId, $type, $totalCents, $deliveryAddress, $deliveryPersonId, $status, $metadata);
        $this->em->persist($order);
        $this->em->flush();
        return $order;
    }

    public function getDeliveryOrder(string $id)
    {
        $order = $this->repository->find($id);
        if (!$order) throw new \RuntimeException('DeliveryOrder not found');
        return $order;
    }

    public function updateDeliveryOrder(string $id, array $data)
    {
        $order = $this->getDeliveryOrder($id);
        $this->applyDeliveryOrderFields($order, $data);
        $this->em->flush();
        return $order;
    }

    public function deleteDeliveryOrder(string $id)
    {
        $order = $this->getDeliveryOrder($id);
        $this->em->remove($order);
        $this->em->flush();
    }

    public function findByStoreId(string $storeId)
    {
        return $this->repository->findBy(['storeId' => $storeId]);
    }

    public function findByCustomerId(string $customerId)
    {
        return $this->repository->findBy(['customerId' => $customerId]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }

    public function findByDeliveryPersonId(string $deliveryPersonId)
    {
        return $this->repository->findBy(['deliveryPersonId' => $deliveryPersonId]);
    }

    private function validateCreateInput(string $storeId, string $customerId, string $type, int $totalCents): void
    {
        if (empty(trim($storeId))) throw new \InvalidArgumentException('Store ID is required');
        if (empty(trim($customerId))) throw new \InvalidArgumentException('Customer ID is required');
        if (empty(trim($type))) throw new \InvalidArgumentException('Order type is required');
        if ($totalCents <= 0) throw new \InvalidArgumentException('Total must be positive');
    }

    private function buildOrderEntity(
        string $storeId,
        string $customerId,
        string $type,
        int $totalCents,
        ?array $deliveryAddress,
        ?string $deliveryPersonId,
        ?string $status,
        ?array $metadata
    ): DeliveryOrder {
        $order = new DeliveryOrder(
            bin2hex(random_bytes(16)),
            $storeId,
            $customerId,
            $type,
            $totalCents
        );
        $order->setDeliveryAddress($deliveryAddress);
        $order->setDeliveryPersonId($deliveryPersonId);
        $order->setStatus($status);
        $order->setMetadata($metadata);
        return $order;
    }

    private function applyDeliveryOrderFields(DeliveryOrder $order, array $data): void
    {
        $this->applyStoreId($order, $data);
        $this->applyCustomerId($order, $data);
        $this->applyType($order, $data);
        $this->applyTotalCents($order, $data);
        $this->applyDeliveryAddress($order, $data);
        $this->applyDeliveryPersonId($order, $data);
        $this->applyStatus($order, $data);
        $this->applyMetadata($order, $data);
    }

    private function applyStoreId(DeliveryOrder $order, array $data): void
    {
        if (!array_key_exists('storeId', $data)) return;
        $storeId = trim($data['storeId']);
        if ($storeId === '') throw new \InvalidArgumentException('Store ID cannot be empty');
        $order->setStoreId($storeId);
    }

    private function applyCustomerId(DeliveryOrder $order, array $data): void
    {
        if (!array_key_exists('customerId', $data)) return;
        $customerId = trim($data['customerId']);
        if ($customerId === '') throw new \InvalidArgumentException('Customer ID cannot be empty');
        $order->setCustomerId($customerId);
    }

    private function applyType(DeliveryOrder $order, array $data): void
    {
        if (!array_key_exists('type', $data)) return;
        $type = trim($data['type']);
        if ($type === '') throw new \InvalidArgumentException('Order type cannot be empty');
        $order->setType($type);
    }

    private function applyTotalCents(DeliveryOrder $order, array $data): void
    {
        if (!array_key_exists('totalCents', $data)) return;
        $total = (int)$data['totalCents'];
        if ($total <= 0) throw new \InvalidArgumentException('Total must be positive');
        $order->setTotalCents($total);
    }

    private function applyDeliveryAddress(DeliveryOrder $order, array $data): void
    {
        if (array_key_exists('deliveryAddress', $data)) {
            $order->setDeliveryAddress($data['deliveryAddress']);
        }
    }

    private function applyDeliveryPersonId(DeliveryOrder $order, array $data): void
    {
        if (array_key_exists('deliveryPersonId', $data)) {
            $order->setDeliveryPersonId($data['deliveryPersonId']);
        }
    }

    private function applyStatus(DeliveryOrder $order, array $data): void
    {
        if (!array_key_exists('status', $data)) return;
        $status = trim($data['status']);
        if ($status === '') throw new \InvalidArgumentException('Status cannot be empty');
        $order->setStatus($status);
    }

    private function applyMetadata(DeliveryOrder $order, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $order->setMetadata($data['metadata']);
        }
    }
}
