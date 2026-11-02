<?php

declare(strict_types=1);

namespace App\Loyalty\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'achievements')]
class Achievement
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: LoyaltyProgram::class)]
    #[ORM\JoinColumn(name: 'program_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?LoyaltyProgram $program = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $earnedAt;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(User $user, string $type, string $name)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->user = $user;
        $this->type = $type;
        $this->name = $name;
        $this->earnedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getProgram()
    {
        return $this->program;
    }

    public function setProgram(?LoyaltyProgram $program)
    {
        $this->program = $program;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getEarnedAt()
    {
        return $this->earnedAt;
    }

    public function setEarnedAt(\DateTime $earnedAt)
    {
        $this->earnedAt = $earnedAt;
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->user->getId(),
            'programId' => $this->program?->getId(),
            'type' => $this->type,
            'name' => $this->name,
            'earnedAt' => $this->earnedAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }
}
