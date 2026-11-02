<?php

declare(strict_types=1);

namespace App\Conversation\Entity;

use App\User\Entity\User;
use App\Conversation\Entity\ConversationParticipant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=GroupRepository::class)
 *
 * @ORM\Table(name="groups")
 */
class Group
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     *
     * @Groups({"group:read"})
     */
    /** @phpstan-ignore-next-line */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Groups({"group:read"})
     */
    private string $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"group:read"})
     */
    private ?string $description;

     /**
      * @ORM\ManyToOne(targetEntity=\App\User\Entity\User::class)
      *
      * @ORM\JoinColumn(nullable=false)
      *
      * @Groups({"group:read"})
      */
    private User $createdBy;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Groups({"group:read"})
     */
    private \DateTimeInterface $createdAt;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     *
     * @Groups({"group:read"})
     */
    private bool $isActive = true;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @Groups({"group:read"})
     */
    private bool $isPrivate = false;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     *
     * @Groups({"group:read"})
     */
    private int $maxMembers = 0;

     /**
      * @ORM\ManyToMany(targetEntity=\App\User\Entity\User::class)
      *
      * @ORM\JoinTable(name="group_members")
      *
      * @Groups({"group:members"})
      */
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
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

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isPrivate()
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate)
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    public function getMaxMembers()
    {
        return $this->maxMembers;
    }

    public function setMaxMembers(int $maxMembers)
    {
        $this->maxMembers = $maxMembers;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembers()
    {
        return $this->members;
    }

    public function addMember(User $member)
    {
        if (!$this->members->contains($member)) {
            $this->members[] = $member;
        }

        return $this;
    }

    public function removeMember(User $member)
    {
        $this->members->removeElement($member);

        return $this;
    }
}
