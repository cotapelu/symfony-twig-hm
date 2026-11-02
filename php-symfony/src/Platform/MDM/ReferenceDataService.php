<?php

declare(strict_types=1);

namespace Platform\MDM;

use InvalidArgumentException;
use Platform\MDM\Exception\MDMException;

class ReferenceDataService
{
    private const ALLOWED_TYPES = ['country', 'province', 'district', 'ward', 'currency', 'language', 'timezone'];
    private const MAX_CODE_LENGTH = 50;
    private const MAX_TYPE_LENGTH = 50;

    public function __construct()
    {
        // Constructor for dependency injection
    }

    public function getReferenceData(string $type, string $code): array
    {
        $this->validateType($type);
        $this->validateCode($code);

        // TODO: Replace with actual persistence lookup when repository is available
        return [
            'type' => $type,
            'code' => $code,
            'description' => 'Reference data for ' . $type . ':' . $code,
            'valid_from' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'valid_to' => null,
        ];
    }

    public function searchReferenceData(string $type, array $criteria): array
    {
        $this->validateType($type);

        if (empty($criteria)) {
            throw new InvalidArgumentException('Search criteria cannot be empty');
        }

        if (count($criteria) > 10) {
            throw new InvalidArgumentException('Search criteria exceeds maximum of 10 items');
        }

        // TODO: Replace with actual persistence search when repository is available
        return [
            [
                'type' => $type,
                'code' => 'RESULT-1',
                'description' => 'Search result 1 for ' . $type,
            ],
            [
                'type' => $type,
                'code' => 'RESULT-2',
                'description' => 'Search result 2 for ' . $type,
            ],
        ];
    }

    public function validateReferenceData(string $type, string $code): bool
    {
        try {
            $this->validateType($type);
            $this->validateCode($code);
            // TODO: Replace with actual validation logic when repository is available
            return !empty($type) && !empty($code);
        } catch (InvalidArgumentException|MDMException $e) {
            return false;
        }
    }

    private function validateType(string $type): void
    {
        if (empty($type)) {
            throw new InvalidArgumentException('Reference data type cannot be empty');
        }

        if (strlen($type) > self::MAX_TYPE_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Reference data type exceeds maximum length of %d characters',
                self::MAX_TYPE_LENGTH
            ));
        }

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw MDMException::invalidReferenceData($type, '');
        }
    }

    private function validateCode(string $code): void
    {
        if (empty($code)) {
            throw new InvalidArgumentException('Reference data code cannot be empty');
        }

        if (strlen($code) > self::MAX_CODE_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Reference data code exceeds maximum length of %d characters',
                self::MAX_CODE_LENGTH
            ));
        }
    }
}