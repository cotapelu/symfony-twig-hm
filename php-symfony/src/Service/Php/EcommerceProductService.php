<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\EcommerceProduct;
use App\Repository\Php\EcommerceProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class EcommerceProductService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EcommerceProductRepository $repository
    ) {}

    public function createEcommerceProduct(
        string $businessId,
        string $name,
        string $category,
        int $priceCents,
        ?string $description = null,
        ?int $stockQuantity = 0,
        ?array $attributes = null,
        ?array $metadata = null
    ) {
        $this->validateCreateInput($businessId, $name, $category, $priceCents);
        $product = $this->buildProductEntity($businessId, $name, $category, $priceCents, $description, $stockQuantity, $attributes, $metadata);
        $this->em->persist($product);
        $this->em->flush();
        return $product;
    }

    public function getEcommerceProduct(string $id)
    {
        $product = $this->repository->find($id);
        if (!$product) throw new \RuntimeException('EcommerceProduct not found');
        return $product;
    }

    public function updateEcommerceProduct(string $id, array $data)
    {
        $product = $this->getEcommerceProduct($id);
        $this->applyProductFields($product, $data);
        $this->em->flush();
        return $product;
    }

    public function deleteEcommerceProduct(string $id)
    {
        $product = $this->getEcommerceProduct($id);
        $this->em->remove($product);
        $this->em->flush();
    }

    public function findByBusinessId(string $businessId)
    {
        return $this->repository->findBy(['businessId' => $businessId]);
    }

    public function findByCategory(string $category)
    {
        return $this->repository->findBy(['category' => $category]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }

    private function validateCreateInput(string $businessId, string $name, string $category, int $priceCents): void
    {
        if (empty(trim($businessId))) throw new \InvalidArgumentException('Business ID is required');
        if (empty(trim($name))) throw new \InvalidArgumentException('Name is required');
        if (empty(trim($category))) throw new \InvalidArgumentException('Category is required');
        if ($priceCents <= 0) throw new \InvalidArgumentException('Price must be positive');
    }

    private function buildProductEntity(
        string $businessId,
        string $name,
        string $category,
        int $priceCents,
        ?string $description,
        ?int $stockQuantity,
        ?array $attributes,
        ?array $metadata
    ): EcommerceProduct {
        $product = new EcommerceProduct(
            bin2hex(random_bytes(16)),
            $businessId,
            $name,
            $category,
            $priceCents
        );
        $product->setDescription($description);
        $product->setStockQuantity($stockQuantity ?? 0);
        $product->setAttributes($attributes);
        $product->setMetadata($metadata);
        return $product;
    }

    private function applyProductFields($product, array $data): void
    {
        $this->applyBusinessId($product, $data);
        $this->applyName($product, $data);
        $this->applyCategory($product, $data);
        $this->applyPriceCents($product, $data);
        $this->applyDescription($product, $data);
        $this->applyStockQuantity($product, $data);
        $this->applyAttributes($product, $data);
        $this->applyMetadata($product, $data);
        $this->applyStatus($product, $data);
    }

    private function applyBusinessId($product, array $data): void
    {
        if (!array_key_exists('businessId', $data)) return;
        $businessId = trim($data['businessId']);
        if ($businessId === '') throw new \InvalidArgumentException('Business ID cannot be empty');
        $product->setBusinessId($businessId);
    }

    private function applyName($product, array $data): void
    {
        if (!array_key_exists('name', $data)) return;
        $name = trim($data['name']);
        if ($name === '') throw new \InvalidArgumentException('Name cannot be empty');
        $product->setName($name);
    }

    private function applyCategory($product, array $data): void
    {
        if (!array_key_exists('category', $data)) return;
        $category = trim($data['category']);
        if ($category === '') throw new \InvalidArgumentException('Category cannot be empty');
        $product->setCategory($category);
    }

    private function applyPriceCents($product, array $data): void
    {
        if (!array_key_exists('priceCents', $data)) return;
        $price = (int)$data['priceCents'];
        if ($price <= 0) throw new \InvalidArgumentException('Price must be positive');
        $product->setPriceCents($price);
    }

    private function applyDescription($product, array $data): void
    {
        if (array_key_exists('description', $data)) {
            $product->setDescription($data['description']);
        }
    }

    private function applyStockQuantity($product, array $data): void
    {
        if (!array_key_exists('stockQuantity', $data)) return;
        $stock = (int)$data['stockQuantity'];
        if ($stock < 0) throw new \InvalidArgumentException('Stock quantity cannot be negative');
        $product->setStockQuantity($stock);
    }

    private function applyAttributes($product, array $data): void
    {
        if (array_key_exists('attributes', $data)) {
            $product->setAttributes($data['attributes']);
        }
    }

    private function applyMetadata($product, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $product->setMetadata($data['metadata']);
        }
    }

    private function applyStatus($product, array $data): void
    {
        if (!array_key_exists('status', $data)) return;
        $status = trim($data['status']);
        if ($status === '') throw new \InvalidArgumentException('Status cannot be empty');
        $product->setStatus($status);
    }
}
