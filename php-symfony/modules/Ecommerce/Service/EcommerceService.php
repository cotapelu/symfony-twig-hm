<?php

namespace App\Ecommerce\Service;

use App\Ecommerce\Entity\EcommerceProduct;
use App\Ecommerce\Entity\EcommerceOrder;
use App\Ecommerce\Repository\EcommerceProductRepository;
use App\Ecommerce\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EcommerceService
{
    private EcommerceProductRepository $productRepository;
    private OrderService $orderService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        EcommerceProductRepository $productRepository,
        OrderService $orderService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->orderService = $orderService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function createProduct(array $data): EcommerceProduct
    {
        $this->validateProductData($data);

        $product = new EcommerceProduct();
        $this->applyBasicFields($product, $data);
        $this->applyOptionalFields($product, $data);

        $this->productRepository->save($product);
        return $product;
    }

    public function getProduct(string $id): ?EcommerceProduct
    {
        return $this->productRepository->findById($id);
    }

    public function getSellerProducts(string $sellerId, int $limit = 20, int $offset = 0): array
    {
        return $this->productRepository->findBySeller($sellerId, $limit, $offset);
    }

    public function getProductsByCategory(string $category, int $limit = 20, int $offset = 0): array
    {
        return $this->productRepository->findByCategory($category, $limit, $offset);
    }

    public function searchProducts(string $query, int $limit = 20, int $offset = 0): array
    {
        return $this->productRepository->searchByName($query, $limit, $offset);
    }

    public function getProducts(int $limit = 20, int $offset = 0): array
    {
        return $this->productRepository->findActive($limit, $offset);
    }

    public function countActiveProducts(): int
    {
        return $this->productRepository->countActive();
    }

    public function deleteProduct(string $id, ?string $sellerId = null): bool
    {
        $product = $this->entityManager->find(EcommerceProduct::class, $id);
        if (!$product) {
            return false;
        }
        if ($sellerId !== null && $product->getSellerId() !== $sellerId) {
            throw new \InvalidArgumentException('Access denied: not product owner');
        }
        $this->entityManager->remove($product);
        $this->entityManager->flush();
        return true;
    }

    public function activateProduct(string $id, ?string $sellerId = null): ?EcommerceProduct
    {
        $product = $this->productRepository->findById($id);
        if ($product) {
            if ($sellerId !== null && $product->getSellerId() !== $sellerId) {
                throw new \InvalidArgumentException('Access denied: not product owner');
            }
            $product->setIsActive(true);
            $product->setUpdatedAt(new \DateTime());
            $this->productRepository->save($product);
        }
        return $product;
    }

    public function deactivateProduct(string $id, ?string $sellerId = null): ?EcommerceProduct
    {
        $product = $this->productRepository->findById($id);
        if ($product) {
            if ($sellerId !== null && $product->getSellerId() !== $sellerId) {
                throw new \InvalidArgumentException('Access denied: not product owner');
            }
            $product->setIsActive(false);
            $product->setUpdatedAt(new \DateTime());
            $this->productRepository->save($product);
        }
        return $product;
    }

    public function updateProduct(string $id, array $data): ?EcommerceProduct
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return null;
        }

        if (isset($data['seller_id']) && $product->getSellerId() !== $data['seller_id']) {
            throw new \InvalidArgumentException('Access denied: not product owner');
        }

        $this->applyUpdateFields($product, $data);
        $product->setUpdatedAt(new \DateTime());
        $this->productRepository->save($product);
        return $product;
    }

    public function updateStock(string $id, int $quantity, ?string $sellerId = null): ?EcommerceProduct
    {
        $product = $this->productRepository->findById($id);
        if ($product) {
            if ($sellerId !== null && $product->getSellerId() !== $sellerId) {
                throw new \InvalidArgumentException('Access denied: not product owner');
            }
            $product->setStock($quantity);
            $product->setUpdatedAt(new \DateTime());
            $this->productRepository->save($product);
        }
        return $product;
    }

    public function getFeaturedProducts(int $limit = 10): array
    {
        $products = $this->productRepository->findActive($limit * 2);
        usort($products, fn($a, $b) => $b->getPrice() <=> $a->getPrice());
        return array_slice($products, 0, $limit);
    }

    public function createOrder(array $data)
    {
        return $this->orderService->createOrder(
            $data['user_id'],
            $data['product_id'],
            $data['quantity'],
            $data['shipping_address'] ?? null,
            $data['idempotency_key'] ?? null
        );
    }

    public function getOrder(string $orderId, string $userId)
    {
        return $this->orderService->getOrder($orderId, $userId);
    }

    public function confirmOrder(string $orderId, string $userId)
    {
        return $this->orderService->confirmOrder($orderId, $userId);
    }

    public function cancelOrder(string $orderId, string $userId)
    {
        return $this->orderService->cancelOrder($orderId, $userId);
    }

    public function getUserOrders(string $userId, int $limit = 20)
    {
        return $this->orderService->getUserOrders($userId, $limit);
    }

    private function validateProductData(array $data): void
    {
        if (empty($data['seller_id'])) {
            throw new \InvalidArgumentException('seller_id is required');
        }
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('name is required');
        }
        if (!isset($data['price'])) {
            throw new \InvalidArgumentException('price is required');
        }
    }

    private function applyBasicFields(EcommerceProduct $product, array $data): void
    {
        $product->setSellerId($data['seller_id']);
        $product->setName($data['name']);
        $product->setPrice((float)$data['price']);
    }

    private function applyOptionalFields(EcommerceProduct $product, array $data): void
    {
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        if (isset($data['category'])) {
            $product->setCategory($data['category']);
        }
        if (isset($data['stock'])) {
            $product->setStock((int)$data['stock']);
        }
        if (isset($data['images'])) {
            $product->setImages($data['images']);
        }
        if (isset($data['attributes'])) {
            $product->setAttributes($data['attributes']);
        }
        if (isset($data['status'])) {
            $product->setIsActive($data['status'] === 'active');
        }
    }

    private function applyUpdateFields(EcommerceProduct $product, array $data): void
    {
        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name !== '') {
                $product->setName($name);
            }
        }
        if (isset($data['price'])) {
            $product->setPrice((float)$data['price']);
        }
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        if (isset($data['sale_price'])) {
            $product->setSalePrice($data['sale_price']);
        }
        if (isset($data['stock'])) {
            $product->setStock((int)$data['stock']);
        }
        if (isset($data['images'])) {
            $product->setImages($data['images']);
        }
        if (isset($data['attributes'])) {
            $product->setAttributes($data['attributes']);
        }
        if (isset($data['status'])) {
            $product->setIsActive($data['status'] === 'active');
        }
    }
}
