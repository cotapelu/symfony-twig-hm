<?php

declare(strict_types=1);

namespace App\Media\Service;

class PngOptimizer implements ImageOptimizerInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'image/png' === $mimeType;
    }

    public function optimize(string $filePath): void
    {
        if (!function_exists('imagecreatefrompng')) {
            return;
        }

        $image = imagecreatefrompng($filePath);
        if (!$image) {
            return;
        }

        imagepng($image, $filePath, 6);
        imagedestroy($image);
    }
}
