<?php

declare(strict_types=1);

namespace App\Media\Validator;

class ImageFileValidator implements FileTypeValidatorInterface
{
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const MAX_SIZE_MB = 10;

    public function canValidate(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_IMAGE_TYPES, true);
    }

    public function validate(array $file): array
    {
        $errors = [];

        // Check file size (10MB limit for images)
        if ($file['size'] > self::MAX_SIZE_MB * 1024 * 1024) {
            $errors[] = 'Image file size cannot exceed '.self::MAX_SIZE_MB.'MB';
        }

        // Check for potentially dangerous image files
        if ($this->isPotentiallyDangerous($file)) {
            $errors[] = 'Image file appears to be potentially dangerous';
        }

        return $errors;
    }

    public function getTypeName(): string
    {
        return 'image';
    }

    private function isPotentiallyDangerous(array $file): bool
    {
        $filename = strtolower($file['name']);

        // Check for path traversal attempts
        if (false !== strpos($filename, '..') || false !== strpos($filename, '/') || false !== strpos($filename, '\\')) {
            return true;
        }

        // Check for suspicious extensions
        $suspiciousExtensions = ['.exe', '.bat', '.cmd', '.scr', '.pif', '.com', '.php', '.js', '.jar'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array('.'.$extension, $suspiciousExtensions, true)) {
            return true;
        }

        // Check for double extensions (image.jpg.exe)
        if (substr_count($filename, '.') > 1) {
            return true;
        }

        return false;
    }
}
