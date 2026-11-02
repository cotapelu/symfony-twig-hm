<?php

declare(strict_types=1);

namespace App\Media\Service;

class JpegValidator implements FileValidatorInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'image/jpeg' === $mimeType;
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

        $bytes = fread($handle, 3);
        fclose($handle);

        if (!str_starts_with(bin2hex($bytes), 'ffd8ff')) {
            throw new \InvalidArgumentException('Invalid JPEG file signature');
        }
    }
}
