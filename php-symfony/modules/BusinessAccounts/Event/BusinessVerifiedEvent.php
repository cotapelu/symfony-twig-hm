<?php

declare(strict_types=1);

namespace App\BusinessAccounts\Event;

use App\Shared\DomainEvent\DomainEvent;
use DateTimeImmutable;

class BusinessVerifiedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $businessId,
        public readonly string $userId,
        public readonly bool $verified,
        public readonly ?string $businessName = null,
        public readonly ?\DateTime $verifiedAt = null,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'business.verified';
    }

    protected function eventData(): array
    {
        return [
            'businessId' => $this->businessId,
            'userId' => $this->userId,
            'verified' => $this->verified,
            'businessName' => $this->businessName,
            'verifiedAt' => $this->verifiedAt?->format('c'),
        ];
    }
}
