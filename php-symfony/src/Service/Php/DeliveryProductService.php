<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\DeliveryProduct;
use App\Repository\Php\DeliveryProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryProductService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DeliveryProductRepository $repository
    ) {}

    public function createDeliveryProduct(
        string $storeId,
        string $category,
        string $name,
        int $priceCents,
        int $stock,
        ?string $description = null,
        ?bool $isActive = true,
        ?array $metadata = null
    ) {
        $this->validateCreateInput($storeId, $category, $name, $priceCents, $stock);
        $product = $this->buildProductEntity($storeId, $category, $name, $priceCents, $stock, $description, $isActive, $metadata);
        $this->em->persist($product);
        $this->em->flush();
        return $product;
    }

    public function getDeliveryProduct(string $id)
    {
        $product = $this->repository->find($id);
        if (!$product) throw new \RuntimeException('DeliveryProduct not found');
        return $product;
    }

    public function updateDeliveryProduct(string $id, array $data)
    {
        $product = $this->getDeliveryProduct($id);
        $this->applyProductFields($product, $data);
        $this->em->flush();
        return $product;
    }

    public function deleteDeliveryProduct(string $id)
    {
        $product = $this->getDeliveryProduct($id);
        $this->em->remove($product);
        $this->em->flush();
    }

    public function findByStoreId(string $storeId)
    {
        return $this->repository->findBy(['storeId' => $storeId]);
    }

    public function findByCategory(string $category)
    {
        return $this->repository->findBy(['category' => $category]);
    }

    public function findByIsActive(bool $isActive = true)
    {
        return $this->repository->findBy(['isActive' => $isActive]);
    }

    private function validateCreateInput(string $storeId, string $category, string $name, int $priceCents, int $stock): void
    {
        if (empty(trim($storeId))) throw new \InvalidArgumentException('Store ID is required');
        if (empty(trim($category))) throw new \InvalidArgumentException('Category is required');
        if (empty(trim($name))) throw new \InvalidArgumentException('Name is required');
        if ($priceCents <= 0) throw new \InvalidArgumentException('Price must be positive');
        if ($stock < 0) throw new \InvalidArgumentException('Stock cannot be negative');
    }

    private function buildProductEntity(
        string $storeId,
        string $category,
        string $name,
        int $priceCents,
        int $stock,
        ?string $description,
        ?bool $isActive,
        ?array $metadata
    ): DeliveryProduct {
        $product = new DeliveryProduct(
            bin2hex(random_bytes(16)),
            $storeId,
            $category,
            $name,
            $priceCents,
            $stock
        );
        $product->setDescription($description);
        $product->setActive($isActive);
        $product->setMetadata($metadata);
        return $product;
    }

    private function applyProductFields($product, array $data): void
    {
        $this->applyStoreId($product, $data);
        $this->applyCategory($product, $data);
        $this->applyName($product, $data);
        $this->applyPriceCents($product, $data);
        $this->applyStock($product, $data);
        $this->applyDescription($product, $data);
        $this->applyIsActive($product, $data);
        $this->applyMetadata($product, $data);
    }

    private function applyStoreId($product, array $data): void
    {
        if (!array_key_exists('storeId', $data)) return;
        $storeId = trim($data['storeId']);
        if ($storeId === '') throw new \InvalidArgumentException('Store ID cannot be empty');
        $product->setStoreId($storeId);
    }

    private function applyCategory($product, array $data): void
    {
        if (!array_key_exists('category', $data)) return;
        $category = trim($data['category']);
        if ($category === '') throw new \InvalidArgumentException('Category cannot be empty');
        $product->setCategory($category);
    }

    private function applyName($product, array $data): void
    {
        if (!array_key_exists('name', $data)) return;
        $name = trim($data['name']);
        if ($name === '') throw new \InvalidArgumentException('Name cannot be empty');
        $product->setName($name);
    }

    private function applyPriceCents($product, array $data): void
    {
        if (!array_key_exists('priceCents', $data)) return;
        $price = (int)$data['priceCents'];
        if ($price <= 0) throw new \InvalidArgumentException('Price must be positive');
        $product->setPriceCents($price);
    }

    private function applyStock($product, array $data): void
    {
        if (!array_key_exists('stock', $data)) return;
        $stock = (int)$data['stock'];
        if ($stock < 0) throw new \InvalidArgumentException('Stock cannot be negative');
        $product->setStock($stock);
    }

    private function applyDescription($product, array $data): void
    {
        if (array_key_exists('description', $data)) {
            $product->setDescription($data['description']);
        }
    }

    private function applyIsActive($product, array $data): void
    {
        if (array_key_exists('isActive', $data)) {
            $product->setActive((bool)$data['isActive']);
        }
    }

    private function applyMetadata($product, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $product->setMetadata($data['metadata']);
        }
    }
}
