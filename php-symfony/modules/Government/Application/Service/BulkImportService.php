<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use App\Government\Contracts\DTO\CreateCitizenRequest;
use App\Government\Contracts\DTO\BulkImportResponse;
use App\Government\Domain\Repository\CitizenRepositoryInterface;
use Ramsey\Uuid\Uuid;

class BulkImportService
{
    public function __construct(
        private CitizenService $citizenService,
        private CitizenRepositoryInterface $citizenRepository
    ) {
    }

    /**
     * Import citizens from CSV or JSON data
     *
     * @param array $data Array of citizen data
     * @return BulkImportResponse
     */
    public function importCitizens(array $data): BulkImportResponse
    {
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $importedIds = [];

        foreach ($data as $index => $citizenData) {
            $result = $this->processCitizen($index, $citizenData);
            if ($result['ok']) {
                $successCount++;
                $importedIds[] = $result['citizen']->id;
            } else {
                $failedCount++;
                $errors[] = ['row' => $result['row'], 'error' => $result['error']];
            }
        }

        return new BulkImportResponse(
            successCount: $successCount,
            failedCount: $failedCount,
            totalCount: count($data),
            importedIds: $importedIds,
            errors: $errors
        );
    }

    private function processCitizen(int $row, array $citizenData): array
    {
        $validationError = $this->validateCitizenData($citizenData);
        if ($validationError) {
            return ['ok'=>false, 'row'=>$row+1, 'error'=>$validationError];
        }

        $existingCitizen = $this->citizenRepository->findByIdentityNumber($citizenData['identityNumber']);
        if ($existingCitizen !== null) {
            return ['ok'=>false, 'row'=>$row+1, 'error'=>"Citizen with identity number {$citizenData['identityNumber']} already exists"];
        }

        try {
            $createRequest = $this->buildCreateCitizenRequest($citizenData);
            $citizen = $this->citizenService->createCitizen($createRequest);
            return ['ok'=>true, 'citizen'=>$citizen];
        } catch (\Exception $e) {
            return ['ok'=>false, 'row'=>$row+1, 'error'=>$e->getMessage()];
        }
    }

    private function buildCreateCitizenRequest(array $data): CreateCitizenRequest
    {
        $request = new CreateCitizenRequest(
            fullName: $data['fullName'],
            identityNumber: $data['identityNumber'],
            province: $data['province'],
            district: $data['district'],
            ward: $data['ward'],
            street: $data['street']
        );

        $optionalFields = [
            'apartmentNumber', 'email', 'phone', 'dateOfBirth',
            'gender', 'ethnicity', 'nationality', 'occupation',
            'educationLevel', 'maritalStatus', 'householdId'
        ];

        foreach ($optionalFields as $field) {
            if (array_key_exists($field, $data)) {
                $request->$field = $data[$field];
            }
        }

        return $request;
    }

    /**
     * Parse CSV content into array
     *
     * @param string $csvContent
     * @return array
     */
    public function parseCSV(string $csvContent): array
    {
        $lines = explode("\n", $csvContent);
        $headers = str_getcsv(array_shift($lines));
        $data = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line);
            if (count($values) === count($headers)) {
                $data[] = array_combine($headers, $values);
            }
        }

        return $data;
    }

    /**
     * Generate CSV template for citizen import
     *
     * @return string
     */
    public function generateCSVTemplate(): string
    {
        $headers = ['fullName','identityNumber','province','district','ward','street','apartmentNumber','email','phone','dateOfBirth','gender','ethnicity','nationality','occupation','educationLevel','maritalStatus','householdId'];
        $exampleRow = ['Nguyen Van A','012345678901','Ha Noi','Ba Dinh','Phuc Xa','Tran Phu','12A','nguyenvana@example.com','0901234567','1990-01-01','male','Kinh','Vietnam','Engineer','University','married','HH001'];
        return implode(',', $headers) . "\n" . implode(',', $exampleRow);
    }

    /**
     * Validate citizen data
     *
     * @param array $data
     * @return string|null Error message or null if valid
     */
    private function validateCitizenData(array $data): ?string
    {
        $error = $this->checkRequiredFields($data);
        if ($error) return $error;
        $error = $this->validateIdentityNumber($data);
        if ($error) return $error;
        $error = $this->validateEmail($data);
        if ($error) return $error;
        $error = $this->validatePhone($data);
        if ($error) return $error;
        return $this->validateDateOfBirth($data);
    }

    private function checkRequiredFields(array $data): ?string
    {
        $requiredFields = ['fullName', 'identityNumber', 'province', 'district', 'ward', 'street'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return "Missing required field: {$field}";
            }
        }
        return null;
    }

    private function validateIdentityNumber(array $data): ?string
    {
        if (!preg_match('/^\d{9,12}$/', $data['identityNumber'])) {
            return 'Identity number must be 9-12 digits';
        }
        return null;
    }

    private function validateEmail(array $data): ?string
    {
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }
        return null;
    }

    private function validatePhone(array $data): ?string
    {
        if (!empty($data['phone']) && !preg_match('/^\d{10,11}$/', $data['phone'])) {
            return 'Phone number must be 10-11 digits';
        }
        return null;
    }

    private function validateDateOfBirth(array $data): ?string
    {
        if (!empty($data['dateOfBirth'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['dateOfBirth']);
            if (!$date || $date->format('Y-m-d') !== $data['dateOfBirth']) {
                return 'Date of birth must be in YYYY-MM-DD format';
            }
        }
        return null;
    }

    /**
     * Get import statistics
     *
     * @return array
     */
    public function getImportStatistics(): array
    {
        // In a real implementation, this would track imports in the database
        return [
            'totalImports' => 0,
            'lastImportDate' => null,
            'averageSuccessRate' => 0
        ];
    }
}
