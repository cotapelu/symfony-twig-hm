<?php

declare(strict_types=1);

namespace App\Media\Service;

class Mp4Validator implements FileValidatorInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'video/mp4' === $mimeType;
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

        $bytes = fread($handle, 12);
        fclose($handle);

        if (strlen($bytes) >= 12) {
            $boxSize = unpack('N', substr($bytes, 0, 4))[1];
            $boxType = substr($bytes, 4, 4);
            if ('ftyp' !== $boxType) {
                throw new \InvalidArgumentException('Invalid MP4 file signature');
            }
        } else {
            throw new \InvalidArgumentException('Invalid MP4 file signature');
        }
    }
}
