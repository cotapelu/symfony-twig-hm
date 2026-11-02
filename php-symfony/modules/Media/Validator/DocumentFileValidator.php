<?php

declare(strict_types=1);

namespace App\Media\Validator;

class DocumentFileValidator implements FileTypeValidatorInterface
{
    private const ALLOWED_DOCUMENT_TYPES = [
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private const MAX_SIZE_MB = 25; // Documents can be larger than images

    public function canValidate(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_DOCUMENT_TYPES, true);
    }

    public function validate(array $file): array
    {
        $errors = [];

        // Check file size (25MB limit for documents)
        if ($file['size'] > self::MAX_SIZE_MB * 1024 * 1024) {
            $errors[] = 'Document file size cannot exceed '.self::MAX_SIZE_MB.'MB';
        }

        // Check for potentially dangerous document files
        if ($this->isPotentiallyDangerous($file)) {
            $errors[] = 'Document file appears to be potentially dangerous';
        }

        // Additional validation for text files
        if ('text/plain' === $file['type'] && $this->isSuspectedBinary($file)) {
            $errors[] = 'Text file appears to contain binary content';
        }

        return $errors;
    }

    public function getTypeName(): string
    {
        return 'document';
    }

    private function isPotentiallyDangerous(array $file): bool
    {
        $filename = strtolower($file['name']);

        // Check for path traversal attempts
        if (false !== strpos($filename, '..') || false !== strpos($filename, '/') || false !== strpos($filename, '\\')) {
            return true;
        }

        // Check for executable file extensions disguised as documents
        $dangerousExtensions = ['.exe', '.bat', '.cmd', '.scr', '.pif', '.com', '.jar', '.php', '.js'];
        $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array('.'.$fileExtension, $dangerousExtensions, true)) {
            return true;
        }

        // Check for double extensions (document.pdf.exe)
        if (substr_count($filename, '.') > 1) {
            return true;
        }

        return false;
    }

    private function isSuspectedBinary(array $file): bool
    {
        // For text files, check if they might actually be binary
        // This is a simple check - in production you'd want more sophisticated detection
        if ('text/plain' !== $file['type']) {
            return false;
        }

        // Check file size - very small files might be scripts
        if ($file['size'] < 10) {
            return true;
        }

        // Check for suspicious filename patterns
        $filename = strtolower($file['name']);
        if (str_contains($filename, 'script') || str_contains($filename, 'exec')) {
            return true;
        }

        return false;
    }
}
