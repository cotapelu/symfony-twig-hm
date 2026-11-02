<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use App\OptimizedSchema\Entity\Trait\TimestampableTrait;
use App\OptimizedSchema\Entity\Trait\StatusAwareTrait;

#[ORM\Entity]
#[ORM\Table(name: 'common.media_files')]
#[ORM\Index(name: 'idx_media_files_folder_id', columns: ['folder_id'])]
#[ORM\Index(name: 'idx_media_files_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_media_files_created_at', columns: ['created_at'])]
class MediaFile
{
    use IdentifiableTrait;
    use TimestampableTrait;
    use StatusAwareTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $folderId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $filename;

    #[ORM\Column(type: 'string', length: 100)]
    private string $mimeType;

    #[ORM\Column(type: 'integer')]
    private int $size;

    #[ORM\Column(type: 'string', length: 500)]
    private string $url;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $filename, string $mimeType, int $size, string $url)
    {
        $this->id = $id;
        $this->filename = $filename;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->url = $url;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }


    public function getFolderId()
    {
        return $this->folderId;
    }

    public function setFolderId(?string $folderId)
    {
        $this->folderId = $folderId;
        $this->updatedAt = new \DateTime();
        return $this;
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

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType)
    {
        $this->mimeType = $mimeType;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setSize(int $size)
    {
        $this->size = $size;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
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

    public function isImage()
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    public function isVideo()
    {
        return str_starts_with($this->mimeType, 'video/');
    }

    public function isAudio()
    {
        return str_starts_with($this->mimeType, 'audio/');
    }

    public function isDocument()
    {
        return str_starts_with($this->mimeType, 'application/');
    }
}
