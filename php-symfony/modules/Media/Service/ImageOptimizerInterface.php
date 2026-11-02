<?php

declare(strict_types=1);

namespace App\Media\Service;

interface ImageOptimizerInterface
{
    public function canHandle(string $mimeType): bool;

    public function optimize(string $filePath): void;
}
