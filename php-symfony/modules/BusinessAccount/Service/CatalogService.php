<?php

declare(strict_types=1);

namespace App\BusinessAccount\Service;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\CatalogItem;
use App\BusinessAccount\Repository\CatalogItemRepository;
use Doctrine\ORM\EntityManagerInterface;

class CatalogService
{
    private EntityManagerInterface $em;
    private CatalogItemRepository $catalogRepo;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        /** @var CatalogItemRepository $catalogRepo */
        $catalogRepo = $em->getRepository(CatalogItem::class);
        $this->catalogRepo = $catalogRepo;
    }

    public function createCatalogItem(Business $business, array $data)
    {
        $item = new CatalogItem();
        $item->setBusiness($business);
        $item->setName($data['name']);
        $item->setDescription($data['description'] ?? null);
        $item->setPrice($data['price']);
        $item->setCurrency($data['currency'] ?? 'VND');
        $item->setCategory($data['category'] ?? null);
        $item->setImageUrl($data['imageUrl'] ?? null);
        $item->setStock($data['stock'] ?? null);
        $item->setIsActive(true);

        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }

    public function updateCatalogItem(CatalogItem $item, array $data)
    {
        if (isset($data['name'])) {
            $item->setName($data['name']);
        }
        if (isset($data['description'])) {
            $item->setDescription($data['description']);
        }
        if (isset($data['price'])) {
            $item->setPrice($data['price']);
        }
        if (isset($data['currency'])) {
            $item->setCurrency($data['currency']);
        }
        if (isset($data['category'])) {
            $item->setCategory($data['category']);
        }
        if (isset($data['imageUrl'])) {
            $item->setImageUrl($data['imageUrl']);
        }
        if (isset($data['stock'])) {
            $item->setStock($data['stock']);
        }
        if (isset($data['isActive'])) {
            $item->setIsActive($data['isActive']);
        }

        $this->em->flush();

        return $item;
    }

    public function deleteCatalogItem(CatalogItem $item)
    {
        $this->em->remove($item);
        $this->em->flush();
        return $item;
    }

    public function getBusinessCatalog(Business $business)
    {
        return $this->catalogRepo->findByBusiness($business->getId());
    }
}
