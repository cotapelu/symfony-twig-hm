<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\AdministrativeProcedure;
use App\Repository\Php\AdministrativeProcedureRepository;
use Doctrine\ORM\EntityManagerInterface;

class AdministrativeProcedureService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdministrativeProcedureRepository $repository
    ) {}

    public function createAdministrativeProcedure(
        string $code,
        string $name,
        ?string $description = null,
        ?array $requirements = null
    ) {
        if (empty(trim($code))) {
            throw new \InvalidArgumentException('Code is required');
        }
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Name is required');
        }

        // Check unique code
        $existing = $this->repository->findOneBy(['code' => $code]);
        if ($existing) {
            throw new \InvalidArgumentException("Procedure with code '$code' already exists");
        }

        $id = bin2hex(random_bytes(16));
        $procedure = new AdministrativeProcedure($id, $code, $name);
        $procedure->setDescription($description);
        $procedure->setRequirements($requirements);

        $this->em->persist($procedure);
        $this->em->flush();

        return $procedure;
    }

    public function getAdministrativeProcedure(string $id)
    {
        $procedure = $this->repository->find($id);
        if (!$procedure) {
            throw new \RuntimeException('AdministrativeProcedure not found');
        }
        return $procedure;
    }

    public function getProcedureByCode(string $code)
    {
        return $this->repository->findOneBy(['code' => $code]);
    }

    public function updateAdministrativeProcedure(string $id, array $data)
    {
        $procedure = $this->getAdministrativeProcedure($id);
        $this->applyProcedureFields($procedure, $id, $data);
        $this->em->flush();
        return $procedure;
    }

    private function applyProcedureFields($procedure, string $id, array $data): void
    {
        $this->applyCode($procedure, $id, $data);
        $this->applyName($procedure, $data);
        $this->applyDescription($procedure, $data);
        $this->applyRequirements($procedure, $data);
        $this->applyStatus($procedure, $data);
    }

    private function applyCode($procedure, string $id, array $data): void
    {
        if (!array_key_exists('code', $data)) {
            return;
        }
        $code = trim($data['code']);
        if ($code === '') {
            throw new \InvalidArgumentException('Code cannot be empty');
        }
        // Check if code already exists on another record
        $existing = $this->repository->findOneBy(['code' => $code]);
        if ($existing && $existing->getId() !== $id) {
            throw new \InvalidArgumentException("Procedure with code '$code' already exists");
        }
        $procedure->setCode($code);
    }

    private function applyName($procedure, array $data): void
    {
        if (!array_key_exists('name', $data)) {
            return;
        }
        $name = trim($data['name']);
        if ($name === '') {
            throw new \InvalidArgumentException('Name cannot be empty');
        }
        $procedure->setName($name);
    }

    private function applyDescription($procedure, array $data): void
    {
        if (array_key_exists('description', $data)) {
            $procedure->setDescription($data['description']);
        }
    }

    private function applyRequirements($procedure, array $data): void
    {
        if (array_key_exists('requirements', $data)) {
            $procedure->setRequirements($data['requirements']);
        }
    }

    private function applyStatus($procedure, array $data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }
        $status = trim($data['status']);
        if ($status === '') {
            throw new \InvalidArgumentException('Status cannot be empty');
        }
        $procedure->setStatus($status);
    }

    public function deleteAdministrativeProcedure(string $id)
    {
        $procedure = $this->getAdministrativeProcedure($id);
        $this->em->remove($procedure);
        $this->em->flush();
    }

    public function findByCode(string $code)
    {
        return $this->repository->findBy(['code' => $code]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }
}
