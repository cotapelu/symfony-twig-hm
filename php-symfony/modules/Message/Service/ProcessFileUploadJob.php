<?php

declare(strict_types=1);

namespace App\Message\Service;

class ProcessFileUploadJob
{
    public function __construct(
        public readonly string $fileId,
        public readonly string $filePath,
        public readonly array $metadata,
    ) {
    }
}
