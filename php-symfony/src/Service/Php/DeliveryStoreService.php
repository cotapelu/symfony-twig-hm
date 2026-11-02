<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\DeliveryStore;
use App\Repository\Php\DeliveryStoreRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryStoreService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DeliveryStoreRepository $repository
    ) {}

    public function createDeliveryStore(
        string $ownerId,
        string $type,
        string $name,
        ?string $license = null,
        ?array $metadata = null
    ) {
        if (empty(trim($ownerId))) {
            throw new \InvalidArgumentException('Owner ID is required');
        }
        if (empty(trim($type))) {
            throw new \InvalidArgumentException('Type is required');
        }
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Name is required');
        }

        $id = bin2hex(random_bytes(16));
        $store = new DeliveryStore($id, $ownerId, $type, $name);
        $store->setLicense($license);
        $store->setMetadata($metadata);

        $this->em->persist($store);
        $this->em->flush();

        return $store;
    }

    public function getDeliveryStore(string $id)
    {
        $store = $this->repository->find($id);
        if (!$store) {
            throw new \RuntimeException('DeliveryStore not found');
        }
        return $store;
    }

    public function updateDeliveryStore(string $id, array $data)
    {
        $store = $this->getDeliveryStore($id);
        $this->applyDeliveryStoreFields($store, $data);
        $this->em->flush();
        return $store;
    }

    private function applyDeliveryStoreFields(DeliveryStore $store, array $data): void
    {
        $this->applyOwnerId($store, $data);
        $this->applyType($store, $data);
        $this->applyName($store, $data);
        $this->applyLicense($store, $data);
        $this->applyMetadata($store, $data);
        $this->applyStatus($store, $data);
    }

    private function applyOwnerId(DeliveryStore $store, array $data): void
    {
        if (!array_key_exists('ownerId', $data)) {
            return;
        }
        $ownerId = trim($data['ownerId']);
        if ($ownerId === '') {
            throw new \InvalidArgumentException('Owner ID cannot be empty');
        }
        $store->setOwnerId($ownerId);
    }

    private function applyType(DeliveryStore $store, array $data): void
    {
        if (!array_key_exists('type', $data)) {
            return;
        }
        $type = trim($data['type']);
        if ($type === '') {
            throw new \InvalidArgumentException('Type cannot be empty');
        }
        $store->setType($type);
    }

    private function applyName(DeliveryStore $store, array $data): void
    {
        if (!array_key_exists('name', $data)) {
            return;
        }
        $name = trim($data['name']);
        if ($name === '') {
            throw new \InvalidArgumentException('Name cannot be empty');
        }
        $store->setName($name);
    }

    private function applyLicense(DeliveryStore $store, array $data): void
    {
        if (array_key_exists('license', $data)) {
            $store->setLicense($data['license']);
        }
    }

    private function applyMetadata(DeliveryStore $store, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $store->setMetadata($data['metadata']);
        }
    }

    private function applyStatus(DeliveryStore $store, array $data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }
        $status = trim($data['status']);
        if ($status === '') {
            throw new \InvalidArgumentException('Status cannot be empty');
        }
        $store->setStatus($status);
    }


    public function deleteDeliveryStore(string $id)
    {
        $store = $this->getDeliveryStore($id);
        $this->em->remove($store);
        $this->em->flush();
    }

    public function findByOwnerId(string $ownerId)
    {
        return $this->repository->findBy(['ownerId' => $ownerId]);
    }

    public function findByType(string $type)
    {
        return $this->repository->findBy(['type' => $type]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }
}
