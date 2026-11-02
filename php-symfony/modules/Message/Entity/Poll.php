<?php

declare(strict_types=1);

namespace App\Message\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Doctrine entity for Message Poll.
 */
#[ORM\Entity]
#[ORM\Table(name: 'communication_polls')]
class Poll
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $messageId;

    #[ORM\Column(type: 'string', length: 500)]
    private string $question;

    #[ORM\Column(type: 'json')]
    private array $options;

    #[ORM\Column(type: 'json')]
    private array $votes;

    #[ORM\Column(type: 'string', length: 36)]
    private string $creatorId;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: true)]
    private ?\DateTime $expiresAt;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive;

    public function __construct(
        string $id,
        string $messageId,
        string $question,
        array $options,
        string $creatorId,
        ?\DateTime $expiresAt = null,
    ) {
        $this->id = $id;
        $this->messageId = $messageId;
        $this->question = $question;
        $this->options = $options;
        $this->creatorId = $creatorId;
        $this->votes = [];
        $this->createdAt = new \DateTime();
        $this->expiresAt = $expiresAt;
        $this->isActive = true;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getMessageId()
    {
        return $this->messageId;
    }

    public function getQuestion()
    {
        return $this->question;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getVotes()
    {
        return $this->votes;
    }

    public function getCreatorId()
    {
        return $this->creatorId;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function isExpired()
    {
        return $this->expiresAt && $this->expiresAt < new \DateTime();
    }

    // Setters
    public function setQuestion(string $question)
    {
        $this->question = $question;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function addVote(string $userId, string $optionId)
    {
        if (!isset($this->votes[$userId])) {
            $this->votes[$userId] = $optionId;
        }
    }

    public function removeVote(string $userId)
    {
        unset($this->votes[$userId]);
    }

    public function getVoteCounts()
    {
        $counts = [];
        foreach ($this->votes as $userId => $optionId) {
            if (!isset($counts[$optionId])) {
                $counts[$optionId] = 0;
            }
            ++$counts[$optionId];
        }

        return $counts;
    }

    public function getTotalVotes()
    {
        return count($this->votes);
    }

    public function hasUserVoted(string $userId)
    {
        return isset($this->votes[$userId]);
    }

    public function getUserVote(string $userId)
    {
        return $this->votes[$userId] ?? null;
    }

    public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;
    }

    public function setExpiresAt(?\DateTime $expiresAt)
    {
        $this->expiresAt = $expiresAt;
    }
}
