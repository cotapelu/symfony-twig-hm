<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\NotificationChannel;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing user notification channel preferences
 */
class NotificationChannelService
{
    public const CHANNEL_EMAIL = 'EMAIL';
    public const CHANNEL_SMS = 'SMS';
    public const CHANNEL_PUSH = 'PUSH';
    public const CHANNEL_WEBHOOK = 'WEBHOOK';

    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    /**
     * Register a notification channel for a user
     */
    public function registerChannel(
        string $userId,
        string $channel,
        ?string $endpoint = null,
        ?array $preferences = null
    ) {
        if (!$this->isValidChannel($channel)) {
            throw new \InvalidArgumentException('Invalid notification channel: ' . $channel);
        }

        $existing = $this->findExistingChannel($userId, $channel);
        if ($existing !== null) {
            $this->updateExistingChannel($existing, $endpoint, $preferences);
            return $existing;
        }
        return $this->createNewChannel($userId, $channel, $endpoint, $preferences);
    }

    /**
     * Get channel by ID
     */
    public function getChannel(string $channelId)
    {
        return $this->em->getRepository(NotificationChannel::class)->find($channelId);
    }

    /**
     * Get user's channel
     */
    public function getUserChannel(string $userId, string $channel)
    {
        return $this->em->getRepository(NotificationChannel::class)
            ->findOneBy(['userId' => $userId, 'channel' => $channel]);
    }

    /**
     * Get all channels for a user
     */
    public function getUserChannels(string $userId)
    {
        return $this->em->getRepository(NotificationChannel::class)
            ->findBy(['userId' => $userId, 'status' => 'ACTIVE']);
    }

    /**
     * Get active channels for a user
     */
    public function getActiveChannels(string $userId)
    {
        return $this->getUserChannels($userId);
    }

    /**
     * Check if user has a specific channel
     */
    public function hasChannel(string $userId, string $channel)
    {
        return $this->getUserChannel($userId, $channel) !== null;
    }

    /**
     * Check if user has any active channels
     */
    public function hasAnyChannel(string $userId)
    {
        return count($this->getUserChannels($userId)) > 0;
    }

    /**
     * Disable a channel
     */
    public function disableChannel(string $userId, string $channel)
    {
        $notificationChannel = $this->getUserChannel($userId, $channel);
        if ($notificationChannel === null) {
            return false;
        }

        $notificationChannel->setStatus('DISABLED');
        $this->em->flush();

        $this->logger->info('Notification channel disabled', [
            'user_id' => $userId,
            'channel' => $channel,
        ]);

        return true;
    }

    /**
     * Enable a channel
     */
    public function enableChannel(string $userId, string $channel)
    {
        $notificationChannel = $this->getUserChannel($userId, $channel);
        if ($notificationChannel === null) {
            return false;
        }

        $notificationChannel->setStatus('ACTIVE');
        $this->em->flush();

        $this->logger->info('Notification channel enabled', [
            'user_id' => $userId,
            'channel' => $channel,
        ]);

        return true;
    }

    /**
     * Update channel endpoint
     */
    public function updateEndpoint(string $userId, string $channel, string $endpoint)
    {
        $notificationChannel = $this->getUserChannel($userId, $channel);
        if ($notificationChannel === null) {
            return false;
        }

        $notificationChannel->setEndpoint($endpoint);
        $this->em->flush();

        $this->logger->info('Notification channel endpoint updated', [
            'user_id' => $userId,
            'channel' => $channel,
        ]);

        return true;
    }

    /**
     * Update channel preferences
     */
    public function updatePreferences(string $userId, string $channel, array $preferences)
    {
        $notificationChannel = $this->getUserChannel($userId, $channel);
        if ($notificationChannel === null) {
            return false;
        }

        $notificationChannel->setPreferences($preferences);
        $this->em->flush();

        $this->logger->info('Notification channel preferences updated', [
            'user_id' => $userId,
            'channel' => $channel,
        ]);

        return true;
    }

    /**
     * Delete a channel
     */
    public function deleteChannel(string $userId, string $channel)
    {
        $notificationChannel = $this->getUserChannel($userId, $channel);
        if ($notificationChannel === null) {
            return false;
        }

        $this->em->remove($notificationChannel);
        $this->em->flush();

        $this->logger->info('Notification channel deleted', [
            'user_id' => $userId,
            'channel' => $channel,
        ]);

        return true;
    }

    /**
     * Get all users with a specific channel type
     */
    public function getUsersByChannel(string $channel)
    {
        return $this->em->getRepository(NotificationChannel::class)
            ->findBy(['channel' => $channel, 'status' => 'ACTIVE']);
    }

    /**
     * Get available channel types
     */
    public function getAvailableChannels()
    {
        return [
            self::CHANNEL_EMAIL,
            self::CHANNEL_SMS,
            self::CHANNEL_PUSH,
            self::CHANNEL_WEBHOOK,
        ];
    }

    /**
     * Validate channel
     */
    public function isValidChannel(string $channel)
    {
        return in_array($channel, $this->getAvailableChannels(), true);
    }

    /**
     * Generate unique ID
     */
    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    private function findExistingChannel(string $userId, string $channel): ?NotificationChannel
    {
        return $this->em->getRepository(NotificationChannel::class)
            ->findOneBy(['userId' => $userId, 'channel' => $channel]);
    }

    private function updateExistingChannel(
        NotificationChannel $chan,
        ?string $endpoint,
        ?array $preferences
    ): void {
        $chan->setEndpoint($endpoint);
        $chan->setPreferences($preferences);
        $chan->setStatus('ACTIVE');
        $this->em->flush();

        $this->logger->info('Notification channel updated', [
            'user_id' => $chan->getUserId(),
            'channel' => $chan->getChannel(),
        ]);
    }

    private function createNewChannel(
        string $userId,
        string $channel,
        ?string $endpoint,
        ?array $preferences
    ): NotificationChannel {
        $notificationChannel = new NotificationChannel(
            $this->generateId(),
            $userId,
            $channel
        );
        $notificationChannel->setEndpoint($endpoint);
        $notificationChannel->setPreferences($preferences);

        $this->em->persist($notificationChannel);
        $this->em->flush();

        $this->logger->info('Notification channel registered', [
            'user_id' => $userId,
            'channel' => $channel,
        ]);

        return $notificationChannel;
    }
}
