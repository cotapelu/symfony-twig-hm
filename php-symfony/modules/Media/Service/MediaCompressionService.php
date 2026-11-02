<?php

declare(strict_types=1);

namespace App\Media\Service;

class MediaCompressionService
{
    private const MAX_IMAGE_SIZE = 5242880;
    private const MAX_VIDEO_SIZE = 104857600;
    private const THUMBNAIL_WIDTH = 200;
    private const THUMBNAIL_HEIGHT = 200;

    public function __construct(
        private string $uploadDir
    ) {
    }

    public function compressImage(string $imagePath)
    {
        $imageInfo = $this->ensureImageFileValid($imagePath);
        $fileSize = \filesize($imagePath);
        $mimeType = $imageInfo['mime'];

        if ($fileSize > self::MAX_IMAGE_SIZE) {
            $compressedPath = $this->performCompression($imagePath);
            return $this->buildCompressedResult($fileSize, $compressedPath);
        }

        return $this->buildUncompressedResult($fileSize, $mimeType, $imagePath);
    }

    private function ensureImageFileValid(string $imagePath): array
    {
        if (!\file_exists($imagePath)) {
            throw new \Exception('Image file not found');
        }
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            throw new \Exception('Invalid image file');
        }
        return $imageInfo;
    }

    private function performCompression(string $imagePath): string
    {
        return $this->resizeImage($imagePath);
    }

    private function buildCompressedResult(int $originalSize, string $compressedPath): array
    {
        $compressedSize = \filesize($compressedPath);
        $compressionRatio = ($originalSize - $compressedSize) / $originalSize * 100;
        return [
            'path' => $compressedPath,
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'compression_ratio' => round($compressionRatio, 2),
            'format' => 'jpeg',
        ];
    }

    private function buildUncompressedResult(int $fileSize, string $mimeType, string $imagePath): array
    {
        return [
            'path' => $imagePath,
            'original_size' => $fileSize,
            'compressed_size' => $fileSize,
            'compression_ratio' => 0,
            'format' => $this->getImageFormat($mimeType),
        ];
    }

    public function generateThumbnail(string $mediaPath, string $mediaType)
    {
        $thumbnailPath = $this->computeThumbnailPath($mediaPath);
        if ($mediaType === 'image') {
            $this->generateImageThumbnail($mediaPath, $thumbnailPath);
        } elseif ($mediaType === 'video') {
            $this->generateVideoThumbnail($mediaPath, $thumbnailPath);
        }
        return $thumbnailPath;
    }

    private function computeThumbnailPath(string $mediaPath): string
    {
        $thumbnailDir = $this->uploadDir . '/thumbnails/';
        if (!\is_dir($thumbnailDir)) {
            \mkdir($thumbnailDir, 0755, true);
        }
        $extension = \pathinfo($mediaPath, PATHINFO_EXTENSION);
        return $thumbnailDir . md5($mediaPath) . '_thumb.' . $extension;
    }

    private function resizeImage(string $imagePath): string
    {
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        $sourceImage = $this->createSourceImage($mimeType, $imagePath);
        [$originalWidth, $originalHeight] = [imagesx($sourceImage), imagesy($sourceImage)];
        [$newWidth, $newHeight] = $this->calculateNewDimensions($originalWidth, $originalHeight);
        $thumbnail = $this->createThumbnail($sourceImage, $originalWidth, $originalHeight, $newWidth, $newHeight);
        $thumbnailPath = $this->buildThumbnailPath($imagePath);
        $this->ensureDirectoryExists(\dirname($thumbnailPath));
        imagejpeg($thumbnail, $thumbnailPath, 85);
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
        return $thumbnailPath;
    }

    private function createSourceImage(string $mimeType, string $imagePath)
    {
        return match($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($imagePath),
            'image/png' => imagecreatefrompng($imagePath),
            'image/gif' => imagecreatefromgif($imagePath),
            'image/webp' => imagecreatefromwebp($imagePath),
            default => throw new \Exception('Unsupported image format'),
        };
    }

    private function calculateNewDimensions(int $originalWidth, int $originalHeight): array
    {
        $newWidth = self::THUMBNAIL_WIDTH;
        $newHeight = (int)($originalHeight * ($newWidth / $originalWidth));
        return [$newWidth, $newHeight];
    }

    private function createThumbnail($sourceImage, int $originalWidth, int $originalHeight, int $newWidth, int $newHeight)
    {
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        return $thumbnail;
    }

    private function buildThumbnailPath(string $imagePath): string
    {
        return $this->uploadDir . '/compressed/' . \pathinfo($imagePath, PATHINFO_FILENAME) . '.jpg';
    }

    private function ensureDirectoryExists(string $dir): void
    {
        if (!\is_dir($dir)) {
            \mkdir($dir, 0755, true);
        }
    }

    private function generateImageThumbnail(string $imagePath, string $thumbnailPath)
    {
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        $sourceImage = $this->createSourceImage($mimeType, $imagePath);
        [$originalWidth, $originalHeight] = [imagesx($sourceImage), imagesy($sourceImage)];
        [$newWidth, $newHeight] = $this->calculateNewDimensions($originalWidth, $originalHeight);
        $thumbnail = $this->createThumbnail($sourceImage, $originalWidth, $originalHeight, $newWidth, $newHeight);
        $this->ensureDirectoryExists(\dirname($thumbnailPath));
        imagejpeg($thumbnail, $thumbnailPath, 85);
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    }

    private function generateVideoThumbnail(string $videoPath, string $thumbnailPath)
    {
        $command = sprintf(
            'ffmpeg -i %s -ss 00:00:01.000 -vframes 1 -vf "scale=%d:%d" %s 2>&1',
            escapeshellarg($videoPath),
            $this::THUMBNAIL_WIDTH,
            $this::THUMBNAIL_HEIGHT,
            escapeshellarg($thumbnailPath)
        );

        \exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $image = imagecreatetruecolor($this::THUMBNAIL_WIDTH, $this::THUMBNAIL_HEIGHT);
            $bgColor = imagecolorallocate($image, 200, 200, 200);
            imagefill($image, 0, 0, $bgColor);
            imagejpeg($image, $thumbnailPath, 75);
            imagedestroy($image);
        }
    }

    public function getMediaInfo(string $mediaPath)
    {
        if (!\file_exists($mediaPath)) {
            throw new \Exception('Media file not found');
        }
        $fileSize = \filesize($mediaPath);
        $mimeType = mime_content_type($mediaPath);
        $extension = \pathinfo($mediaPath, PATHINFO_EXTENSION);
        $info = [
            'size' => $fileSize,
            'size_mb' => round($fileSize / 1048576, 2),
            'mime_type' => $mimeType,
            'extension' => $extension,
        ];
        return $this->enrichMediaInfo($info, $mimeType, $mediaPath);
    }

    private function enrichMediaInfo(array $info, string $mimeType, string $mediaPath): array
    {
        if (str_starts_with($mimeType, 'image/')) {
            $imageInfo = getimagesize($mediaPath);
            $info['width'] = $imageInfo[0];
            $info['height'] = $imageInfo[1];
            $info['format'] = $this->getImageFormat($mimeType);
        } elseif (str_starts_with($mimeType, 'video/')) {
            $duration = $this->getVideoDuration($mediaPath);
            $info['duration'] = $duration;
            $info['format'] = $info['extension'];
        }
        return $info;
    }

    private function getVideoDuration(string $videoPath)
    {
        $command = sprintf('ffprobe -i %s -show_entries format=duration -v quiet -of csv="p=0" 2>&1', escapeshellarg($videoPath));
        \exec($command, $output, $returnCode);

        if ($returnCode === 0 && !empty($output)) {
            $duration = (float) $output[0];
            return (int) $duration;
        }

        return 0;
    }

    private function getImageFormat(string $mimeType)
    {
        return match($mimeType) {
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'unknown',
        };
    }
}
