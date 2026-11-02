<?php

declare(strict_types=1);

namespace App\Utility\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'poll_surveys')]
#[ORM\HasLifecycleCallbacks]
class PollSurvey
{
    public const TYPE_POLL = 'POLL';
    public const TYPE_SURVEY = 'SURVEY';
    public const TYPE_QUIZ = 'QUIZ';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_CLOSED = 'closed';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $question;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $options = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $expiresAt = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $type, string $question)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->type = $type;
        $this->question = $question;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
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

    public function getQuestion()
    {
        return $this->question;
    }

    public function setQuestion(string $question)
    {
        $this->question = $question;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(?array $options)
    {
        $this->options = $options;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData(?array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTime $expiresAt)
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired()
    {
        return $this->expiresAt !== null && new \DateTime() > $this->expiresAt;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->user?->getId(),
            'type' => $this->type,
            'question' => $this->question,
            'options' => $this->options,
            'data' => $this->data,
            'status' => $this->status,
            'expiresAt' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
