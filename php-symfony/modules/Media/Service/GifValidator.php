<?php

declare(strict_types=1);

namespace App\Media\Service;

class GifValidator implements FileValidatorInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'image/gif' === $mimeType;
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

        $bytes = fread($handle, 6);
        fclose($handle);

        if ('GIF87a' !== $bytes && 'GIF89a' !== $bytes) {
            throw new \InvalidArgumentException('Invalid GIF file signature');
        }
    }
}
