<?php

declare(strict_types=1);

namespace App\Media\Service;

class GifThumbnailGenerator implements ThumbnailGeneratorInterface
{
    public function canHandle(string $mimeType): bool
    {
        return 'image/gif' === $mimeType;
    }

    public function generate(string $originalPath, string $thumbnailPath): void
    {
        if (!function_exists('imagecreatefromgif')) {
            return;
        }

        $image = imagecreatefromgif($originalPath);
        if (!$image) {
            return;
        }

        $this->createThumbnail($image, $thumbnailPath, 'gif');
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

        if ('gif' === $format) {
            imagegif($thumbnail, $thumbnailPath);
        }

        imagedestroy($image);
        imagedestroy($thumbnail);
    }
}
