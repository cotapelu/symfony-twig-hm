<?php

declare(strict_types=1);

namespace App\Citizen\Application\Service;

use App\Citizen\Application\DTO\CreateCitizenRequest;
use App\Citizen\Application\DTO\CitizenResponse;
use App\Citizen\Application\Repository\CitizenRepositoryInterface;
use App\Citizen\Domain\Entity\Citizen;
use App\Citizen\Domain\Entity\Household;
use App\Citizen\Domain\Entity\ResidentHistory;

class CitizenService
{
    public function __construct(
        private readonly CitizenRepositoryInterface $citizenRepository
    ) {
    }

    public function createCitizen(CreateCitizenRequest $request): CitizenResponse
    {
        $existingCitizen = $this->citizenRepository->findByIdentityNumber($request->identityNumber);
        if ($existingCitizen !== null) {
            throw new \InvalidArgumentException('Citizen with this identity number already exists');
        }

        $citizen = new Citizen(
            $request->identityNumber,
            $request->fullName,
            $request->dateOfBirth,
            $request->gender
        );

        $this->populateCitizen($citizen, $request);
        $this->citizenRepository->save($citizen);

        return CitizenResponse::fromEntity($citizen);
    }

    private function populateCitizen(Citizen $citizen, CreateCitizenRequest $request): void
    {
        $this->setContactInfo($citizen, $request);
        $this->setAddressInfo($citizen, $request);
        $this->setDemographicInfo($citizen, $request);
        $this->setMetadata($citizen, $request);
    }

    private function setContactInfo(Citizen $citizen, CreateCitizenRequest $request): void
    {
        $citizen->setPhoneNumber($request->phoneNumber);
        $citizen->setEmail($request->email);
    }

    private function setAddressInfo(Citizen $citizen, CreateCitizenRequest $request): void
    {
        $citizen->setPermanentAddress($request->permanentAddress);
        $citizen->setCurrentAddress($request->currentAddress);
    }

    private function setDemographicInfo(Citizen $citizen, CreateCitizenRequest $request): void
    {
        $citizen->setEthnicity($request->ethnicity);
        $citizen->setReligion($request->religion);
        $citizen->setEducationLevel($request->educationLevel);
        $citizen->setOccupation($request->occupation);
        $citizen->setMaritalStatus($request->maritalStatus);
    }

    private function setMetadata(Citizen $citizen, CreateCitizenRequest $request): void
    {
        $citizen->setIsHouseholdHead($request->isHouseholdHead);
        $citizen->setSpecialCategories($request->specialCategories);
        $citizen->setNotes($request->notes);
        $citizen->setUserId($request->userId);
    }

    public function getCitizen(string $id, string $userId): ?CitizenResponse
    {
        $citizen = $this->citizenRepository->findById($id);
        
        if ($citizen === null) {
            return null;
        }
        
        if ($citizen->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Access denied');
        }

        return CitizenResponse::fromEntity($citizen);
    }

    public function searchCitizens(string $query, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $citizens = $this->citizenRepository->search($query, $filters);
        
        return array_map(
            fn(Citizen $citizen) => CitizenResponse::fromEntity($citizen),
            array_slice($citizens, $offset, $limit)
        );
    }

    public function getCitizensByHousehold(string $householdId, int $limit = 50, int $offset = 0): array
    {
        $citizens = $this->citizenRepository->findByHouseholdId($householdId, $limit, $offset);
        
        return array_map(
            fn(Citizen $citizen) => CitizenResponse::fromEntity($citizen),
            $citizens
        );
    }

    public function getElderlyCitizens(int $minAge = 60, int $limit = 50, int $offset = 0): array
    {
        $citizens = $this->citizenRepository->findElderly($minAge, $limit, $offset);
        
        return array_map(
            fn(Citizen $citizen) => CitizenResponse::fromEntity($citizen),
            $citizens
        );
    }

    public function getChildCitizens(int $maxAge = 16, int $limit = 50, int $offset = 0): array
    {
        $citizens = $this->citizenRepository->findChildren($maxAge, $limit, $offset);
        
        return array_map(
            fn(Citizen $citizen) => CitizenResponse::fromEntity($citizen),
            $citizens
        );
    }

