<?php

declare(strict_types=1);

namespace App\Media\Service;

interface ThumbnailGeneratorInterface
{
    public function canHandle(string $mimeType): bool;

    public function generate(string $originalPath, string $thumbnailPath): void;
}
