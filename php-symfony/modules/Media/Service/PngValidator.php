<?php

declare(strict_types=1);

namespace App\Media\Service;

class PngValidator implements FileValidatorInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'image/png' === $mimeType;
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

        $pngSignature = "\x89PNG\x0D\x0A\x1A\x0A";
        if ($bytes !== $pngSignature) {
            throw new \InvalidArgumentException('Invalid PNG file signature');
        }
    }
}
