<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\DeliveryPerson;
use App\Repository\Php\DeliveryPersonRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryPersonService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DeliveryPersonRepository $repository
    ) {}

    public function createDeliveryPerson(
        string $userId,
        string $name,
        string $phone,
        ?string $vehicleId = null,
        ?array $currentLocation = null,
        ?array $metadata = null
    ) {
        if (empty(trim($userId))) {
            throw new \InvalidArgumentException('User ID is required');
        }
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Name is required');
        }
        if (empty(trim($phone))) {
            throw new \InvalidArgumentException('Phone is required');
        }

        $id = bin2hex(random_bytes(16));
        $person = new DeliveryPerson($id, $userId, $name, $phone);
        $person->setVehicleId($vehicleId);
        $person->setCurrentLocation($currentLocation);
        $person->setMetadata($metadata);

        $this->em->persist($person);
        $this->em->flush();

        return $person;
    }

    public function getDeliveryPerson(string $id)
    {
        $person = $this->repository->find($id);
        if (!$person) {
            throw new \RuntimeException('DeliveryPerson not found');
        }
        return $person;
    }

    public function updateDeliveryPerson(string $id, array $data)
    {
        $person = $this->getDeliveryPerson($id);
        $this->applyPersonFields($person, $data);
        $this->em->flush();
        return $person;
    }

    private function applyPersonFields($person, array $data): void
    {
        $this->applyUserId($person, $data);
        $this->applyName($person, $data);
        $this->applyPhone($person, $data);
        $this->applyVehicleId($person, $data);
        $this->applyCurrentLocation($person, $data);
        $this->applyMetadata($person, $data);
        $this->applyStatus($person, $data);
    }

    private function applyUserId($person, array $data): void
    {
        if (!array_key_exists('userId', $data)) {
            return;
        }
        $userId = trim($data['userId']);
        if ($userId === '') {
            throw new \InvalidArgumentException('User ID cannot be empty');
        }
        $person->setUserId($userId);
    }

    private function applyName($person, array $data): void
    {
        if (!array_key_exists('name', $data)) {
            return;
        }
        $name = trim($data['name']);
        if ($name === '') {
            throw new \InvalidArgumentException('Name cannot be empty');
        }
        $person->setName($name);
    }

    private function applyPhone($person, array $data): void
    {
        if (!array_key_exists('phone', $data)) {
            return;
        }
        $phone = trim($data['phone']);
        if ($phone === '') {
            throw new \InvalidArgumentException('Phone cannot be empty');
        }
        $person->setPhone($phone);
    }

    private function applyVehicleId($person, array $data): void
    {
        if (array_key_exists('vehicleId', $data)) {
            $person->setVehicleId($data['vehicleId']);
        }
    }

    private function applyCurrentLocation($person, array $data): void
    {
        if (array_key_exists('currentLocation', $data)) {
            $person->setCurrentLocation($data['currentLocation']);
        }
    }

    private function applyMetadata($person, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $person->setMetadata($data['metadata']);
        }
    }

    private function applyStatus($person, array $data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }
        $status = trim($data['status']);
        if ($status === '') {
            throw new \InvalidArgumentException('Status cannot be empty');
        }
        $person->setStatus($status);
    }

    public function deleteDeliveryPerson(string $id)
    {
        $person = $this->getDeliveryPerson($id);
        $this->em->remove($person);
        $this->em->flush();
    }

    public function findByUserId(string $userId)
    {
        return $this->repository->findBy(['userId' => $userId]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }

    public function findByVehicleId(string $vehicleId)
    {
        return $this->repository->findBy(['vehicleId' => $vehicleId]);
    }
}
