<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\Poll;
use App\Message\Repository\PollRepositoryInterface;

/**
 * Message Polls Service
 * Handles poll creation, voting, and analytics for messages.
 */
class MessagePollsService
{
    public function __construct(
        private PollRepositoryInterface $pollRepository,
    ) {
    }

    /**
     * Create a new poll for a message.
     */
    public function createPoll(
        string $messageId, string $question, array $options, string $creatorId,
        ?\DateTime $expiresAt = null,
    ) {
        $this->validatePollCreation($messageId, $question, $options);
        $validatedOptions = $this->buildValidatedOptions($options);
        $pollId = uniqid('poll_', true);
        $poll = new Poll($pollId, $messageId, $question, $validatedOptions, $creatorId, $expiresAt);
        $this->pollRepository->save($poll);
        return $poll;
    }

    /**
     * Vote on a poll option.
     */
    public function voteOnPoll(string $pollId, string $userId, string $optionId): array
    {
        $poll = $this->pollRepository->findById($pollId);
        if (!$poll) throw new \InvalidArgumentException('Poll not found');
        if (!$poll->isActive()) throw new \InvalidArgumentException('Poll is no longer active');
        if ($poll->isExpired()) throw new \InvalidArgumentException('Poll has expired');
        if ($poll->hasUserVoted($userId)) throw new \InvalidArgumentException('User has already voted');
        foreach ($poll->getOptions() as $opt) {
            if ($opt['id'] === $optionId) {
                $poll->addVote($userId, $optionId);
                $this->pollRepository->save($poll);
                return ['poll'=>$poll, 'voteCounts'=>$poll->getVoteCounts(), 'totalVotes'=>$poll->getTotalVotes(), 'userVote'=>$optionId];
            }
        }
        throw new \InvalidArgumentException('Invalid option');
    }

    /**
     * End a poll early.
     */
    public function endPoll(string $pollId, string $userId)
    {
        $poll = $this->pollRepository->findById($pollId);
        if (!$poll || $poll->getCreatorId() !== $userId) {
            return false;
        }

        $poll->setIsActive(false);
        $this->pollRepository->save($poll);

        return true;
    }

    /**
     * Get poll by ID.
     */
    public function getPoll(string $pollId)
    {
        return $this->pollRepository->findById($pollId);
    }

    /**
     * Get poll by message ID.
     */
    public function getPollByMessageId(string $messageId)
    {
        return $this->pollRepository->findByMessageId($messageId);
    }

    /**
     * Get poll results for display.
     */
    public function getPollResults(string $pollId, ?string $userId = null): array
    {
        $poll = $this->pollRepository->findById($pollId);
        if (!$poll) throw new \InvalidArgumentException('Poll not found');
        $voteCounts = $poll->getVoteCounts();
        $totalVotes = $poll->getTotalVotes();
        $options = $this->buildOptionsData($poll->getOptions(), $voteCounts, $totalVotes);
        $result = [
            'id' => $poll->getId(),
            'question' => $poll->getQuestion(),
            'options' => $options,
            'totalVotes' => $totalVotes,
            'isActive' => $poll->isActive(),
            'isExpired' => $poll->isExpired(),
            'expiresAt' => $poll->getExpiresAt()?->format('c'),
            'createdAt' => $poll->getCreatedAt()->format('c'),
        ];
        if ($userId) $result['userVote'] = $poll->getUserVote($userId);
        return $result;
    }

    private function validatePollCreation(string $messageId, string $question, array $options): void
    {
        if (empty($messageId) || empty($question) || empty($options) || count($options) < 2) {
            throw new \InvalidArgumentException('Poll must have valid message ID, question, and at least 2 options');
        }
        if (strlen($question) > 200) {
            throw new \InvalidArgumentException('Poll question cannot exceed 200 characters');
        }
    }

    private function buildValidatedOptions(array $options): array
    {
        $validated = [];
        foreach ($options as $option) {
            if (!is_string($option) || empty(trim($option)) || strlen($option) > 100) {
                throw new \InvalidArgumentException('Poll options must be non-empty strings under 100 characters');
            }
            $validated[] = ['id' => uniqid('opt_'), 'text' => trim($option)];
        }
        return $validated;
    }

    private function buildOptionsData(array $options, array $voteCounts, int $totalVotes): array
    {
        return array_map(function ($option) use ($voteCounts, $totalVotes) {
            $votes = $voteCounts[$option['id']] ?? 0;
            return [
                'id' => $option['id'],
                'text' => $option['text'],
                'votes' => $votes,
                'percentage' => $totalVotes > 0 ? round(($votes / $totalVotes) * 100, 1) : 0,
            ];
        }, $options);
    }
}
