<?php

namespace App\LiveSupport\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'live_support_agents')]
class Agent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $agentUserId; // corresponds to User ID

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: 'simple_array', nullable: true)]
    private ?array $skills = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['online', 'offline', 'busy'])]
    private string $status = 'offline';

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    private int $currentLoad = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastHeartbeat = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function __construct(string $agentUserId, string $name, ?array $skills = null)
    {
        $this->agentUserId = trim($agentUserId);
        $this->name = trim($name);
        $this->skills = $skills ? array_map('trim', $skills) : [];
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;

        if (empty($this->agentUserId)) {
            throw new \InvalidArgumentException('Agent user ID cannot be empty');
        }
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Agent name cannot be empty');
        }
    }

    public function getId() { return $this->id; }

    public function getAgentUserId() { return $this->agentUserId; }
    public function setAgentUserId(string $agentUserId)
    {
        $this->agentUserId = trim($agentUserId);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getName() { return $this->name; }
    public function setName(string $name)
    {
        $this->name = trim($name);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getSkills() { return $this->skills; }
    public function setSkills(?array $skills)
    {
        $this->skills = $skills ? array_map('trim', $skills) : [];
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getStatus() { return $this->status; }
    public function setStatus(string $status)
    {
        $allowed = ['online', 'offline', 'busy'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid agent status');
        }
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getCurrentLoad() { return $this->currentLoad; }
    public function setCurrentLoad(int $load)
    {
        if ($load < 0) {
            throw new \InvalidArgumentException('Load cannot be negative');
        }
        $this->currentLoad = $load;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function incrementLoad(int $amount = 1)
    {
        $this->currentLoad += $amount;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getLastHeartbeat() { return $this->lastHeartbeat; }
    public function setLastHeartbeat(?\DateTimeImmutable $time)
    {
        $this->lastHeartbeat = $time;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getVersion() { return $this->version; }
}
