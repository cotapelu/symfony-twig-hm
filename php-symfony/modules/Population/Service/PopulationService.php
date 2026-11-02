<?php

declare(strict_types=1);

namespace App\Population\Service;

use App\Population\Entity\PopulationRecord;
use App\Population\Repository\PopulationRecordRepository;
use DateTime;
use Exception;
use InvalidArgumentException;

class PopulationService
{
    public function __construct(
        private PopulationRecordRepository $populationRecordRepository
    ) {}

    public function getPopulationList(int $page = 1, int $limit = 20, string $filter = 'all', string $search = '')
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than 0');
        }
        if ($limit < 1) {
            throw new InvalidArgumentException('Limit must be greater than 0');
        }
        if ($limit > 100) {
            throw new InvalidArgumentException('Limit cannot exceed 100');
        }

        $filters = [];
        if ($filter !== 'all') {
            $filters['type'] = $filter;
        }

        return $this->populationRecordRepository->findPaginated($page, $limit, $filters, $search);
    }

    public function getStatistics()
    {
        $records = $this->populationRecordRepository->findAll();
        $total = count($records);
        if ($total === 0) {
            return ['total'=>0,'male'=>0,'female'=>0,'urban'=>0,'rural'=>0,'growth'=>0.0];
        }
        [$male,$female] = $this->countByGender($records);
        [$urban,$rural] = $this->countByLocationType($records);
        return ['total'=>$total,'male'=>$male,'female'=>$female,'urban'=>$urban,'rural'=>$rural,'growth'=>0.0];
    }

    public function getAgeDistribution(): array
    {
        $records = $this->populationRecordRepository->findAll();
        $distribution = $this->initializeAgeDistribution();
        $total = count($records);
        if ($total === 0) {
            return $distribution;
        }
        foreach ($records as $record) {
            $idx = $this->getAgeGroup($record->getBirthDate());
            $distribution[$idx]['count']++;
        }
        return $this->finalizeDistribution($distribution, $total);
    }

    public function getById(string $id)
    {
        $record = $this->populationRecordRepository->find($id);
        return $record ? $record->toArray() : null;
    }

    public function create(array $data, int $userId)
    {
        $this->validateName($data);
        $birthDate = $this->validateBirthDate($data);
        $this->validateGender($data);
        $this->validateAddress($data);
        $this->validateHouseholdId($data);
        $this->validateStatus($data);
        $this->validateType($data);
        $this->validateCitizenId($data);
        $record = $this->buildPopulationRecord($data, $birthDate);
        $this->populationRecordRepository->save($record);
        return $record->toArray();
    }

    private function validateName(array $data): void
    {
        if (empty($data['name']) || trim($data['name']) === '') {
            throw new InvalidArgumentException('Name is required');
        }
        if (strlen(trim($data['name'])) > 255) {
            throw new InvalidArgumentException('Name is too long (max 255 characters)');
        }
    }

    private function validateBirthDate(array $data): DateTime
    {
        if (empty($data['birthDate'])) {
            throw new InvalidArgumentException('Birth date is required');
        }
        try {
            $birthDate = new DateTime($data['birthDate']);
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid birth date format');
        }
        if ($birthDate > new DateTime()) {
            throw new InvalidArgumentException('Birth date cannot be in the future');
        }
        return $birthDate;
    }

    private function validateGender(array $data): void
    {
        if (empty($data['gender']) || !in_array($data['gender'], ['Nam', 'Nữ'], true)) {
            throw new InvalidArgumentException('Gender must be either "Nam" or "Nữ"');
        }
    }

    private function validateAddress(array $data): void
    {
        if (empty($data['address']) || trim($data['address']) === '') {
            throw new InvalidArgumentException('Address is required');
        }
        if (strlen(trim($data['address'])) > 255) {
            throw new InvalidArgumentException('Address is too long (max 255 characters)');
        }
    }

    private function validateHouseholdId(array $data): void
    {
        if (empty($data['householdId']) || trim($data['householdId']) === '') {
            throw new InvalidArgumentException('Household ID is required');
        }
        if (strlen(trim($data['householdId'])) > 50) {
            throw new InvalidArgumentException('Household ID is too long (max 50 characters)');
        }
    }

    private function validateStatus(array $data): void
    {
        if (empty($data['status']) || !in_array($data['status'], ['Thường trú', 'Tạm trú'], true)) {
            throw new InvalidArgumentException('Status must be either "Thường trú" or "Tạm trú"');
        }
    }

    private function validateType(array $data): void
    {
        if (empty($data['type']) || !in_array($data['type'], ['Công dân', 'Trẻ em', 'Người cao tuổi'], true)) {
            throw new InvalidArgumentException('Type must be either "Công dân", "Trẻ em", or "Người cao tuổi"');
        }
    }

    private function validateCitizenId(array $data): void
    {
        if (!empty($data['citizenId'])) {
            if (strlen(trim($data['citizenId'])) > 20) {
                throw new InvalidArgumentException('Citizen ID is too long (max 20 characters)');
            }
            $existing = $this->populationRecordRepository->findOneBy(['citizenId' => trim($data['citizenId'])]);
            if ($existing) {
                throw new InvalidArgumentException('Citizen ID already exists');
            }
        }
    }

    private function buildPopulationRecord(array $data, DateTime $birthDate): PopulationRecord
    {
        $record = new PopulationRecord();
        $record->setName(trim($data['name']));
        $record->setBirthDate($birthDate);
        $record->setGender($data['gender']);
        $record->setAddress(trim($data['address']));
        $record->setHouseholdId(trim($data['householdId']));
        $record->setStatus($data['status']);
        $record->setType($data['type']);
        if (!empty($data['citizenId'])) {
            $record->setCitizenId(trim($data['citizenId']));
        }
        $record->setCreatedAt(new DateTime());
        return $record;
    }

    public function update(string $id, array $data, int $userId)
    {
        $record = $this->populationRecordRepository->find($id);
        if (!$record) {
            return null;
        }

        $this->applyPopulationRecordFields($record, $data);
        $record->setUpdatedAt(new DateTime());
        $this->populationRecordRepository->save($record);
        return $record->toArray();
    }

    private function applyPopulationRecordFields($record, array $data): void
    {
        $this->applyName($record, $data);
        $this->applyBirthDate($record, $data);
        $this->applyGender($record, $data);
        $this->applyAddress($record, $data);
        $this->applyHouseholdId($record, $data);
        $this->applyStatus($record, $data);
        $this->applyType($record, $data);
        $this->applyCitizenId($record, $data);
    }

    private function applyName($record, array $data): void
    {
        if (!array_key_exists('name', $data)) {
            return;
        }
        $name = trim($data['name']);
        if ($name === '') {
            throw new InvalidArgumentException('Name cannot be empty');
        }
        if (strlen($name) > 255) {
            throw new InvalidArgumentException('Name is too long (max 255 characters)');
        }
        $record->setName($name);
    }

    private function applyBirthDate($record, array $data): void
    {
        if (!array_key_exists('birthDate', $data)) {
            return;
        }
        if (!empty($data['birthDate'])) {
            try {
                $birthDate = new DateTime($data['birthDate']);
                if ($birthDate > new DateTime()) {
                    throw new InvalidArgumentException('Birth date cannot be in the future');
                }
                $record->setBirthDate($birthDate);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Invalid birth date format');
            }
        }
    }

    private function applyGender($record, array $data): void
    {
        if (!array_key_exists('gender', $data)) {
            return;
        }
        if (!in_array($data['gender'], ['Nam', 'Nữ'], true)) {
            throw new InvalidArgumentException('Gender must be either "Nam" or "Nữ"');
        }
        $record->setGender($data['gender']);
    }

    private function applyAddress($record, array $data): void
    {
        if (!array_key_exists('address', $data)) {
            return;
        }
        $address = trim($data['address']);
        if ($address === '') {
            throw new InvalidArgumentException('Address cannot be empty');
        }
        if (strlen($address) > 255) {
            throw new InvalidArgumentException('Address is too long (max 255 characters)');
        }
        $record->setAddress($address);
    }

    private function applyHouseholdId($record, array $data): void
    {
        if (!array_key_exists('householdId', $data)) {
            return;
        }
        $householdId = trim($data['householdId']);
        if ($householdId === '') {
            throw new InvalidArgumentException('Household ID cannot be empty');
        }
        if (strlen($householdId) > 50) {
            throw new InvalidArgumentException('Household ID is too long (max 50 characters)');
        }
        $record->setHouseholdId($householdId);
    }

    private function applyStatus($record, array $data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }
        if (!in_array($data['status'], ['Thường trú', 'Tạm trú'], true)) {
            throw new InvalidArgumentException('Status must be either "Thường trú" or "Tạm trú"');
        }
        $record->setStatus($data['status']);
    }

    private function applyType($record, array $data): void
    {
        if (!array_key_exists('type', $data)) {
            return;
        }
        if (!in_array($data['type'], ['Công dân', 'Trẻ em', 'Người cao tuổi'], true)) {
            throw new InvalidArgumentException('Type must be either "Công dân", "Trẻ em", or "Người cao tuổi"');
        }
        $record->setType($data['type']);
    }

    private function applyCitizenId($record, array $data): void
    {
        if (!array_key_exists('citizenId', $data)) {
            return;
        }
        $citizenIdInput = $data['citizenId'];
        if (trim($citizenIdInput ?? '') === '') {
            $record->setCitizenId('');
            return;
        }
        $citizenId = trim($citizenIdInput);
        if (strlen($citizenId) > 20) {
            throw new InvalidArgumentException('Citizen ID is too long (max 20 characters)');
        }
        $existing = $this->populationRecordRepository->findOneBy(['citizenId' => $citizenId]);
        if ($existing && $existing->getId() !== $record->getId()) {
            throw new InvalidArgumentException('Citizen ID already exists');
        }
        $record->setCitizenId($citizenId);
    }

    public function delete(string $id, int $userId)
    {
        $record = $this->populationRecordRepository->find($id);
        if (!$record) {
            return false;
        }

        $this->populationRecordRepository->remove($record);
        return true;
    }

    public function getByHousehold(string $householdId)
    {
        if (empty($householdId) || trim($householdId) === '') {
            return [];
        }
        $records = $this->populationRecordRepository->findBy(['householdId' => $householdId]);
        return array_map(fn(PopulationRecord $r) => $r->toArray(), $records);
    }

    public function search(string $query)
    {
        if (empty($query) || trim($query) === '') {
            return [];
        }
        return $this->populationRecordRepository->search(trim($query));
    }

    private function countByGender(array $records): array
    {
        $male = $female = 0;
        foreach ($records as $record) {
            if ($record->getGender() === 'Nam') {
                $male++;
            } elseif ($record->getGender() === 'Nữ') {
                $female++;
            }
        }
        return [$male, $female];
    }

    private function countByLocationType(array $records): array
    {
        $urban = $rural = 0;
        foreach ($records as $record) {
            $address = strtolower($record->getAddress());
            if (str_contains($address, 'thành phố') || str_contains($address, 'tp.') || str_contains($address, 'quận') || str_contains($address, 'quan')) {
                $urban++;
            } else {
                $rural++;
            }
        }
        return [$urban, $rural];
    }

    private function initializeAgeDistribution(): array
    {
        return [
            ['range' => '0-17', 'count' => 0, 'percentage' => 0],
            ['range' => '18-35', 'count' => 0, 'percentage' => 0],
            ['range' => '36-55', 'count' => 0, 'percentage' => 0],
            ['range' => '56+', 'count' => 0, 'percentage' => 0]
        ];
    }

    private function getAgeGroup(DateTime $birthDate): int
    {
        $now = new DateTime();
        $age = $birthDate->diff($now)->y;
        if ($age <= 17) {
            return 0;
        } elseif ($age <= 35) {
            return 1;
        } elseif ($age <= 55) {
            return 2;
        }
        return 3;
    }

    private function finalizeDistribution(array $distribution, int $total): array
    {
        foreach ($distribution as &$group) {
            $group['percentage'] = round(($group['count'] / $total) * 100, 1);
        }
        return $distribution;
    }
}