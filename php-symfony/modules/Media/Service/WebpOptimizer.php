<?php

declare(strict_types=1);

namespace App\Media\Service;

class WebpOptimizer implements ImageOptimizerInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'image/webp' === $mimeType;
    }

    public function optimize(string $filePath): void
    {
        if (!function_exists('imagecreatefromwebp')) {
            return;
        }

        $image = imagecreatefromwebp($filePath);
        if (!$image) {
            return;
        }

        imagewebp($image, $filePath, 85);
        imagedestroy($image);
    }
}
