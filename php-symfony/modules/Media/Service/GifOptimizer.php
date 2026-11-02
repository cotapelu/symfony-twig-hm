<?php

declare(strict_types=1);

namespace App\Media\Service;

class GifOptimizer implements ImageOptimizerInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'image/gif' === $mimeType;
    }

    public function optimize(string $filePath): void
    {
        if (!function_exists('imagecreatefromgif')) {
            return;
        }

        $image = imagecreatefromgif($filePath);
        if (!$image) {
            return;
        }

        imagegif($image, $filePath);
        imagedestroy($image);
    }
}
