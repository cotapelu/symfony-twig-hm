<?php

declare(strict_types=1);

namespace App\Feedback\Application\DTO;

class SubmitFeedbackRequest
{
    public function __construct(
        public readonly string $type,
        public readonly string $wardCode,
        public readonly string $title,
        public readonly string $description,
        public readonly string $citizenName,
        public readonly ?string $citizenPhone = null,
        public readonly ?string $citizenEmail = null,
        public readonly ?string $department = null,
        public readonly ?string $serviceType = null,
        public readonly ?int $rating = null,
        public readonly ?array $attachments = null,
        public readonly bool $isAnonymous = false
    ) {
        $validTypes = ['service_rating', 'complaint', 'suggestion', 'technical_issue'];
        if (!in_array($type, $validTypes, true)) {
            throw new \InvalidArgumentException('Invalid feedback type');
        }
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Title is required');
        }
        if (empty(trim($description))) {
            throw new \InvalidArgumentException('Description is required');
        }
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
    }
}
