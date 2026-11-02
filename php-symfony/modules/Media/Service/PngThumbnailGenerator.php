<?php

declare(strict_types=1);

namespace App\Media\Service;

class PngThumbnailGenerator implements ThumbnailGeneratorInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'image/png' === $mimeType;
    }

    public function generate(string $originalPath, string $thumbnailPath): void
    {
        if (!function_exists('imagecreatefrompng')) {
            return;
        }

        $image = imagecreatefrompng($originalPath);
        if (!$image) {
            return;
        }

        $this->createThumbnail($image, $thumbnailPath, 'png');
    }

    private function createThumbnail($image, string $thumbnailPath, string $format)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $maxSize = 200;

        if ($width > $height) {
            $newWidth = $maxSize;
            $newHeight = (int) ($height * ($maxSize / $width));
        } else {
            $newHeight = $maxSize;
            $newWidth = (int) ($width * ($maxSize / $height));
        }

        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        if ('png' === $format) {
            imagepng($thumbnail, $thumbnailPath, 6);
        }

        imagedestroy($image);
        imagedestroy($thumbnail);
    }
}
