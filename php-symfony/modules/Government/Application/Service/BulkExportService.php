<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use App\Government\Domain\Service\BulkExportServiceInterface;
use App\Government\Domain\Repository\CitizenRepositoryInterface;

class BulkExportService implements BulkExportServiceInterface
{
    private CitizenRepositoryInterface $citizenRepository;

    public function __construct(CitizenRepositoryInterface $citizenRepository)
    {
        $this->citizenRepository = $citizenRepository;
    }

    public function exportCitizensToCSV(array $filters = []): string
    {
        $citizens = $this->citizenRepository->findAll($filters);

        $header = ['id', 'fullName', 'email', 'phone', 'identityNumber', 'gender', 'isActive'];
        $rows = [];
        $rows[] = implode(',', $header);

        foreach ($citizens as $citizen) {
            $rows[] = implode(',', [
                $citizen->getId()->getValue(),
                $this->escapeCsv($citizen->getFullName()),
                $this->escapeCsv($citizen->getEmail() ?? ''),
                $this->escapeCsv($citizen->getPhone() ?? ''),
                $this->escapeCsv($citizen->getIdentityNumber() ?? ''),
                $this->escapeCsv($citizen->getGender()),
                $citizen->isActive() ? 'true' : 'false'
            ]);
        }

        return implode("\n", $rows);
    }

    public function exportCitizensToJSON(array $filters = []): string
    {
        $citizens = $this->citizenRepository->findAll($filters);
        $data = [];

        foreach ($citizens as $citizen) {
            $data[] = $citizen->toArray();
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function escapeCsv(string $field): string
    {
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            $field = '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }
}