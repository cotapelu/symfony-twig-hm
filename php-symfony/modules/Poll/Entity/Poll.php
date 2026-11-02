<?php

namespace App\Poll\Entity;

use App\Attribute\Module;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'polls')]
#[Module(name: 'Poll', dependencies: ['User'])]
class Poll
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'created_by', type: 'integer')]
    private int $creatorId;

    #[ORM\Column(type: 'string', length: 500)]
    private string $question;

    #[ORM\Column(type: 'json')]
    private array $options = []; // ['text'=>string, 'votes'=>int]

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'allow_multiple', type: 'boolean')]
    private bool $allowMultiple = false;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    // Computed total votes (not persisted but maintained)
    private int $totalVotes = 0;

    // Integration test fields (non-persisted)
    private ?string $conversationId = null;
    private ?string $messageId = null;
    private bool $isAnonymous = false;

    // Constructor for service use: creator ID first
    public function __construct(
        int $creatorId, string $question, array $options = [], ?string $description = null,
        bool $allowMultiple = false, ?DateTimeInterface $expiresAt = null, ?int $id = null,
        ?string $conversationId = null, ?string $messageId = null, bool $isAnonymous = false
    ) {
        $this->id = $id;
        $this->creatorId = $creatorId;
        $this->question = $question;
        $this->initializeOptions($options);
        $this->description = $description;
        $this->allowMultiple = $allowMultiple;
        $this->expiresAt = $this->normalizeExpiresAt($expiresAt);
        $this->conversationId = $conversationId;
        $this->messageId = $messageId;
        $this->isAnonymous = $isAnonymous;
        $this->totalVotes = $this->calculateTotalVotes();
    }

    // Getters and setters
    public function getId() { return $this->id; }
    public function setId(?int $id) { $this->id = $id; return $this; }
    public function getCreatorId() { return $this->creatorId; }
    public function setCreatorId(int $creatorId) { $this->creatorId = $creatorId; return $this; }
    public function getQuestion() { return $this->question; }
    public function setQuestion(string $question) { $this->question = $question; return $this; }
    public function getOptions() { return $this->options; }
    public function setOptions(array $options) { $this->options = []; foreach ($options as $opt) { $this->addOption($opt); } return $this; }
    public function getDescription() { return $this->description; }
    public function setDescription(?string $desc) { $this->description = $desc; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getAllowMultiple() { return $this->allowMultiple; }
    public function setAllowMultiple(bool $allow) { $this->allowMultiple = $allow; return $this; }
    public function allowMultiple() { return $this->allowMultiple; }

    // Alias for integration tests
    public function isMultiSelect(): bool
    {
        return $this->allowMultiple;
    }
    public function getExpiresAt() { return $this->expiresAt; }
    public function setExpiresAt(?\DateTimeImmutable $date) { $this->expiresAt = $date; return $this; }
    public function getTotalVotes() { return $this->totalVotes; }

    // Getter for votes array (used by integration tests)
    public function getVotes(): array
    {
        return array_column($this->options, 'votes');
    }

    public function addOption(string $text)
    {
        $this->options[] = ['text' => $text, 'votes' => 0];
        return $this;
    }

    public function incrementVotes(int $optionIndex)
    {
        if (isset($this->options[$optionIndex])) {
            $this->options[$optionIndex]['votes']++;
            $this->totalVotes++;
        }
    }

    public function vote(int $optionIndex)
    {
        $this->incrementVotes($optionIndex);
    }

    // Extra getters/setters for integration tests
    public function getConversationId() { return $this->conversationId; }
    public function setConversationId(?string $id) { $this->conversationId = $id; return $this; }
    public function getMessageId() { return $this->messageId; }
    public function setMessageId(?string $id) { $this->messageId = $id; return $this; }
    public function isAnonymous() { return $this->isAnonymous; }
    public function setIsAnonymous(bool $flag) { $this->isAnonymous = $flag; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'options' => $this->buildOptionsArray(),
            'created_by' => $this->creatorId,
            'total_votes' => $this->totalVotes,
            'votes' => $this->getVotesArray(),
            'is_multi_select' => $this->allowMultiple,
            'is_anonymous' => $this->isAnonymous,
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'description' => $this->description,
            'status' => $this->status,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'created_at' => null,
        ];
    }

    private function getVotesArray()
    {
        return array_column($this->options, 'votes');
    }

    private function initializeOptions(array $options): void
    {
        foreach ($options as $opt) {
            $this->addOption($opt);
        }
    }

    private function normalizeExpiresAt(?DateTimeInterface $expiresAt): ?\DateTimeImmutable
    {
        if ($expiresAt instanceof \DateTimeImmutable) {
            return $expiresAt;
        } elseif ($expiresAt instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($expiresAt);
        }
        return null;
    }

    private function calculateTotalVotes(): int
    {
        return array_sum(array_column($this->options, 'votes'));
    }

    private function buildOptionsArray(): array
    {
        $opts = [];
        foreach ($this->options as $idx => $opt) {
            $percentage = $this->totalVotes > 0 ? round(($opt['votes'] / $this->totalVotes) * 100, 2) : 0.0;
            $opts[] = [
                'text' => $opt['text'],
                'votes' => $opt['votes'],
                'percentage' => $percentage,
                'index' => $idx,
            ];
        }
        return $opts;
    }
}
