<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\GovOfficer;
use App\Repository\Php\GovOfficerRepository;
use Doctrine\ORM\EntityManagerInterface;

class GovOfficerService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GovOfficerRepository $repository
    ) {}

    public function createGovOfficer(string $userId, string $department, string $position, ?array $metadata = null)
    {
        if (empty(trim($userId))) throw new \InvalidArgumentException('User ID is required');
        if (empty(trim($department))) throw new \InvalidArgumentException('Department is required');
        if (empty(trim($position))) throw new \InvalidArgumentException('Position is required');

        $id = bin2hex(random_bytes(16));
        $officer = new GovOfficer($id, $userId, $department, $position);
        $officer->setMetadata($metadata);
        $this->em->persist($officer);
        $this->em->flush();
        return $officer;
    }

    public function getGovOfficer(string $id)
    {
        $officer = $this->repository->find($id);
        if (!$officer) throw new \RuntimeException('GovOfficer not found');
        return $officer;
    }

    public function updateGovOfficer(string $id, array $data)
    {
        $officer = $this->getGovOfficer($id);
        isset($data['department']) && $this->applyDepartmentUpdate($officer, $data['department']);
        isset($data['position']) && $this->applyPositionUpdate($officer, $data['position']);
        isset($data['status']) && $this->applyStatusUpdate($officer, $data['status']);
        array_key_exists('metadata', $data) && $officer->setMetadata($data['metadata']);
        $this->em->flush();
        return $officer;
    }

    public function deleteGovOfficer(string $id)
    {
        $officer = $this->getGovOfficer($id);
        $this->em->remove($officer);
        $this->em->flush();
    }

    public function findByUserId(string $userId)
    {
        return $this->repository->findBy(['userId' => $userId]);
    }

    public function findByDepartment(string $department)
    {
        return $this->repository->findBy(['department' => $department]);
    }

    private function applyDepartmentUpdate(GovOfficer $officer, mixed $dept): void
    {
        $dept = trim($dept);
        if ($dept === '') throw new \InvalidArgumentException('Department cannot be empty');
        $officer->setDepartment($dept);
    }

    private function applyPositionUpdate(GovOfficer $officer, mixed $pos): void
    {
        $pos = trim($pos);
        if ($pos === '') throw new \InvalidArgumentException('Position cannot be empty');
        $officer->setPosition($pos);
    }

    private function applyStatusUpdate(GovOfficer $officer, mixed $status): void
    {
        if (empty(trim($status))) throw new \InvalidArgumentException('Status cannot be empty');
        $officer->setStatus($status);
    }
}
