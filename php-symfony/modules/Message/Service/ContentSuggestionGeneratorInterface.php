<?php

declare(strict_types=1);

namespace App\Message\Service;

interface ContentSuggestionGeneratorInterface
{
    public function canHandle(string $type): bool;

    public function generateSuggestions(): array;
}
