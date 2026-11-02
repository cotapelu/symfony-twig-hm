<?php

declare(strict_types=1);

namespace App\Media\Service;

/**
 * Image Processing Service
 * Handles image optimization and thumbnail generation.
 */
class ImageProcessingService
{
    private string $uploadDirectory;
    private FileProcessorFactory $processorFactory;

    public function __construct(
        FileProcessorFactory $processorFactory,
        ?string $uploadDirectory = null
    ) {
        $this->processorFactory = $processorFactory;
        $this->uploadDirectory = $uploadDirectory ?? __DIR__.'/../../../public/uploads/communication';
    }

    public function optimizeImage(string $filepath, string $mimeType)
    {
        $optimizer = $this->processorFactory->getOptimizer($mimeType);
        if (!$optimizer) {
            return;
        }

        $optimizer->optimize($filepath);
    }

    public function generateThumbnail(string $originalPath, string $filename, string $mimeType)
    {
        $generator = $this->processorFactory->getThumbnailGenerator($mimeType);
        if (!$generator) {
            return null;
        }

        $thumbnailFilename = 'thumb_'.$filename;
        $thumbnailPath = $this->uploadDirectory.'/'.$thumbnailFilename;

        $generator->generate($originalPath, $thumbnailPath);

        return '/uploads/communication/'.$thumbnailFilename;
    }

    public function isImage(string $mimeType)
    {
        return str_starts_with($mimeType, 'image/');
    }
}
