<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\DeliveryVehicle;
use App\Repository\Php\DeliveryVehicleRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryVehicleService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DeliveryVehicleRepository $repository
    ) {}

    public function createVehicle(
        string $type,
        string $model,
        int $capacity,
        ?string $plateNumber = null,
        ?string $status = 'ACTIVE',
        ?array $metadata = null
    ) {
        if (empty(trim($type))) {
            throw new \InvalidArgumentException('Vehicle type is required');
        }
        if (empty(trim($model))) {
            throw new \InvalidArgumentException('Vehicle model is required');
        }
        if ($capacity <= 0) {
            throw new \InvalidArgumentException('Capacity must be positive');
        }

        $id = bin2hex(random_bytes(16));
        $vehicle = new DeliveryVehicle($id, $type, $model, $capacity);
        $vehicle->setPlateNumber($plateNumber);
        $vehicle->setStatus($status);
        $vehicle->setMetadata($metadata);

        $this->em->persist($vehicle);
        $this->em->flush();

        return $vehicle;
    }

    public function getVehicle(string $id)
    {
        $vehicle = $this->repository->find($id);
        if (!$vehicle) {
            throw new \RuntimeException('DeliveryVehicle not found');
        }
        return $vehicle;
    }

    public function updateVehicle(string $id, array $data)
    {
        $vehicle = $this->getVehicle($id);
        $this->applyVehicleFields($vehicle, $data);
        $this->em->flush();
        return $vehicle;
    }

    private function applyVehicleFields($vehicle, array $data): void
    {
        $this->applyType($vehicle, $data);
        $this->applyModel($vehicle, $data);
        $this->applyCapacity($vehicle, $data);
        $this->applyPlateNumber($vehicle, $data);
        $this->applyStatus($vehicle, $data);
        $this->applyMetadata($vehicle, $data);
    }

    private function applyType($vehicle, array $data): void
    {
        if (!array_key_exists('type', $data)) {
            return;
        }
        $type = trim($data['type']);
        if ($type === '') {
            throw new \InvalidArgumentException('Vehicle type cannot be empty');
        }
        $vehicle->setType($type);
    }

    private function applyModel($vehicle, array $data): void
    {
        if (!array_key_exists('model', $data)) {
            return;
        }
        $model = trim($data['model']);
        if ($model === '') {
            throw new \InvalidArgumentException('Vehicle model cannot be empty');
        }
        $vehicle->setModel($model);
    }

    private function applyCapacity($vehicle, array $data): void
    {
        if (!array_key_exists('capacity', $data)) {
            return;
        }
        $cap = (int)$data['capacity'];
        if ($cap <= 0) {
            throw new \InvalidArgumentException('Capacity must be positive');
        }
        $vehicle->setCapacity($cap);
    }

    private function applyPlateNumber($vehicle, array $data): void
    {
        if (array_key_exists('plateNumber', $data)) {
            $vehicle->setPlateNumber($data['plateNumber']);
        }
    }

    private function applyStatus($vehicle, array $data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }
        $status = trim($data['status']);
        if ($status === '') {
            throw new \InvalidArgumentException('Status cannot be empty');
        }
        $vehicle->setStatus($status);
    }

    private function applyMetadata($vehicle, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $vehicle->setMetadata($data['metadata']);
        }
    }

    public function deleteVehicle(string $id)
    {
        $vehicle = $this->getVehicle($id);
        $this->em->remove($vehicle);
        $this->em->flush();
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
