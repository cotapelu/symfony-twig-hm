<?php

declare(strict_types=1);

namespace App\Media\Service;

class JpegOptimizer implements ImageOptimizerInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'image/jpeg' === $mimeType;
    }

    public function optimize(string $filePath): void
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return;
        }

        $image = imagecreatefromjpeg($filePath);
        if (!$image) {
            return;
        }

        imagejpeg($image, $filePath, 85);
        imagedestroy($image);
    }
}
