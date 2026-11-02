<?php

declare(strict_types=1);

namespace App\Media\Validator;

class VideoFileValidator implements FileTypeValidatorInterface
{
    private const ALLOWED_VIDEO_TYPES = [
        'video/mp4',
        'video/avi',
        'video/mov',
        'video/wmv',
    ];

    private const MAX_SIZE_MB = 100; // Videos can be much larger

    public function canValidate(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_VIDEO_TYPES, true);
    }

    public function validate(array $file): array
    {
        $errors = [];

        // Check file size (100MB limit for videos)
        if ($file['size'] > self::MAX_SIZE_MB * 1024 * 1024) {
            $errors[] = 'Video file size cannot exceed '.self::MAX_SIZE_MB.'MB';
        }

        // Check for potentially dangerous video files
        if ($this->isPotentiallyDangerous($file)) {
            $errors[] = 'Video file appears to be potentially dangerous';
        }

        return $errors;
    }

    public function getTypeName(): string
    {
        return 'video';
    }

    private function isPotentiallyDangerous(array $file): bool
    {
        $filename = strtolower($file['name']);

        // Check for path traversal
        if (false !== strpos($filename, '..') || false !== strpos($filename, '/') || false !== strpos($filename, '\\')) {
            return true;
        }

        // Check for suspicious extensions
        $dangerousExtensions = ['.exe', '.bat', '.cmd', '.scr', '.php', '.js'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return in_array('.'.$extension, $dangerousExtensions, true);
    }
}
