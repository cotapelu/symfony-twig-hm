<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Event\ReactionAddedEvent;
use App\Message\Event\ReactionRemovedEvent;
use App\Message\Event\ReactionUpdatedEvent;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\EventBus\EventBus;

/**
 * Use case for adding a reaction to a message.
 */
class AddReactionUseCase
{
    private MessageRepositoryInterface $messageRepository;
    private EventBus $eventBus;

    public function __construct(
        MessageRepositoryInterface $messageRepository,
        EventBus $eventBus,
    ) {
        $this->messageRepository = $messageRepository;
        $this->eventBus = $eventBus;
    }

    /**
     * Execute the add reaction use case.
     */
    public function execute(AddReactionRequest $request)
    {
        $message = $this->findMessage($request->messageId);
        $this->validateEmoji($request->emoji);

        $reactions = $message->getReactions() ?? [];
        $existingIndex = $this->findExistingReactionIndex($reactions, $request->userId);

        [$action, $oldEmoji, $newReaction] = $this->determineAction($existingIndex, $reactions, $request);
        $this->applyReaction($reactions, $existingIndex, $request, $newReaction);
        $message->setReactions($reactions);

        $this->saveMessage($message);
        $this->dispatchEvent($action, $message, $request, $oldEmoji);

        return new AddReactionResponse($message, $action, $oldEmoji);
    }

    private function findMessage(string $messageId)
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            throw new \InvalidArgumentException('Message not found');
        }
        return $message;
    }

    private function validateEmoji(string $emoji): void
    {
        if (!$this->isValidEmoji($emoji)) {
            throw new \InvalidArgumentException('Invalid emoji');
        }
    }

    private function findExistingReactionIndex(array $reactions, string $userId): ?int
    {
        foreach ($reactions as $index => $reaction) {
            if ($reaction['userId'] === $userId) {
                return $index;
            }
        }
        return null;
    }

    private function determineAction(?int $existingIndex, array $reactions, AddReactionRequest $request): array
    {
        if (null === $existingIndex) {
            return ['add', null, null];
        }

        $oldEmoji = $reactions[$existingIndex]['emoji'];
        if ($oldEmoji === $request->emoji) {
            return ['remove', $oldEmoji, null];
        }
        return ['update', $oldEmoji, [
            'userId' => $request->userId,
            'emoji' => $request->emoji,
            'createdAt' => new \DateTime(),
        ]];
    }

    private function applyReaction(array &$reactions, ?int $existingIndex, AddReactionRequest $request, ?array $newReaction): void
    {
        if (null === $existingIndex) {
            $reactions[] = [
                'userId' => $request->userId,
                'emoji' => $request->emoji,
                'createdAt' => new \DateTime(),
            ];
            return;
        }

        if ($newReaction === null) {
            // remove
            unset($reactions[$existingIndex]);
            $reactions = array_values($reactions);
        } else {
            // update
            $reactions[$existingIndex] = $newReaction;
        }
    }

    private function saveMessage($message): void
    {
        try {
            $this->messageRepository->save($message);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to save reaction: '.$e->getMessage());
        }
    }

    private function dispatchEvent($action, $message, AddReactionRequest $request, $oldEmoji): void
    {
        $occurredOn = new \DateTimeImmutable();
        if ('remove' === $action) {
            $this->eventBus->dispatch(new ReactionRemovedEvent($message->getId(), $request->userId, $oldEmoji, $occurredOn));
        } elseif ('update' === $action) {
            $this->eventBus->dispatch(new ReactionUpdatedEvent($message->getId(), $request->userId, $oldEmoji, $request->emoji, $occurredOn));
        } else {
            $this->eventBus->dispatch(new ReactionAddedEvent($message->getId(), $request->userId, $request->emoji, $occurredOn));
        }
    }

    /**
     * Validate emoji is supported.
     */
    private function isValidEmoji(string $emoji)
    {
        // Check if empty
        if (empty(trim($emoji))) {
            return false;
        }

        // Check the length - emojis should not be too long
        if (mb_strlen($emoji, 'UTF-8') > 10) {
            return false;
        }

        // More comprehensive emoji validation using Unicode property escapes
        $emojiPattern = '/^[\p{So}\p{Cf}\x{200D}\x{FE0F}]+$/u';

        return 1 === preg_match($emojiPattern, $emoji);
    }
}

/**
 * Response DTO for AddReactionUseCase.
 */
class AddReactionResponse
{
    public \App\Message\Entity\Message $message;
    public string $action; // 'add', 'remove', 'update'
    public ?string $oldEmoji;

    public function __construct(\App\Message\Entity\Message $message, string $action = 'add', ?string $oldEmoji = null)
    {
        $this->message = $message;
        $this->action = $action;
        $this->oldEmoji = $oldEmoji;
    }
}