    public function getCitizensBySpecialCategories(array $categories): array
    {
        $citizens = $this->citizenRepository->findBySpecialCategories($categories);
        
        return array_map(
            fn(Citizen $citizen) => CitizenResponse::fromEntity($citizen),
            $citizens
        );
    }

    public function getAllCitizens(int $limit = 50, int $offset = 0): array
    {
        $citizens = $this->citizenRepository->findAll($limit, $offset);
        
        return array_map(
            fn(Citizen $citizen) => CitizenResponse::fromEntity($citizen),
            $citizens
        );
    }

    public function updateCitizen(string $id, array $data, string $userId): ?CitizenResponse
    {
        $citizen = $this->findCitizenOrNull($id);
        if (!$citizen) {
            return null;
        }
        $this->ensureCitizenOwnership($citizen, $userId);
        $this->applyCitizenUpdates($citizen, $data);
        $this->citizenRepository->save($citizen);
        return CitizenResponse::fromEntity($citizen);
    }

    private function findCitizenOrNull(string $id): ?Citizen
    {
        return $this->citizenRepository->findById($id);
    }

    private function ensureCitizenOwnership(Citizen $citizen, string $userId): void
    {
        if ($citizen->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Access denied');
        }
    }

    private function applyCitizenUpdates(Citizen $citizen, array $data): void
    {
        $this->updateBasicInfo($citizen, $data);
        $this->updateAddressInfo($citizen, $data);
        $this->updateDemographicInfo($citizen, $data);
        $this->updateAdditionalInfo($citizen, $data);
    }

    private function updateBasicInfo(Citizen $citizen, array $data): void
    {
        if (isset($data['fullName'])) {
            $citizen->setFullName($data['fullName']);
        }
        if (isset($data['dateOfBirth'])) {
            $citizen->setDateOfBirth(new \DateTime($data['dateOfBirth']));
        }
        if (isset($data['gender'])) {
            $citizen->setGender($data['gender']);
        }
        if (isset($data['phoneNumber'])) {
            $citizen->setPhoneNumber($data['phoneNumber']);
        }
        if (isset($data['email'])) {
            $citizen->setEmail($data['email']);
        }
    }

    private function updateAddressInfo(Citizen $citizen, array $data): void
    {
        if (isset($data['permanentAddress'])) {
            $citizen->setPermanentAddress($data['permanentAddress']);
        }
        if (isset($data['currentAddress'])) {
            $citizen->setCurrentAddress($data['currentAddress']);
        }
    }

    private function updateDemographicInfo(Citizen $citizen, array $data): void
    {
        if (isset($data['ethnicity'])) {
            $citizen->setEthnicity($data['ethnicity']);
        }
        if (isset($data['religion'])) {
            $citizen->setReligion($data['religion']);
        }
        if (isset($data['educationLevel'])) {
            $citizen->setEducationLevel($data['educationLevel']);
        }
        if (isset($data['occupation'])) {
            $citizen->setOccupation($data['occupation']);
        }
        if (isset($data['maritalStatus'])) {
            $citizen->setMaritalStatus($data['maritalStatus']);
        }
    }

    private function updateAdditionalInfo(Citizen $citizen, array $data): void
    {
        if (isset($data['specialCategories'])) {
            $citizen->setSpecialCategories($data['specialCategories']);
        }
        if (isset($data['notes'])) {
            $citizen->setNotes($data['notes']);
        }
    }

    public function deleteCitizen(string $id, string $userId): bool
    {
        $citizen = $this->citizenRepository->findById($id);
        
        if ($citizen === null) {
            return false;
        }
        
        if ($citizen->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Access denied');
        }

        $this->citizenRepository->delete($citizen);
        return true;
    }

    public function getCitizenStatistics(): array
    {
        return $this->citizenRepository->getStatistics();
    }

    public function addResidentHistory(string $citizenId, ResidentHistory $history, string $userId): void
    {
        $citizen = $this->citizenRepository->findById($citizenId);
        
        if ($citizen === null) {
            throw new \InvalidArgumentException('Citizen not found');
        }
        
        if ($citizen->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Access denied');
        }

        $citizen->addResidentHistory($history);
        $this->citizenRepository->save($citizen);
    }
}