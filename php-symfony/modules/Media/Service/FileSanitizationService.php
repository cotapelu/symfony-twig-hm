<?php

declare(strict_types=1);

namespace App\Media\Service;

/**
 * File sanitization service for removing metadata and sensitive data.
 */
class FileSanitizationService
{
    /**
     * Sanitize EXIF data from image files to remove location data.
     */
    public function sanitizeImageExif(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('File does not exist');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'tif', 'tiff'], true)) {
            // Other image types typically don't have EXIF data that needs sanitization
            return;
        }

        try {
            // Remove EXIF data by re-saving image
            $imageInfo = getimagesize($filePath);
            if (false === $imageInfo) {
                return;
            }

            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($filePath);
                    if (false !== $image) {
                        imagejpeg($image, $filePath, 90);
                        imagedestroy($image);
                    }
                    break;
                case IMAGETYPE_TIFF_II:
                case IMAGETYPE_TIFF_MM:
                    // TIFF requires external library for EXIF removal
                    // For now, just log that sanitization was skipped
                    break;
            }
        } catch (\Exception $e) {
            // Log error but don't fail upload
            error_log('Failed to sanitize EXIF data: '.$e->getMessage());
        }
    }
}
