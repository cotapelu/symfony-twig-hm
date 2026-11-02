<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Redis;
use RedisException;

/**
 * EventBus Factory - Creates event publisher/subscriber for cross-language communication.
 */
class EventBusFactory
{
    public function __construct(
        private Redis $redis,
    )
    {
        $this->redis = $redis;
    }

    /**
     * Publish an event to the EventBus.
     */
    public function publish(string $channel, array $data): void
    {
        $payload = json_encode($data);
        $redis = $this->redis;
        // @phpstan-ignore-next-line
        try {
            $redis->publish($channel, $payload, function (...$args) use ($payload, $redis, $channel) {
                // Use PUBLISH with Redis Stream events
                $actualChannel = $args[1] ?? $channel;
                return $redis->publish('events:'.$actualChannel, $payload);
            });

            $redis->expire('events:'.$channel, 24*60*60); // 24 hours TTL
        } catch (RedisException $e) {
            error_log('EventBus publish failed: ' . $e->getMessage());
        }
    }

    /**
     * Subscribe to an event channel.
     */
    public function subscribe(string $channel, callable $callback): void
    {
        $redis = $this->redis;

        try {
            $redis->subscribe(['events:'.$channel], $callback);

            // @phpstan-ignore-next-line
            while (true) {
                $result = $redis->brpop('events:'.$channel, 1);
                if ($result !== false && $result !== null && is_array($result)) {
                    $callback($result);
                }
            }
        } catch (RedisException $e) {
            error_log('EventBus subscribe failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions that might break the loop
            error_log('EventBus subscribe error: ' . $e->getMessage());
        }
    }

    /**
     * Publish an event and return.
     */
    public function publishSync(string $channel, array $data): array
    {
        $payload = json_encode($data);

        try {
            $this->redis->publish('events:'.$channel, $payload);
            $this->redis->expire('events:'.$channel, 24*60*60);

            return ['success' => true, 'timestamp' => time()];
        } catch (RedisException $e) {
            error_log('EventBus publish failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get all messages from a channel.
     */
    public function getMessages(string $channel): array
    {
        try {
            $length = (int) $this->redis->llen('events:'.$channel);

            if (0 === $length) {
                return [];
            }

            $messages = [];
            for ($i = 0; $i < $length; $i++) {
                $messages[] = $this->redis->lindex('events:'.$channel, 0, -1);
            }

            // Clear the list after reading
            $this->redis->del('events:'.$channel);

            return $messages;
        } catch (RedisException $e) {
            error_log('EventBus getMessages failed: ' . $e->getMessage());

            return [];
        }
    }
}
