<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\BusinessCatalog;
use App\Repository\Php\BusinessCatalogRepository;
use Doctrine\ORM\EntityManagerInterface;

class BusinessCatalogService
{
    public function __construct(
        private EntityManagerInterface $em,
        private BusinessCatalogRepository $repository
    ) {}

    public function createBusinessCatalog(
        string $businessId,
        string $name,
        string $category,
        ?string $description = null,
        ?array $items = null,
        ?array $metadata = null
    ) {
        if (empty(trim($businessId))) {
            throw new \InvalidArgumentException('Business ID is required');
        }
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Name is required');
        }
        if (empty(trim($category))) {
            throw new \InvalidArgumentException('Category is required');
        }

        $id = bin2hex(random_bytes(16));
        $catalog = new BusinessCatalog($id, $businessId, $name, $category);
        $catalog->setDescription($description);
        $catalog->setItems($items);
        $catalog->setMetadata($metadata);

        $this->em->persist($catalog);
        $this->em->flush();

        return $catalog;
    }

    public function getBusinessCatalog(string $id)
    {
        $catalog = $this->repository->find($id);
        if (!$catalog) {
            throw new \RuntimeException('BusinessCatalog not found');
        }
        return $catalog;
    }

    public function updateBusinessCatalog(string $id, array $data)
    {
        $catalog = $this->getBusinessCatalog($id);
        $this->applyBusinessCatalogFields($catalog, $data);
        $this->em->flush();
        return $catalog;
    }

    private function applyBusinessCatalogFields(BusinessCatalog $catalog, array $data): void
    {
        $this->applyBusinessId($catalog, $data);
        $this->applyName($catalog, $data);
        $this->applyCategory($catalog, $data);
        $this->applyDescription($catalog, $data);
        $this->applyItems($catalog, $data);
        $this->applyMetadata($catalog, $data);
        $this->applyStatus($catalog, $data);
    }

    private function applyBusinessId(BusinessCatalog $catalog, array $data): void
    {
        if (!array_key_exists('businessId', $data)) {
            return;
        }
        $businessId = trim($data['businessId']);
        if ($businessId === '') {
            throw new \InvalidArgumentException('Business ID cannot be empty');
        }
        $catalog->setBusinessId($businessId);
    }

    private function applyName(BusinessCatalog $catalog, array $data): void
    {
        if (!array_key_exists('name', $data)) {
            return;
        }
        $name = trim($data['name']);
        if ($name === '') {
            throw new \InvalidArgumentException('Name cannot be empty');
        }
        $catalog->setName($name);
    }

    private function applyCategory(BusinessCatalog $catalog, array $data): void
    {
        if (!array_key_exists('category', $data)) {
            return;
        }
        $category = trim($data['category']);
        if ($category === '') {
            throw new \InvalidArgumentException('Category cannot be empty');
        }
        $catalog->setCategory($category);
    }

    private function applyDescription(BusinessCatalog $catalog, array $data): void
    {
        if (array_key_exists('description', $data)) {
            $catalog->setDescription($data['description']);
        }
    }

    private function applyItems(BusinessCatalog $catalog, array $data): void
    {
        if (array_key_exists('items', $data)) {
            $catalog->setItems($data['items']);
        }
    }

    private function applyMetadata(BusinessCatalog $catalog, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $catalog->setMetadata($data['metadata']);
        }
    }

    private function applyStatus(BusinessCatalog $catalog, array $data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }
        $status = trim($data['status']);
        if ($status === '') {
            throw new \InvalidArgumentException('Status cannot be empty');
        }
        $catalog->setStatus($status);
    }

    public function deleteBusinessCatalog(string $id)
    {
        $catalog = $this->getBusinessCatalog($id);
        $this->em->remove($catalog);
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

    public function addItemToCatalog(string $catalogId, array $item)
    {
        $catalog = $this->getBusinessCatalog($catalogId);
        $currentItems = $catalog->getItems() ?? [];
        $currentItems[] = $item;
        $catalog->setItems($currentItems);
        $this->em->flush();

        return $catalog;
    }

    public function removeItemFromCatalog(string $catalogId, int $itemIndex)
    {
        $catalog = $this->getBusinessCatalog($catalogId);
        $items = $catalog->getItems() ?? [];

        if (!array_key_exists($itemIndex, $items)) {
            throw new \InvalidArgumentException('Item not found at index');
        }

        array_splice($items, $itemIndex, 1);
        $catalog->setItems($items);
        $this->em->flush();

        return $catalog;
    }
}
