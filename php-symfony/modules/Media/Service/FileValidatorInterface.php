<?php

declare(strict_types=1);

namespace App\Media\Service;

interface FileValidatorInterface
{
    public function canHandle(string $mimeType): bool;

    public function validateSignature(string $filePath): void;
}
