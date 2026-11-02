<?php

declare(strict_types=1);

namespace App\Call\Entity;

use App\Call\Repository\CallRepository;
use App\User\Entity\User;
use App\Conversation\Entity\Group;
use App\Shared\Service\UuidService;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=CallRepository::class)
 *
 * @ORM\Table(name="calls")
 */
class Call
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     *
     * @Groups({"call:read"})
     */
    /** @phpstan-ignore-next-line */
    private int $id;

    /**
     * @ORM\Column(type="string", length=36)
     *
     * @Groups({"call:read"})
     */
    private string $callId;

    /**
     * @ORM\ManyToOne(targetEntity=\App\User\Entity\User::class)
     *
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"call:read"})
     */
    private User $initiator;

    /**
     * @ORM\ManyToOne(targetEntity=\App\User\Entity\User::class, nullable=true)
     *
     * @ORM\JoinColumn(nullable=true)
     *
     * @Groups({"call:read"})
     */
    private ?User $recipient;

    /**
     * @ORM\ManyToOne(targetEntity=\App\Conversation\Entity\Group::class, nullable=true)
     *
     * @ORM\JoinColumn(nullable=true)
     *
     * @Groups({"call:read"})
     */
    private ?Group $group;

    /**
     * @ORM\Column(type="string", length=20)
     *
     * @Groups({"call:read"})
     */
    private string $type; // 'audio', 'video'

    /**
     * @ORM\Column(type="string", length=20)
     *
     * @Groups({"call:read"})
     */
    private string $status; // 'ringing', 'connected', 'ended', 'missed'

    /**
     * @ORM\Column(type="datetime")
     *
     * @Groups({"call:read"})
     */
    private \DateTimeInterface $startedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Groups({"call:read"})
     */
    private ?\DateTimeInterface $endedAt = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Groups({"call:read"})
     */
    private ?int $duration = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $participants;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $recordingUrl = null;

    private UuidService $uuidService;

    public function __construct(?UuidService $uuidService = null)
    {
        $this->uuidService = $uuidService ?? new UuidService();
        $this->callId = $this->uuidService->generateWithPrefix('call_');
        $this->status = 'ringing';
        $this->startedAt = new \DateTime();
        $this->participants = [];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCallId()
    {
        return $this->callId;
    }

    public function setCallId(string $callId)
    {
        $this->callId = $callId;

        return $this;
    }

    public function getInitiator()
    {
        return $this->initiator;
    }

    public function setInitiator(User $initiator)
    {
        $this->initiator = $initiator;

        return $this;
    }

    public function getRecipient()
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup(?Group $group)
    {
        $this->group = $group;

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

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;

        return $this;
    }

    public function getStartedAt()
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeInterface $startedAt)
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt()
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeInterface $endedAt)
    {
        $this->endedAt = $endedAt;
        if ($endedAt) {
            $this->duration = $endedAt->getTimestamp() - $this->startedAt->getTimestamp();
        }

        return $this;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration(?int $duration)
    {
        $this->duration = $duration;

        return $this;
    }

    public function getParticipants()
    {
        return $this->participants;
    }

    public function setParticipants(?array $participants)
    {
        $this->participants = $participants;

        return $this;
    }

    public function getRecordingUrl()
    {
        return $this->recordingUrl;
    }

    public function setRecordingUrl(?string $recordingUrl)
    {
        $this->recordingUrl = $recordingUrl;

        return $this;
    }

    public function isActive()
    {
        return in_array($this->status, ['ringing', 'connected']);
    }

    public function endCall()
    {
        $this->status = 'ended';
        $this->endedAt = new \DateTime();
        $this->duration = $this->endedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }

    public function addParticipant(string $userId)
    {
        if (!in_array($userId, $this->participants)) {
            $this->participants[] = $userId;
        }
    }

    public function removeParticipant(string $userId)
    {
        $this->participants = array_filter($this->participants, fn ($id) => $id !== $userId);
    }
}
