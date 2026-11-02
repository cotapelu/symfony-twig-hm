<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\ProcedureApplication;
use App\Repository\Php\ProcedureApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProcedureApplicationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProcedureApplicationRepository $repository
    ) {}

    public function createApplication(
        string $citizenId,
        string $procedureId,
        ?array $data = null,
        ?string $status = 'PENDING'
    ) {
        if (empty(trim($citizenId))) throw new \InvalidArgumentException('Citizen ID is required');
        if (empty(trim($procedureId))) throw new \InvalidArgumentException('Procedure ID is required');
        if (empty(trim($status))) throw new \InvalidArgumentException('Status cannot be empty');

        $id = bin2hex(random_bytes(16));
        $application = new ProcedureApplication($id, $citizenId, $procedureId);
        $application->setData($data);
        $application->setStatus($status);
        $this->em->persist($application);
        $this->em->flush();
        return $application;
    }

    public function getApplication(string $id)
    {
        $application = $this->repository->find($id);
        if (!$application) throw new \RuntimeException('ProcedureApplication not found');
        return $application;
    }

    public function updateApplication(string $id, array $data)
    {
        $application = $this->getApplication($id);
        isset($data['citizenId']) && $this->applyCitizenIdUpdate($application, $data['citizenId']);
        isset($data['procedureId']) && $this->applyProcedureIdUpdate($application, $data['procedureId']);
        isset($data['status']) && $this->applyStatusUpdate($application, $data['status']);
        array_key_exists('data', $data) && $application->setData($data['data']);
        $this->em->flush();
        return $application;
    }

    public function deleteApplication(string $id)
    {
        $application = $this->getApplication($id);
        $this->em->remove($application);
        $this->em->flush();
    }

    public function findByCitizenId(string $citizenId)
    {
        return $this->repository->findBy(['citizenId' => $citizenId]);
    }

    public function findByProcedureId(string $procedureId)
    {
        return $this->repository->findBy(['procedureId' => $procedureId]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }

    private function applyCitizenIdUpdate(ProcedureApplication $application, mixed $citizenId): void
    {
        $citizenId = trim($citizenId);
        if ($citizenId === '') throw new \InvalidArgumentException('Citizen ID cannot be empty');
        $application->setCitizenId($citizenId);
    }

    private function applyProcedureIdUpdate(ProcedureApplication $application, mixed $procedureId): void
    {
        $procedureId = trim($procedureId);
        if ($procedureId === '') throw new \InvalidArgumentException('Procedure ID cannot be empty');
        $application->setProcedureId($procedureId);
    }

    private function applyStatusUpdate(ProcedureApplication $application, mixed $status): void
    {
        $status = trim($status);
        if ($status === '') throw new \InvalidArgumentException('Status cannot be empty');
        $application->setStatus($status);
    }
}
