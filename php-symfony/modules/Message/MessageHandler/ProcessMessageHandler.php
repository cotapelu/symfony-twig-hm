<?php

declare(strict_types=1);

namespace App\Message\MessageHandler;

use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Service\ProcessMessageJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for processing messages asynchronously.
 */
#[AsMessageHandler]
class ProcessMessageHandler
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessMessageJob $job): void
    {
        try {
            $this->logger->info('Processing message', ['messageId' => $job->messageId]);

            // Get message
            $message = $this->messageRepository->findById($job->messageId);
            if (!$message) {
                $this->logger->warning('Message not found for processing', ['messageId' => $job->messageId]);

                return;
            }

            // Process message content (e.g., link previews, mentions, etc.)
            $processedContent = $this->processMessageContent($message->getContent());

            // Update message if content changed
            if ($processedContent !== $message->getContent()) {
                $message->setContent($processedContent);
                $this->messageRepository->save($message);
            }

            $this->logger->info('Message processed successfully', ['messageId' => $job->messageId]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process message', [
                'messageId' => $job->messageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function processMessageContent(string $content): string
    {
        // Process URLs for previews
        $content = preg_replace_callback(
            '/https?:\/\/[^\s]+/',
            function ($matches) {
                $url = $matches[0];

                // In a real implementation, you'd fetch URL metadata
                // For now, just return the URL as-is
                return $url;
            },
            $content
        );

        // Process mentions (@username)
        $content = preg_replace_callback(
            '/@(\w+)/',
            function ($matches) {
                $username = $matches[1];

                // In a real implementation, you'd validate and link users
                return "@{$username}";
            },
            $content
        );

        return $content;
    }
}
