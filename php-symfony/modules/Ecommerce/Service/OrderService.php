<?php

declare(strict_types=1);

namespace App\Ecommerce\Service;

use App\Ecommerce\Entity\EcommerceProduct;
use App\Ecommerce\Entity\EcommerceOrder;
use App\Ecommerce\Repository\EcommerceProductRepository;
use App\Ecommerce\Repository\EcommerceOrderRepository;
use App\DigitalWallet\Entity\IdempotencyKey;
use App\DigitalWallet\Repository\IdempotencyKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OrderService
{
    private EntityManagerInterface $entityManager;
    private EcommerceProductRepository $productRepository;
    private EcommerceOrderRepository $orderRepository;
    private IdempotencyKeyRepository $idempotencyRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        EcommerceProductRepository $productRepository,
        EcommerceOrderRepository $orderRepository,
        IdempotencyKeyRepository $idempotencyRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->idempotencyRepository = $idempotencyRepository;
        $this->logger = $logger;
    }

    public function createOrder(
        string $userId,
        string $productId,
        int $quantity,
        ?string $shippingAddress = null,
        ?string $idempotencyKey = null
    ) {
        $this->validateOrderInput($quantity);
        $existing = $this->checkIdempotency($idempotencyKey);
        if ($existing) return $existing;
        return $this->executeInTransaction(function () use ($userId, $productId, $quantity, $shippingAddress) {
            $product = $this->loadProduct($productId);
            $this->ensureStockAvailable($product, $quantity);
            $this->reserveStock($product, $quantity);
            return $this->createOrderEntity($userId, $product, $quantity, $shippingAddress);
        }, $idempotencyKey);
    }

    private function validateOrderInput(int $quantity)
    {
        if ($quantity <= 0) throw new \InvalidArgumentException('Quantity must be positive');
    }

    private function checkIdempotency(?string $key)
    {
        if ($key === null) return null;
        $existingKey = $this->idempotencyRepository->findByKey($key);
        if ($existingKey && !$existingKey->isExpired() && $existingKey->getEntityType() === 'EcommerceOrder') {
            return $this->orderRepository->find($existingKey->getEntityId());
        }
        return null;
    }

    private function loadProduct(string $productId)
    {
        $product = $this->productRepository->findById($productId);
        if (!$product) throw new \Exception('Product not found');
        return $product;
    }

    private function ensureStockAvailable(EcommerceProduct $product, int $quantity)
    {
        if ($product->getStock() < $quantity) throw new \Exception('Insufficient stock');
    }

    private function reserveStock(EcommerceProduct $product, int $quantity)
    {
        $affected = $this->productRepository->decreaseStock($product->getId(), $quantity);
        if ($affected === 0) {
            throw new \Exception('Stock concurrently updated. Please retry.');
        }
    }

    private function createOrderEntity(string $userId, EcommerceProduct $product, int $quantity, ?string $shippingAddress)
    {
        $order = new EcommerceOrder();
        $order->setId(\Ramsey\Uuid\Uuid::uuid4()->toString());
        $order->setUserId($userId);
        $order->setProductId($product->getId());
        $order->setQuantity($quantity);
        $order->setTotalPrice($product->getPrice() * $quantity);
        $order->setCurrency($product->getCurrency());
        $order->setShippingAddress($shippingAddress ? json_decode($shippingAddress, true) : null);
        $order->setStatus('pending');
        $order->setCreatedAt(new \DateTime());
        $order->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->logger->info('Order created', [
            'order_id' => $order->getId(),
            'product_id' => $product->getId(),
            'quantity' => $quantity,
            'user_id' => $userId
        ]);

        return $order;
    }

    private function executeInTransaction(callable $operation, ?string $idempotencyKey)
    {
        $this->entityManager->beginTransaction();
        try {
            $result = $operation();
            $this->entityManager->commit();
            $this->storeIdempotencyIfNeeded($idempotencyKey, $result);
            return $result;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Order operation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function storeIdempotencyIfNeeded(?string $key, EcommerceOrder $order)
    {
        if ($key === null) return;
        $expires = new \DateTime('+24 hours');
        $keyEntity = new IdempotencyKey($key, 'EcommerceOrder', $order->getId(), $expires);
        $this->idempotencyRepository->save($keyEntity);
    }

    public function confirmOrder(string $orderId, string $userId)
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            throw new \Exception('Order not found');
        }
        if ($order->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Forbidden: cannot confirm order');
        }
        if ($order->getStatus() !== 'pending') {
            throw new \Exception('Order cannot be confirmed from current status');
        }
        $order->setStatus('confirmed');
        $order->setUpdatedAt(new \DateTime());
        $this->orderRepository->save($order);
        $this->logger->info('Order confirmed', ['order_id' => $orderId, 'user_id' => $userId]);
        return $order;
    }

    public function cancelOrder(string $orderId, string $userId, bool $isAdmin = false)
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            throw new \Exception('Order not found');
        }
        if (!$isAdmin && $order->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Forbidden: cannot cancel order');
        }
        if ($order->getStatus() !== 'pending') {
            throw new \Exception('Order cannot be cancelled from current status');
        }
        $order->setStatus('cancelled');
        $order->setUpdatedAt(new \DateTime());
        $this->orderRepository->save($order);
        $this->logger->info('Order cancelled', ['order_id' => $orderId, 'user_id' => $userId]);
        return $order;
    }

    public function getOrder(string $orderId, string $userId, bool $isAdmin = false)
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            return null;
        }
        if (!$isAdmin && $order->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Forbidden: cannot access order');
        }
        return $order;
    }

    public function getUserOrders(string $userId, int $limit = 20)
    {
        return $this->orderRepository->findByUser($userId, $limit);
    }
}