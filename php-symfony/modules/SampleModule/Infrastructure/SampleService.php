<?php

declare(strict_types=1);

namespace App\SampleModule\Infrastructure;

/**
 * Sample Infrastructure Service
 * Handles external concerns like file system, network, etc.
 * Used by Application layer when needed.
 */
class SampleService
{
    public function processFile(string $filePath): string
    {
        // Simulate file processing
        return strtoupper(file_get_contents($filePath));
    }

    public function sendNotification(string $message): void
    {
        // Simulate sending notification
        error_log('Notification sent: ' . $message);
    }
}