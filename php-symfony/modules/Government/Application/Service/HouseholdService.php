<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use App\Government\Contracts\DTO\CreateHouseholdRequest;
use App\Government\Contracts\DTO\UpdateHouseholdRequest;
use App\Government\Domain\Entity\Household;
use App\Government\Domain\Repository\HouseholdRepositoryInterface;
use App\Government\Domain\Repository\CitizenRepositoryInterface;
use App\Government\Domain\ValueObject\HouseholdId;
use App\Government\Domain\ValueObject\Address;

class HouseholdService
{
    public function __construct(
        private HouseholdRepositoryInterface $householdRepository,
        private CitizenRepositoryInterface $citizenRepository
    ) {
    }

    public function createHousehold(CreateHouseholdRequest $request): Household
    {
        // Validate head of household exists
        $headOfHousehold = $this->citizenRepository->findById($request->headOfHouseholdId);
        if (!$headOfHousehold) {
            throw new \InvalidArgumentException('Head of household not found');
        }

        $household = new Household(
            HouseholdId::generate(),
            $request->householdNumber,
            new Address(
                $request->province,
                $request->district,
                $request->ward,
                $request->street,
                $request->apartmentNumber
            ),
            $request->headOfHouseholdId
        );

        $this->householdRepository->save($household);

        return $household;
    }

    public function getHouseholdById(string $id): ?Household
    {
        return $this->householdRepository->findById($id);
    }

    public function getHouseholdByNumber(string $number): ?Household
    {
        return $this->householdRepository->findByHouseholdNumber($number);
    }

    public function listHouseholds(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        return $this->householdRepository->findByFilters($filters, $page, $perPage);
    }

    public function updateHousehold(string $id, UpdateHouseholdRequest $request): ?Household
    {
        $household = $this->householdRepository->findById($id);
        if (!$household) {
            return null;
        }

        if ($request->householdNumber !== null) {
            $household->setHouseholdNumber($request->householdNumber);
        }

        if ($request->headOfHouseholdId !== null) {
            $headOfHousehold = $this->validateHeadOfHousehold($request->headOfHouseholdId);
            $household->setHeadOfHouseholdId($request->headOfHouseholdId);
        }

        $newAddress = $this->buildUpdatedAddress($household->getAddress(), $request);
        if ($newAddress !== null) {
            $household->setAddress($newAddress);
        }

        $this->householdRepository->save($household);
        return $household;
    }

    private function validateHeadOfHousehold(string $citizenId): void
    {
        $head = $this->citizenRepository->findById($citizenId);
        if (!$head) {
            throw new \InvalidArgumentException('Head of household not found');
        }
    }

    private function buildUpdatedAddress(?Address $current, UpdateHouseholdRequest $request): ?Address
    {
        $province = $request->province ?? $current?->getProvince();
        $district = $request->district ?? $current?->getDistrict();
        $ward = $request->ward ?? $current?->getWard();
        $street = $request->street ?? $current?->getStreet();
        $apartment = $request->apartmentNumber ?? $current?->getApartmentNumber();

        // If any request field is provided, we will combine them to build an Address
        if ($request->province !== null || $request->district !== null || $request->ward !== null || $request->street !== null) {
            return new Address($province, $district, $ward, $street, $apartment);
        }

        return null;
    }

    public function dissolveHousehold(string $id, string $reason): bool
    {
        $household = $this->householdRepository->findById($id);
        if (!$household) {
            return false;
        }

        $household->dissolve($reason);
        $this->householdRepository->save($household);

        return true;
    }

    public function activateHousehold(string $id): bool
    {
        $household = $this->householdRepository->findById($id);
        if (!$household) {
            return false;
        }

        // Re-activate the household
        $household->setDissolvedAt(null, null);
        $this->householdRepository->save($household);

        return true;
    }

    public function getHouseholdMembers(string $householdId): array
    {
        return $this->citizenRepository->findByHousehold($householdId);
    }

    public function getStatistics(array $filters = []): array
    {
        $total = $this->householdRepository->count($filters);
        $active = $this->householdRepository->count(array_merge($filters, ['isActive' => true]));
        $dissolved = $this->householdRepository->count(array_merge($filters, ['isActive' => false]));

        return [
            'total' => $total,
            'active' => $active,
            'dissolved' => $dissolved,
        ];
    }

    public function searchHouseholds(string $query): array
    {
        return $this->householdRepository->search($query);
    }
}
