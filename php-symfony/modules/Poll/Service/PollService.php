<?php

namespace App\Poll\Service;

use App\Poll\Entity\Poll;
use App\Poll\Entity\PollVote;
use DateTimeImmutable;
use Exception;

class PollService
{
    private array $polls = [];
    private array $pollVotes = [];
    private int $nextPollId = 1;
    private int $nextVoteId = 1;

    private const VALIDATE_METHODS = [
        'validateQuestion',
        'validateOptions',
        'validateDescription',
        'validateAllowMultiple',
        'validateExpiresAt',
        'validateStatus',
    ];

    private const APPLY_METHODS = [
        'applyQuestion',
        'applyOptions',
        'applyDescription',
        'applyAllowMultiple',
        'applyExpiresAt',
        'applyStatus',
    ];
    public function createPoll(
        int $creatorId,
        string $question,
        array $options = [],
        ?string $description = null,
        bool $allowMultiple = false,
        ?string $expiresAt = null
    ) {
        $this->ensureQuestion($question);
        $this->ensureOptions($options);
        $this->ensureDescription($description);
        $expiresAtDateTime = $this->ensureExpiresAt($expiresAt);
        $poll = $this->assemblePoll($creatorId, $question, $options, $description, $allowMultiple, $expiresAtDateTime);
        $this->polls[$poll->getId()] = $poll;
        return $poll;
    }

    private function ensureQuestion(string $question): void
    {
        if (trim($question) === '') {
            throw new Exception('Question cannot be empty');
        }
    }

    private function ensureOptions(array $options): void
    {
        if (empty($options)) {
            throw new Exception('At least one option is required');
        }
        foreach ($options as $index => $option) {
            if (!is_string($option) || trim($option) === '') {
                throw new Exception("Option at index $index must be a non-empty string");
            }
            if (strlen(trim($option)) > 255) {
                throw new Exception("Option at index $index is too long (max 255 characters)");
            }
        }
    }

    private function ensureDescription(?string $description): void
    {
        if ($description !== null && strlen($description) > 1000) {
            throw new Exception('Description is too long (max 1000 characters)');
        }
    }

    private function ensureExpiresAt(?string $expiresAt): ?DateTimeImmutable
    {
        if ($expiresAt === null) {
            return null;
        }
        $expiresAtDateTime = new DateTimeImmutable($expiresAt);
        if ($expiresAtDateTime <= new DateTimeImmutable()) {
            throw new Exception('Expiration date must be in the future');
        }
        return $expiresAtDateTime;
    }

    private function assemblePoll(int $creatorId, string $question, array $options, ?string $description, bool $allowMultiple, ?DateTimeImmutable $expiresAtDateTime): Poll
    {
        return new Poll(
            $creatorId,
            $question,
            $options,
            $description,
            $allowMultiple,
            $expiresAtDateTime,
            $this->nextPollId++
        );
    }

    public function getPoll(int $id)
    {
        return $this->polls[$id] ?? null;
    }

    public function getActivePolls()
    {
        $now = new DateTimeImmutable();
        $active = [];
        foreach ($this->polls as $poll) {
            if ($poll->getStatus() === 'active' && ($poll->getExpiresAt() === null || $poll->getExpiresAt() > $now)) {
                $active[] = $poll;
            }
        }
        return $active;
    }

    public function getCreatorPolls(int $creatorId)
    {
        return array_values(array_filter($this->polls, fn($p) => $p->getCreatorId() == $creatorId));
    }

    public function updatePoll(int $id, array $data, int $userId)
    {
        $poll = $this->getPoll($id);
        if (!$poll) {
            return null;
        }
        if ($poll->getCreatorId() !== $userId) {
            throw new Exception('Unauthorized to update this poll');
        }
        if (isset($data['options']) && $poll->getTotalVotes() > 0) {
            throw new Exception('Cannot modify options after voting has started');
        }

        foreach (self::VALIDATE_METHODS as $method) {
            $this->$method($data);
        }

        foreach (self::APPLY_METHODS as $method) {
            $this->$method($poll, $data);
        }

        return $poll;
    }

    private function validateQuestion(array $data): void
    {
        if (isset($data['question']) && trim($data['question']) === '') {
            throw new Exception('Question cannot be empty');
        }
    }

    private function validateOptions(array $data): void
    {
        if (isset($data['options'])) {
            if (empty($data['options'])) {
                throw new Exception('At least one option is required');
            }
            foreach ($data['options'] as $index => $option) {
                if (!is_string($option) || trim($option) === '') {
                    throw new Exception("Option at index $index must be a non-empty string");
                }
                if (strlen(trim($option)) > 255) {
                    throw new Exception("Option at index $index is too long (max 255 characters)");
                }
            }
        }
    }

