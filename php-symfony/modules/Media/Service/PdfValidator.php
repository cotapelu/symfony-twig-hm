<?php

declare(strict_types=1);

namespace App\Media\Service;

class PdfValidator implements FileValidatorInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'application/pdf' === $mimeType;
    }

    public function validateSignature(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException('Could not read file to validate signature');
        }

        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new \InvalidArgumentException('Could not open file');
        }

        $bytes = fread($handle, 8);
        fclose($handle);

        if (!str_starts_with($bytes, '%PDF-')) {
            throw new \InvalidArgumentException('Invalid PDF file signature');
        }
    }
}
