<?php

declare(strict_types=1);

namespace App\Notification\Service;

interface NotificationBodyGeneratorInterface
{
    public function canHandle(string $type): bool;

    public function generateBody(array $data): string;
}
