<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use App\OptimizedSchema\Entity\Trait\TimestampableTrait;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'common.media_folders')]
#[ORM\Index(name: 'idx_media_folders_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_media_folders_parent_id', columns: ['parent_id'])]
class MediaFolder
{
    use IdentifiableTrait;
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $parentId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(?string $userId)
    {
        $this->userId = $userId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getParentId()
    {
        return $this->parentId;
    }

    public function setParentId(?string $parentId)
    {
        $this->parentId = $parentId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath(?string $path)
    {
        $this->path = $path;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isRoot()
    {
        return $this->parentId === null;
    }

    public function hasParent()
    {
        return $this->parentId !== null;
    }
}
