<?php

namespace App\Poll\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'poll_votes')]
#[ORM\Index(columns: ['poll_id', 'user_id'], name: 'poll_user_idx')]
#[Module(name: 'Poll', dependencies: ['User', 'Poll'])]
class PollVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'poll_id', type: 'integer')]
    private int $pollId;

    #[ORM\Column(name: 'user_id', type: 'string', length: 100)]
    private string $userId;

    #[ORM\Column(type: 'json')]
    private array $selectedOptions;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $votedAt;

    public function __construct(
        int $pollId,
        string $userId,
        array $optionIndices,
        $id = null
    ) {
        $this->id = is_int($id) ? $id : null; // allow string only for tests
        $this->pollId = $pollId;
        $this->userId = $userId;
        $this->selectedOptions = $optionIndices;
        $this->votedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function setId(?int $id) { $this->id = $id; return $this; }
    public function getPollId() { return $this->pollId; }
    public function getUserId() { return $this->userId; }
    public function getSelectedOptions() { return $this->selectedOptions; }
    public function getOptionIndices() { return $this->selectedOptions; }
    public function getVotedAt() { return $this->votedAt; }
}
