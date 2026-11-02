<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\EcommerceOrder;
use App\Repository\Php\EcommerceOrderRepository;
use Doctrine\ORM\EntityManagerInterface;

class EcommerceOrderService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EcommerceOrderRepository $repository
    ) {}

    public function createOrder(
        string $customerId,
        int $totalCents,
        ?string $currency = 'USD',
        ?array $shippingAddress = null,
        ?array $billingAddress = null,
        ?array $metadata = null
    ) {
        if (empty(trim($customerId))) {
            throw new \InvalidArgumentException('Customer ID is required');
        }
        if ($totalCents <= 0) {
            throw new \InvalidArgumentException('Total must be positive');
        }
        if (empty(trim($currency))) {
            throw new \InvalidArgumentException('Currency cannot be empty');
        }

        $id = bin2hex(random_bytes(16));
        $order = new EcommerceOrder($id, $customerId, $totalCents);
        $order->setCurrency($currency);
        $order->setShippingAddress($shippingAddress);
        $order->setBillingAddress($billingAddress);
        $order->setMetadata($metadata);

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    public function getOrder(string $id)
    {
        $order = $this->repository->find($id);
        if (!$order) {
            throw new \RuntimeException('EcommerceOrder not found');
        }
        return $order;
    }

    public function updateOrder(string $id, array $data)
    {
        $order = $this->getOrder($id);
        $this->applyOrderFields($order, $data);
        $this->em->flush();
        return $order;
    }

    private function applyOrderFields($order, array $data): void
    {
        $this->applyCustomerId($order, $data);
        $this->applyTotalCents($order, $data);
        $this->applyStatus($order, $data);
        $this->applyCurrency($order, $data);
        $this->applyShippingAddress($order, $data);
        $this->applyBillingAddress($order, $data);
        $this->applyMetadata($order, $data);
    }

    private function applyCustomerId($order, array $data): void
    {
        if (!array_key_exists('customerId', $data)) {
            return;
        }
        $customerId = trim($data['customerId']);
        if ($customerId === '') {
            throw new \InvalidArgumentException('Customer ID cannot be empty');
        }
        $order->setCustomerId($customerId);
    }

    private function applyTotalCents($order, array $data): void
    {
        if (!array_key_exists('totalCents', $data)) {
            return;
        }
        $total = (int)$data['totalCents'];
        if ($total <= 0) {
            throw new \InvalidArgumentException('Total must be positive');
        }
        $order->setTotalCents($total);
    }

    private function applyStatus($order, array $data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }
        $status = trim($data['status']);
        if ($status === '') {
            throw new \InvalidArgumentException('Status cannot be empty');
        }
        $order->setStatus($status);
    }

    private function applyCurrency($order, array $data): void
    {
        if (!array_key_exists('currency', $data)) {
            return;
        }
        $currency = trim($data['currency']);
        if ($currency === '') {
            throw new \InvalidArgumentException('Currency cannot be empty');
        }
        $order->setCurrency($currency);
    }

    private function applyShippingAddress($order, array $data): void
    {
        if (array_key_exists('shippingAddress', $data)) {
            $order->setShippingAddress($data['shippingAddress']);
        }
    }

    private function applyBillingAddress($order, array $data): void
    {
        if (array_key_exists('billingAddress', $data)) {
            $order->setBillingAddress($data['billingAddress']);
        }
    }

    private function applyMetadata($order, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $order->setMetadata($data['metadata']);
        }
    }

    public function deleteOrder(string $id)
    {
        $order = $this->getOrder($id);
        $this->em->remove($order);
        $this->em->flush();
    }

    public function findByCustomerId(string $customerId)
    {
        return $this->repository->findBy(['customerId' => $customerId]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }
}
