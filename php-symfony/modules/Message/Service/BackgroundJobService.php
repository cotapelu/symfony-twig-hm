<?php

declare(strict_types=1);

namespace App\Message\Service;

use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Background Job Service
 * Handles asynchronous processing of heavy operations.
 */
class BackgroundJobService
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Queue message for processing.
     */
    public function queueMessageProcessing(string $messageId, array $data)
    {
        // Queue for background processing
        $this->messageBus->dispatch(new ProcessMessageJob($messageId, $data));
    }

    /**
     * Queue file upload processing.
     */
    public function queueFileProcessing(string $fileId, string $filePath, array $metadata)
    {
        $this->messageBus->dispatch(new ProcessFileUploadJob($fileId, $filePath, $metadata));
    }

    /**
     * Queue notification sending.
     */
    public function queueNotification(string $userId, string $type, array $data)
    {
        $this->messageBus->dispatch(new SendNotificationJob($userId, $type, $data));
    }

    /**
     * Queue analytics processing.
     */
    public function queueAnalytics(string $eventType, array $data)
    {
        $this->messageBus->dispatch(new ProcessAnalyticsJob($eventType, $data));
    }

    /**
     * Queue cleanup operations.
     */
    public function queueCleanup(string $type, array $criteria)
    {
        $this->messageBus->dispatch(new CleanupJob($type, $criteria));
    }
}