    private function validateDescription(array $data): void
    {
        if (isset($data['description']) && strlen($data['description']) > 1000) {
            throw new Exception('Description is too long (max 1000 characters)');
        }
    }

    private function validateAllowMultiple(array $data): void
    {
        if (isset($data['allow_multiple'])) {
            // No further validation needed, presence is enough
        }
    }

    private function validateExpiresAt(array $data): void
    {
        if (isset($data['expires_at']) && $data['expires_at'] !== null) {
            $expiresAt = new DateTimeImmutable($data['expires_at']);
            if ($expiresAt <= new DateTimeImmutable()) {
                throw new Exception('Expiration date must be in the future');
            }
        }
    }

    private function validateStatus(array $data): void
    {
        if (isset($data['status'])) {
            // Assuming status validation elsewhere? We'll just accept presence.
        }
    }

    private function applyQuestion($poll, array $data): void
    {
        if (isset($data['question'])) {
            $poll->setQuestion($data['question']);
        }
    }

    private function applyOptions($poll, array $data): void
    {
        if (isset($data['options'])) {
            $poll->setOptions($data['options']);
        }
    }

    private function applyDescription($poll, array $data): void
    {
        if (isset($data['description'])) {
            $poll->setDescription($data['description']);
        }
    }

    private function applyAllowMultiple($poll, array $data): void
    {
        if (isset($data['allow_multiple'])) {
            $poll->setAllowMultiple($data['allow_multiple']);
        }
    }

    private function applyExpiresAt($poll, array $data): void
    {
        if (isset($data['expires_at'])) {
            if ($data['expires_at'] !== null) {
                $expiresAt = new DateTimeImmutable($data['expires_at']);
                $poll->setExpiresAt($expiresAt);
            } else {
                $poll->setExpiresAt(null);
            }
        }
    }

    private function applyStatus($poll, array $data): void
    {
        if (isset($data['status'])) {
            $poll->setStatus($data['status']);
        }
    }

    public function deletePoll(int $id, int $userId)
    {
        $poll = $this->getPoll($id);
        if (!$poll) {
            return false;
        }
        if ($poll->getCreatorId() !== $userId) {
            throw new Exception('Unauthorized to delete this poll');
        }
        unset($this->polls[$id]);
        return true;
    }

    public function vote(int $pollId, string $userId, array $optionIndices)
    {
        $poll = $this->getPoll($pollId);
        if (!$poll || $poll->getStatus() !== 'active') {
            return null;
        }
        if ($poll->getExpiresAt() !== null && $poll->getExpiresAt() <= new DateTimeImmutable()) {
            return null;
        }
        if ($this->hasVoted($pollId, $userId)) {
            return null;
        }
        if (!$this->validateOptionIndices($poll, $optionIndices)) {
            return null;
        }

        foreach ($optionIndices as $idx) {
            $poll->incrementVotes($idx);
        }

        $vote = new PollVote($pollId, $userId, $optionIndices, null);
        $this->assignVoteId($vote);
        $this->pollVotes[] = $vote;
        return $vote;
    }

    private function validateOptionIndices($poll, array $optionIndices): bool
    {
        $options = $poll->getOptions();
        foreach ($optionIndices as $idx) {
            if (!is_int($idx) || $idx < 0 || $idx >= count($options)) {
                return false;
            }
        }
        return true;
    }

    private function assignVoteId(PollVote $vote): void
    {
        $refProp = new \ReflectionProperty($vote, 'id');
        $refProp->setAccessible(true);
        $refProp->setValue($vote, $this->nextVoteId++);
    }

    public function hasVoted(int $pollId, string $userId)
    {
        foreach ($this->pollVotes as $vote) {
            if ($vote->getPollId() == $pollId && $vote->getUserId() === $userId) {
                return true;
            }
        }
        return false;
    }

    public function getUserVote(int $pollId, string $userId)
    {
        foreach ($this->pollVotes as $vote) {
            if ($vote->getPollId() == $pollId && $vote->getUserId() === $userId) {
                return $vote;
            }
        }
        return null;
    }

    public function getPollResults(int $pollId)
    {
        $poll = $this->getPoll($pollId);
        if (!$poll) {
            return null;
        }
        $options = [];
        foreach ($poll->getOptions() as $idx => $opt) {
            $percentage = $poll->getTotalVotes() > 0 ? round(($opt['votes'] / $poll->getTotalVotes()) * 100, 2) : 0.0;
            $options[] = [
                'text' => $opt['text'],
                'votes' => $opt['votes'],
                'percentage' => $percentage,
                'index' => $idx,
            ];
        }
        return [
            'poll' => $poll,
            'total_votes' => $poll->getTotalVotes(),
            'options' => $options,
        ];
    }
}
