<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\Citizen;
use App\Repository\Php\CitizenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Citizen Service with security checks
 *
 * SECURITY: Only officers and admins can modify citizen data
 */
class CitizenService
{
    /** @var string[] Roles allowed to manage citizen records */
    private const OFFICER_ROLES = ['ROLE_OFFICER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

    private ?array $currentUserRoles = null;

    /** Set current user roles for permission checking */
    public function setCurrentUserRoles(array $roles)
    {
        $this->currentUserRoles = $roles;
        return $this;
    }

    private function assertOfficerAccess()
    {
        if ($this->currentUserRoles === null) {
            throw new AccessDeniedHttpException('User context not set');
        }
        foreach (self::OFFICER_ROLES as $role) {
            if (in_array($role, $this->currentUserRoles, true)) {
                return; // granted
            }
        }
        throw new AccessDeniedHttpException('Access denied: Officer or admin role required');
    }
    public function __construct(
        private EntityManagerInterface $em,
        private CitizenRepository $repository
    ) {}

    public function createCitizen(
        string $name,
        string $identityNumber,
        ?string $householdId = null,
        ?array $metadata = null
    ) {
        $this->assertOfficerAccess();
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Citizen name is required');
        }
        if (empty(trim($identityNumber))) {
            throw new \InvalidArgumentException('Identity number is required');
        }

        // Check duplicate identity number
        $existing = $this->repository->findByIdentityNumber($identityNumber);
        if ($existing) {
            throw new \InvalidArgumentException("Citizen with identity number '$identityNumber' already exists");
        }

        $id = bin2hex(random_bytes(16));
        $citizen = new Citizen($id, $name, $identityNumber);
        $citizen->setHouseholdId($householdId);
        $citizen->setMetadata($metadata);

        $this->em->persist($citizen);
        $this->em->flush();

        return $citizen;
    }

    public function getCitizen(string $id)
    {
        $citizen = $this->repository->find($id);
        if (!$citizen) {
            throw new \RuntimeException('Citizen not found');
        }
        return $citizen;
    }

    public function getCitizenByIdentityNumber(string $identityNumber)
    {
        return $this->repository->findByIdentityNumber($identityNumber);
    }

    public function updateCitizen(string $id, array $data)
    {
        $this->assertOfficerAccess();
        $citizen = $this->getCitizen($id);
        $this->applyCitizenFields($citizen, $id, $data);
        $this->em->flush();
        return $citizen;
    }

    private function applyCitizenFields($citizen, string $id, array $data): void
    {
        $this->applyName($citizen, $data);
        $this->applyIdentityNumber($citizen, $id, $data);
        $this->applyHouseholdId($citizen, $data);
        $this->applyStatus($citizen, $data);
        $this->applyMetadata($citizen, $data);
    }

    private function applyName($citizen, array $data): void
    {
        if (!array_key_exists('name', $data)) {
            return;
        }
        $name = trim($data['name']);
        if ($name === '') {
            throw new \InvalidArgumentException('Citizen name cannot be empty');
        }
        $citizen->setName($name);
    }

    private function applyIdentityNumber($citizen, string $id, array $data): void
    {
        if (!array_key_exists('identityNumber', $data)) {
            return;
        }
        $identityNumber = trim($data['identityNumber']);
        if ($identityNumber === '') {
            throw new \InvalidArgumentException('Identity number cannot be empty');
        }
        // Check uniqueness
        $existing = $this->repository->findByIdentityNumber($identityNumber);
        if ($existing && $existing->getId() !== $id) {
            throw new \InvalidArgumentException("Citizen with identity number '$identityNumber' already exists");
        }
        $citizen->setIdentityNumber($identityNumber);
    }

    private function applyHouseholdId($citizen, array $data): void
    {
        if (array_key_exists('householdId', $data)) {
            $citizen->setHouseholdId($data['householdId']);
        }
    }

    private function applyStatus($citizen, array $data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }
        $status = trim($data['status']);
        if ($status === '') {
            throw new \InvalidArgumentException('Status cannot be empty');
        }
        $citizen->setStatus($status);
    }

    private function applyMetadata($citizen, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $citizen->setMetadata($data['metadata']);
        }
    }

    public function deleteCitizen(string $id)
    {
        $this->assertOfficerAccess();
        $citizen = $this->getCitizen($id);
        $this->em->remove($citizen);
        $this->em->flush();
    }

    public function findByHouseholdId(string $householdId)
    {
        return $this->repository->findByHouseholdId($householdId);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }

    public function searchByName(string $query, int $limit = 20)
    {
        return $this->repository->searchByName($query, $limit);
    }
}
