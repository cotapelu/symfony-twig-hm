<?php

namespace App\Education\Entity;

use App\Attribute\Module;

#[Module(name: 'Education', dependencies: ['User'])]
class Course
{
    private ?int $id = null;
    private int $instructorId;
    private string $title;
    private ?string $description = null;
    private string $category;
    private string $level; // beginner, intermediate, advanced
    private int $duration; // minutes
    private float $price;
    private bool $isPublished = false;
    private ?string $thumbnailUrl = null;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(int $instructorId, string $title, string $category, int $duration, float $price)
    {
        $this->instructorId = $instructorId;
        $this->title = $title;
        $this->category = $category;
        $this->level = 'beginner';
        $this->duration = $duration;
        $this->price = $price;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getInstructorId() { return $this->instructorId; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getCategory() { return $this->category; }
    public function getLevel() { return $this->level; }
    public function getDuration() { return $this->duration; }
    public function getPrice() { return $this->price; }
    public function isPublished() { return $this->isPublished; }
    public function getThumbnailUrl() { return $this->thumbnailUrl; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    public function setId(?int $id) { $this->id = $id; return $this; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }
    public function setLevel(string $level) { $this->level = $level; return $this; }
    public function setPublished(bool $published) { $this->isPublished = $published; return $this; }
    public function setDuration(int $duration) { $this->duration = $duration; return $this; }
    public function setPrice(float $price) { $this->price = $price; return $this; }
    public function setThumbnailUrl(?string $thumbnailUrl) { $this->thumbnailUrl = $thumbnailUrl; return $this; }
    public function setLessons(array $lessons) { return $this; }
    public function publish() { $this->isPublished = true; return $this; }
    public function archive() { $this->isPublished = false; return $this; }
    public function getStatus() { return $this->isPublished ? 'published' : 'draft'; }
}
