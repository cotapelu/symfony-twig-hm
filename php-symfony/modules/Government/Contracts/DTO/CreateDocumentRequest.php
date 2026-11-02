<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class CreateDocumentRequest
{
    public function __construct(
        public string $title,
        public string $type,
        public string $content,
        public string $issuedBy,
        public ?string $documentNumber = null,
        public ?string $issuedDate = null,
        public ?string $effectiveDate = null,
        public ?string $signedBy = null,
        public ?string $summary = null,
        public array $tags = []
    ) {
    }
}
