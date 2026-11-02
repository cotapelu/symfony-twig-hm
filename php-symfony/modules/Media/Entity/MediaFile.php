<?php

namespace App\Media\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'media_files')]
#[Module(name: 'Media', dependencies: ['User'])]
class MediaFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $filename;

    #[ORM\Column(type: 'string', length: 255)]
    private string $originalName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $mimeType;

    #[ORM\Column(type: 'integer')]
    private int $size;

    #[ORM\Column(type: 'string', length: 50)]
    private string $storageType;

    #[ORM\Column(type: 'string', length: 500)]
    private string $path;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $thumbnailPath = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $folderId = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function setId(?int $id) { $this->id = $id; return $this; }
    public function getUserId() { return $this->userId; }
    public function setUserId(int $userId) { $this->userId = $userId; return $this; }
    public function getFilename() { return $this->filename; }
    public function setFilename(string $filename) { $this->filename = $filename; return $this; }
    public function getOriginalName() { return $this->originalName; }
    public function setOriginalName(string $originalName) { $this->originalName = $originalName; return $this; }
    public function getMimeType() { return $this->mimeType; }
    public function setMimeType(string $mimeType) { $this->mimeType = $mimeType; return $this; }
    public function getSize() { return $this->size; }
    public function setSize(int $size) { $this->size = $size; return $this; }
    public function getStorageType() { return $this->storageType; }
    public function setStorageType(string $storageType) { $this->storageType = $storageType; return $this; }
    public function getPath() { return $this->path; }
    public function setPath(string $path) { $this->path = $path; return $this; }
    public function getThumbnailPath() { return $this->thumbnailPath; }
    public function setThumbnailPath(?string $path) { $this->thumbnailPath = $path; return $this; }
    public function getFolderId() { return $this->folderId; }
    public function setFolderId(?int $id) { $this->folderId = $id; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt(\DateTime $dt) { $this->createdAt = $dt; return $this; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function setUpdatedAt(\DateTime $dt) { $this->updatedAt = $dt; return $this; }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'filename' => $this->getFilename(),
            'original_name' => $this->getOriginalName(),
            'mime_type' => $this->getMimeType(),
            'size' => $this->getSize(),
            'storage_type' => $this->getStorageType(),
            'path' => $this->getPath(),
            'thumbnail_path' => $this->getThumbnailPath(),
            'folder_id' => $this->getFolderId(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()?->format('c'),
        ];
    }
}