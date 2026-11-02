<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class DocumentResponse
{
    public function __construct(
        public string $id,
        public string $title,
        public string $type,
        public string $content,
        public ?string $documentNumber,
        public ?string $issuedDate,
        public ?string $effectiveDate,
        public string $issuedBy,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
        public array $attachments,
        public array $tags,
        public ?string $signedBy,
        public ?string $summary
    ) {
    }
    
    public static function fromEntity($document): self
    {
        return new self(
            $document->getId()->toString(),
            $document->getTitle(),
            $document->getType(),
            $document->getContent(),
            $document->getDocumentNumber(),
            $document->getIssuedDate()?->format('Y-m-d'),
            $document->getEffectiveDate()?->format('Y-m-d'),
            $document->getIssuedBy(),
            $document->getStatus(),
            $document->getCreatedAt()->format('Y-m-d H:i:s'),
            $document->getUpdatedAt()->format('Y-m-d H:i:s'),
            $document->getAttachments(),
            $document->getTags(),
            $document->getSignedBy(),
            $document->getSummary()
        );
    }
}
