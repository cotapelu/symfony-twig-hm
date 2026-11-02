<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use DateTimeImmutable;
use App\Government\Contracts\DTO\CreateCitizenRequest;
use App\Government\Contracts\DTO\CitizenResponse;
use App\Government\Contracts\DTO\UpdateCitizenRequest;
use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\Repository\CitizenRepositoryInterface;
use App\Government\Domain\ValueObject\CitizenId;
use App\Government\Domain\ValueObject\Address;
use Ramsey\Uuid\Uuid;

class CitizenService
{
    public function __construct(
        private CitizenRepositoryInterface $citizenRepository
    ) {
    }

    public function createCitizen(CreateCitizenRequest $request): CitizenResponse
    {
        $this->ensureIdentityUnique($request->identityNumber);

        $id = new CitizenId(Uuid::uuid4()->toString());
        $address = $this->buildAddress($request);

        $citizen = new Citizen($id, $request->fullName, $request->identityNumber, $address);
        $this->applyOptionalFields($citizen, $request);

        $this->citizenRepository->save($citizen);
        return CitizenResponse::fromEntity($citizen);
    }

    private function ensureIdentityUnique(string $identityNumber): void
    {
        $existing = $this->citizenRepository->findByIdentityNumber($identityNumber);
        if ($existing !== null) {
            throw new \InvalidArgumentException(
                sprintf('Citizen with identity number %s already exists', $identityNumber)
            );
        }
    }

    private function buildAddress(CreateCitizenRequest $request): Address
    {
        return new Address(
            $request->province,
            $request->district,
            $request->ward,
            $request->street,
            $request->apartmentNumber
        );
    }

    private function applyOptionalFields(Citizen $citizen, CreateCitizenRequest $request): void
    {
        $this->applyOptional($request->email, fn($v) => $citizen->setEmail($v));
        $this->applyOptional($request->phone, fn($v) => $citizen->setPhone($v));
        $this->applyOptional($request->dateOfBirth, fn($v) => $citizen->setDateOfBirth(new DateTimeImmutable($v)));
        $this->applyOptional($request->gender, fn($v) => $citizen->setGender($v));
        $this->applyOptional($request->ethnicity, fn($v) => $citizen->setEthnicity($v));
        $this->applyOptional($request->nationality, fn($v) => $citizen->setNationality($v));
        $this->applyOptional($request->occupation, fn($v) => $citizen->setOccupation($v));
        $this->applyOptional($request->educationLevel, fn($v) => $citizen->setEducationLevel($v));
        $this->applyOptional($request->maritalStatus, fn($v) => $citizen->setMaritalStatus($v));
        $this->applyOptional($request->householdId, fn($v) => $citizen->setHouseholdId($v));
    }

    private function applyOptional($value, callable $setter): void
    {
        if ($value !== null && $value !== '') {
            $setter($value);
        }
    }

    public function getCitizenById(string $id): ?CitizenResponse
    {
        $citizen = $this->citizenRepository->findById(new CitizenId($id));
        
        if (!$citizen) {
            return null;
        }

        return CitizenResponse::fromEntity($citizen);
    }

    public function listCitizens(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $citizens = $this->citizenRepository->findAll($filters, $page, $perPage);
        
        return array_map(
            fn(Citizen $citizen) => CitizenResponse::fromEntity($citizen),
            $citizens
        );
    }

    public function searchCitizens(string $query): array
    {
        $filters = [
            'search' => $query
        ];
        
        $citizens = $this->citizenRepository->search($query);
        
        return array_map(
            fn(Citizen $citizen) => CitizenResponse::fromEntity($citizen),
            $citizens
        );
    }

    public function updateCitizen(string $id, UpdateCitizenRequest $request): ?CitizenResponse
    {
        $citizen = $this->citizenRepository->findById(new CitizenId($id));
        if (!$citizen) {
            return null;
        }

        $this->applyUpdateFields($citizen, $request);
        $this->citizenRepository->save($citizen);
        return CitizenResponse::fromEntity($citizen);
    }

    private function applyUpdateFields(Citizen $citizen, UpdateCitizenRequest $request): void
    {
        $this->applyOptional($request->fullName, fn($v) => $citizen->setFullName($v));
        $this->applyOptional($request->email, fn($v) => $citizen->setEmail($v));
        $this->applyOptional($request->phone, fn($v) => $citizen->setPhone($v));
        $this->applyOptional($request->dateOfBirth, fn($v) => $citizen->setDateOfBirth(new DateTimeImmutable($v)));
        $this->applyOptional($request->occupation, fn($v) => $citizen->setOccupation($v));
        $this->applyOptional($request->educationLevel, fn($v) => $citizen->setEducationLevel($v));
        $this->applyOptional($request->maritalStatus, fn($v) => $citizen->setMaritalStatus($v));
        $this->applyOptional($request->householdId, fn($v) => $citizen->setHouseholdId($v));
    }

    public function deactivateCitizen(string $id): bool
    {
        $citizen = $this->citizenRepository->findById(new CitizenId($id));
        
        if (!$citizen) {
            return false;
        }

        $citizen->deactivate();
        $this->citizenRepository->save($citizen);
        
        return true;
    }

    public function activateCitizen(string $id): bool
    {
        $citizen = $this->citizenRepository->findById(new CitizenId($id));
        
        if (!$citizen) {
            return false;
        }

        $citizen->activate();
        $this->citizenRepository->save($citizen);
        
        return true;
    }

    public function getCitizensByHousehold(string $householdId): array
    {
        $citizens = $this->citizenRepository->findByHousehold($householdId);
        
        return array_map(
            fn(Citizen $citizen) => CitizenResponse::fromEntity($citizen),
            $citizens
        );
    }

    public function getStatistics(array $filters = []): array
    {
        $total = $this->citizenRepository->count($filters);
        $active = $this->citizenRepository->count(['isActive' => true]);
        $deceased = $this->citizenRepository->count(['isDeceased' => true]);

        return [
            'total' => $total,
            'active' => $active,
            'deceased' => $deceased,
            'inactive' => $total - $active,
        ];
    }
}
