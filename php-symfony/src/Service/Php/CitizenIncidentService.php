<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\CitizenIncident;
use App\Repository\Php\CitizenIncidentRepository;
use Doctrine\ORM\EntityManagerInterface;

class CitizenIncidentService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CitizenIncidentRepository $repository
    ) {}

    public function createIncident(
        string $citizenId,
        string $type,
        ?array $data = null,
        ?string $status = 'PENDING'
    ) {
        if (empty(trim($citizenId))) throw new \InvalidArgumentException('Citizen ID is required');
        if (empty(trim($type))) throw new \InvalidArgumentException('Incident type is required');
        if (empty(trim($status))) throw new \InvalidArgumentException('Status cannot be empty');

        $id = bin2hex(random_bytes(16));
        $incident = new CitizenIncident($id, $citizenId, $type);
        $incident->setData($data);
        $incident->setStatus($status);
        $this->em->persist($incident);
        $this->em->flush();
        return $incident;
    }

    public function getIncident(string $id)
    {
        $incident = $this->repository->find($id);
        if (!$incident) throw new \RuntimeException('CitizenIncident not found');
        return $incident;
    }

    public function updateIncident(string $id, array $data)
    {
        $incident = $this->getIncident($id);
        isset($data['citizenId']) && $this->applyCitizenIdUpdate($incident, $data['citizenId']);
        isset($data['type']) && $this->applyTypeUpdate($incident, $data['type']);
        isset($data['status']) && $this->applyStatusUpdate($incident, $data['status']);
        array_key_exists('data', $data) && $incident->setData($data['data']);
        $this->em->flush();
        return $incident;
    }

    public function deleteIncident(string $id)
    {
        $incident = $this->getIncident($id);
        $this->em->remove($incident);
        $this->em->flush();
    }

    public function findByCitizenId(string $citizenId)
    {
        return $this->repository->findBy(['citizenId' => $citizenId]);
    }

    public function findByType(string $type)
    {
        return $this->repository->findBy(['type' => $type]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }

    private function applyCitizenIdUpdate(CitizenIncident $incident, mixed $citizenId): void
    {
        $citizenId = trim($citizenId);
        if ($citizenId === '') throw new \InvalidArgumentException('Citizen ID cannot be empty');
        $incident->setCitizenId($citizenId);
    }

    private function applyTypeUpdate(CitizenIncident $incident, mixed $type): void
    {
        $type = trim($type);
        if ($type === '') throw new \InvalidArgumentException('Incident type cannot be empty');
        $incident->setType($type);
    }

    private function applyStatusUpdate(CitizenIncident $incident, mixed $status): void
    {
        $status = trim($status);
        if ($status === '') throw new \InvalidArgumentException('Status cannot be empty');
        $incident->setStatus($status);
    }
}
