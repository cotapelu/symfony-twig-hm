<?php
declare(strict_types=1);

namespace App\Queue\Application\DTO;

// Marker abstract class for Queue DTOs to satisfy Symfony autowiring
abstract class QueueDTOs {}

class CreateQueueRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $wardCode,
        public readonly array $serviceTypes,
        public readonly ?string $departmentName = null,
        public readonly ?string $description = null,
        public readonly int $counterCount = 1,
        public readonly ?array $operatingHours = null
    ) {
        if (empty($serviceTypes)) {
            throw new \InvalidArgumentException('Service types cannot be empty');
        }
        if ($counterCount < 1) {
            throw new \InvalidArgumentException('Counter count must be at least 1');
        }
    }
}

class UpdateQueueRequest
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?array $serviceTypes = null,
        public readonly ?string $description = null,
        public readonly ?int $counterCount = null,
        public readonly ?array $operatingHours = null
    ) {
    }
}

class JoinQueueRequest
{
    public function __construct(
        public readonly string $queueId,
        public readonly string $serviceType,
        public readonly string $citizenName,
        public readonly ?string $citizenPhone = null,
        public readonly ?string $citizenId = null,
        public readonly int $priority = 0
    ) {
        if (empty(trim($citizenName))) {
            throw new \InvalidArgumentException('Citizen name is required');
        }
    }
}

class CallNextTicketRequest
{
    public function __construct(
        public readonly string $queueId,
        public readonly string $officerId,
        public readonly string $counterNumber
    ) {
        if (empty(trim($counterNumber))) {
            throw new \InvalidArgumentException('Counter number is required');
        }
    }
}

class CompleteTicketRequest
{
    public function __construct(
        public readonly ?string $notes = null
    ) {
    }
}

class CancelTicketRequest
{
    public function __construct(
        public readonly ?string $reason = null
    ) {
    }
}
