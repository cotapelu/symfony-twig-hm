<?php

declare(strict_types=1);

namespace App\Message\Strategy;

/**
 * Factory Pattern: Creates appropriate message processing strategies.
 *
 * This allows adding new message types without modifying existing code
 */
class MessageProcessingStrategyFactory
{
    /** @var MessageProcessingStrategyInterface[] */
    private array $strategies = [];

    public function __construct()
    {
        // Register default strategies
        $this->registerStrategy(new TextMessageStrategy());
        $this->registerStrategy(new MediaMessageStrategy());

        // Future: Load custom strategies from config/plugins
        // $this->loadCustomStrategies();
    }

    /**
     * Register a new strategy.
     */
    public function registerStrategy(MessageProcessingStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getStrategyName()] = $strategy;
    }

    /**
     * Get appropriate strategy for message data.
     */
    public function getStrategy(array $messageData): MessageProcessingStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canHandle($messageData)) {
                return $strategy;
            }
        }

        // Fallback to text strategy
        return $this->strategies['text'] ?? new TextMessageStrategy();
    }

    /**
     * Process message using appropriate strategy.
     */
    public function processMessage(array $messageData): array
    {
        $strategy = $this->getStrategy($messageData);

        if (!$strategy->validateMessage($messageData)) {
            throw new \InvalidArgumentException('Invalid message data for strategy: '.$strategy->getStrategyName());
        }

        return $strategy->processMessage($messageData);
    }

    /**
     * Get all registered strategies.
     */
    public function getAllStrategies(): array
    {
        return $this->strategies;
    }

    /*
     * Example: How to extend with new strategy without modifying existing code
     *
     * public function addPollStrategy(): void
     * {
     *     $this->registerStrategy(new PollMessageStrategy());
     * }
     *
     * public function addVoiceMessageStrategy(): void
     * {
     *     $this->registerStrategy(new VoiceMessageStrategy());
     * }
     */
}
