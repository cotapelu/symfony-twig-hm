<?php

declare(strict_types=1);

namespace App\Media\Service;

/**
 * File validation service using magic numbers (file signatures).
 * Validates actual file content against declared MIME types.
 */
class FileValidationService
{
    private const MAX_FILE_SIZE = 10485760; // 10MB
    private const MAX_IMAGE_WIDTH = 8000;
    private const MAX_IMAGE_HEIGHT = 8000;

    private array $magicNumbers;

    public function __construct()
    {
        // Magic numbers (file signatures) for common file types
        $this->magicNumbers = [
            // Images
            'ffd8ff' => ['jpg', 'jpeg', 'application/jpeg', 'image/jpeg'],
            '89504e47' => ['png', 'application/png', 'image/png'],
            '47494638' => ['gif', 'image/gif'],
            '52494646' => ['webp', 'image/webp'],
            '00000000' => ['jp2', 'image/jp2'],
            // Documents
            '25504446' => ['pdf', 'application/pdf'],
            'd0cf11e0' => ['doc', 'docx', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            '504b0304' => ['docx', 'xlsx', 'pptx', 'zip', 'application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            // Text
            'efbbbf' => ['txt', 'text/plain'], // UTF-8 BOM
            // Videos (basic signatures)
            '00000018' => ['mp4', 'video/mp4'],
            '00000020' => ['mp4', 'video/mp4'],
            '1a45dfa3' => ['webm', 'video/webm'],
        ];
    }

    /**
     * Validate file using magic numbers (file signatures).
     */
    public function validateMagicNumber(string $filePath, string $expectedMimeType)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $handle = fopen($filePath, 'rb');
        if (false === $handle) {
            return false;
        }

        $bytes = fread($handle, 8);
        fclose($handle);

        if (false === $bytes || strlen($bytes) < 4) {
            return false;
        }

        // Get hex signature (first 4 bytes)
        $signature = bin2hex(substr($bytes, 0, 4));
        $signatureLower = strtolower($signature);

        // Check if signature matches expected file type
        foreach ($this->magicNumbers as $magicSignature => $types) {
            if (str_starts_with($signatureLower, (string)$magicSignature)) {
                // Check if expected MIME type matches
                foreach ($types as $type) {
                    if (false !== strpos($expectedMimeType, $type)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Validate file size.
     */
    public function validateFileSize(int $fileSize, int $maxSize = self::MAX_FILE_SIZE)
    {
        if ($fileSize > $maxSize) {
            throw new \InvalidArgumentException('File size exceeds limit');
        }
    }

    /**
     * Validate image file dimensions.
     */
    public function validateImageDimensions(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('File does not exist');
        }

        $imageInfo = getimagesize($filePath);
        if (false === $imageInfo) {
            throw new \InvalidArgumentException('Invalid image file');
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if ($width > self::MAX_IMAGE_WIDTH || $height > self::MAX_IMAGE_HEIGHT) {
            throw new \InvalidArgumentException(
                "Image dimensions exceed maximum allowed size of " . self::MAX_IMAGE_WIDTH . "x" . self::MAX_IMAGE_HEIGHT . ". Actual: {$width}x{$height}"
            );
        }

        return [
            'width' => $width,
            'height' => $height,
            'type' => $imageInfo[2],
            'mime' => $imageInfo['mime'],
        ];
    }

    /**
     * Validate filename for dangerous patterns.
     */
    public function validateFilename(string $filename)
    {
        $sanitizedFilename = $this->sanitizeFilename($filename);

        if ($sanitizedFilename !== $filename) {
            throw new \InvalidArgumentException('Filename contains invalid characters');
        }
    }

    /**
     * Sanitize filename.
     */
    public function sanitizeFilename(string $filename)
    {
        // Check for dangerous patterns FIRST (before basename strips path)
        $dangerousPatterns = ['..', '../', '..\\', '\\..', '/..', 'php', 'exe', 'bat', 'cmd', 'scr', 'pif', 'com', 'jar', 'js', '<script', '<?php', '<%'];
        foreach ($dangerousPatterns as $pattern) {
            if (false !== stripos($filename, $pattern)) {
                throw new \InvalidArgumentException('Dangerous filename pattern detected');
            }
        }

        // Remove path components
        $filename = basename($filename);

        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        return $filename;
    }

    /**
     * Generate secure unique filename.
     */
    public function generateSecureFilename(string $originalFilename)
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $basename = pathinfo($originalFilename, PATHINFO_FILENAME);

        // Sanitize basename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);

        // Generate unique filename
        $uniqueId = uniqid('file_', true);

        return $uniqueId.'_'.$basename.'.'.$extension;
    }
}
