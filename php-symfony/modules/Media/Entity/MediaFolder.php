<?php

namespace App\Media\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'media_folders')]
#[Module(name: 'Media', dependencies: ['User', 'Media'])]
class MediaFolder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $parentId = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function setId(?int $id) { $this->id = $id; return $this; }
    public function getUserId() { return $this->userId; }
    public function setUserId(int $userId) { $this->userId = $userId; return $this; }
    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getParentId() { return $this->parentId; }
    public function setParentId(?int $id) { $this->parentId = $id; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt(\DateTime $dt) { $this->createdAt = $dt; return $this; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTime $dt) { $this->updatedAt = $dt; return $this; }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'name' => $this->getName(),
            'parent_id' => $this->getParentId(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()?->format('c'),
        ];
    }
}