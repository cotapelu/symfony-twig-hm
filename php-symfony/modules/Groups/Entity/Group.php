<?php

namespace App\Groups\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'groups')]
#[ORM\Index(columns: ['is_active'], name: 'idx_groups_active')]
#[ORM\Index(columns: ['is_public'], name: 'idx_groups_public')]
class Group
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $ownerId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isCommunity = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isChannel = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isForum = false;

    #[ORM\Column(type: 'integer')]
    private int $maxMembers = 500;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: GroupMember::class, cascade: ['persist', 'remove'])]
    private Collection $members;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $settings = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(
        string $name,
        string $ownerId,
        ?string $description = null,
        ?string $avatar = null,
        bool $isPublic = false,
        bool $isCommunity = false,
        bool $isChannel = false,
        bool $isForum = false,
        int $maxMembers = 500,
        ?array $settings = null
    ) {
        $this->id = $this->generateId();
        $this->ownerId = $ownerId;
        $this->name = $name;
        $this->description = $description;
        $this->avatar = $avatar;
        $this->isPublic = $isPublic;
        $this->isCommunity = $isCommunity;
        $this->isChannel = $isChannel;
        $this->isForum = $isForum;
        $this->maxMembers = $maxMembers;
        $this->settings = $settings ?? [
            'allowInvites' => true,
            'requireApproval' => false,
            'allowMessages' => true,
            'moderateContent' => true,
        ];
        $this->members = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    private function generateId()
    {
        return bin2hex(random_bytes(16));
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function setOwnerId(string $ownerId)
    {
        $this->ownerId = $ownerId;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $name = trim($name);
        if (empty($name)) {
            throw new \InvalidArgumentException('Group name cannot be empty');
        }
        if (strlen($name) > 255) {
            throw new \InvalidArgumentException('Group name cannot exceed 255 characters');
        }
        $this->name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        if ($description !== null) {
            $description = trim($description);
            if (strlen($description) > 65535) {
                throw new \InvalidArgumentException('Description cannot exceed 65535 characters');
            }
            $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        }
        $this->description = $description;
        return $this;
    }

    public function getAvatar()
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar)
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function isPublic()
    {
        return $this->isPublic;
    }

    public function setPublic(bool $isPublic)
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function isCommunity()
    {
        return $this->isCommunity;
    }

    public function setCommunity(bool $isCommunity)
    {
        $this->isCommunity = $isCommunity;
        return $this;
    }

    public function isChannel()
    {
        return $this->isChannel;
    }

    public function setChannel(bool $isChannel)
    {
        $this->isChannel = $isChannel;
        return $this;
    }

    public function isForum()
    {
        return $this->isForum;
    }

    public function setForum(bool $isForum)
    {
        $this->isForum = $isForum;
        return $this;
    }

    public function getMaxMembers()
    {
        return $this->maxMembers;
    }

    public function setMaxMembers(int $maxMembers)
    {
        if ($maxMembers < 1) {
            throw new \InvalidArgumentException('maxMembers must be at least 1');
        }
        $this->maxMembers = $maxMembers;
        return $this;
    }

    public function getMembers()
    {
        return $this->members;
    }

    public function addMember(GroupMember $member)
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setGroup($this);
        }
        return $this;
    }

    public function removeMember(GroupMember $member)
    {
        if ($this->members->removeElement($member)) {
            $currentGroup = $member->getGroup();
            if ($currentGroup !== null && $currentGroup->getId() === $this->id) {
                $member->setGroup(null);
            }
        }
        return $this;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function setSettings(?array $settings)
    {
        $this->settings = $settings;
        return $this;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getMemberCount()
    {
        return $this->members->count();
    }

    public function isFull()
    {
        return $this->getMemberCount() >= $this->maxMembers;
    }

    public function canJoin()
    {
        return $this->isActive && !$this->isFull();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'avatar' => $this->avatar,
            'isPublic' => $this->isPublic,
            'isCommunity' => $this->isCommunity,
            'isChannel' => $this->isChannel,
            'isForum' => $this->isForum,
            'maxMembers' => $this->maxMembers,
            'memberCount' => $this->getMemberCount(),
            'settings' => $this->settings,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}