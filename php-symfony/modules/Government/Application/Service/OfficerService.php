<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use DateTimeImmutable;
use App\Government\Contracts\DTO\CreateOfficerRequest;
use App\Government\Contracts\DTO\OfficerResponse;
use App\Government\Domain\Entity\Officer;
use App\Government\Domain\Repository\OfficerRepositoryInterface;
use App\Government\Domain\Repository\WardRepositoryInterface;
use App\Government\Domain\ValueObject\Department;
use App\Government\Domain\ValueObject\OfficerId;
use App\Government\Domain\ValueObject\Position;
use Ramsey\Uuid\Uuid;

class OfficerService
{
    public function __construct(
        private OfficerRepositoryInterface $officerRepository,
        private WardRepositoryInterface $wardRepository
    ) {
    }

    public function createOfficer(CreateOfficerRequest $request): OfficerResponse
    {
        $id = new OfficerId(Uuid::uuid4()->toString());
        
        $position = new Position(
            $request->positionCode,
            $request->positionName,
            $request->positionLevel
        );
        
        $department = new Department(
            $request->departmentCode,
            $request->departmentName,
            $request->parentDepartmentCode
        );

        $officer = new Officer($id, $request->fullName, $position, $department);

        if ($request->email) {
            $officer->setEmail($request->email);
        }
        
        if ($request->phone) {
            $officer->setPhone($request->phone);
        }
        
        if ($request->dateOfBirth) {
            $officer->setDateOfBirth(new DateTimeImmutable($request->dateOfBirth));
        }
        
        if ($request->identityNumber) {
            $officer->setIdentityNumber($request->identityNumber);
        }

        $this->officerRepository->save($officer);

        return OfficerResponse::fromEntity($officer);
    }

    public function getOfficerById(string $id): ?OfficerResponse
    {
        $officer = $this->officerRepository->findById(new OfficerId($id));
        
        if (!$officer) {
            return null;
        }

        return OfficerResponse::fromEntity($officer);
    }

    public function listOfficers(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $officers = $this->officerRepository->findAll($filters, $page, $perPage);
        
        return array_map(
            fn(Officer $officer) => OfficerResponse::fromEntity($officer),
            $officers
        );
    }

    public function searchOfficers(string $query): array
    {
        $filters = [
            'search' => $query
        ];
        
        $officers = $this->officerRepository->findAll($filters, 1, 50);
        
        return array_map(
            fn(Officer $officer) => OfficerResponse::fromEntity($officer),
            $officers
        );
    }

    public function deactivateOfficer(string $id): void
    {
        $officer = $this->officerRepository->findById(new OfficerId($id));
        
        if ($officer) {
            $officer->deactivate();
            $this->officerRepository->save($officer);
        }
    }

    public function activateOfficer(string $id): void
    {
        $officer = $this->officerRepository->findById(new OfficerId($id));
        
        if ($officer) {
            $officer->activate();
            $this->officerRepository->save($officer);
        }
    }

    public function assignOfficerToWard(string $officerId, string $wardId): void
    {
        $officer = $this->officerRepository->findById(new OfficerId($officerId));
        if (!$officer) {
            throw new \InvalidArgumentException('Officer not found');
        }

        $ward = $this->wardRepository->findById($wardId);
        if (!$ward) {
            throw new \InvalidArgumentException('Ward not found');
        }

        $assignedWardsCount = $this->wardRepository->countByAssignedOfficer($officerId);
        if ($assignedWardsCount >= 3) {
            throw new \InvalidArgumentException(
                sprintf('Officer cannot be assigned to more than 3 wards (currently assigned to %d wards)', $assignedWardsCount)
            );
        }

        $ward->setAssignedOfficerId($officerId);
        $this->wardRepository->save($ward);
    }

    public function removeOfficerFromWard(string $officerId, string $wardId): void
    {
        $ward = $this->wardRepository->findById($wardId);
        if (!$ward) {
            throw new \InvalidArgumentException('Ward not found');
        }

        if ($ward->getAssignedOfficerId() !== $officerId) {
            throw new \InvalidArgumentException('Officer is not assigned to this ward');
        }

        $ward->setAssignedOfficerId(null);
        $this->wardRepository->save($ward);
    }

    public function getOfficerWards(string $officerId): array
    {
        return $this->wardRepository->findByAssignedOfficer($officerId);
    }
}
