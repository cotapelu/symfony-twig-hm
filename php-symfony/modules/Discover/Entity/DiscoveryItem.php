<?php

namespace App\Discover\Entity;

use App\Attribute\Module;

#[Module(name: 'Discover', dependencies: ['User', 'Location'])]
class DiscoveryItem
{
    private ?int $id = null;
    private int $userId;
    private string $type; // place, event, offer, user
    private string $title;
    private ?string $description = null;
    private ?string $imageUrl = null;
    private ?float $latitude = null;
    private ?float $longitude = null;
    private ?string $address = null;
    private array $tags = [];
    private int $score;
    private \DateTime $createdAt;

    public function __construct(int $userId, string $type, string $title)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->score = 0;
        $this->createdAt = new \DateTime();
    }

    // Getters and setters
    public function getId() { return $this->id; }
    public function setId(int $id) { $this->id = $id; return $this; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getImageUrl() { return $this->imageUrl; }
    public function getLatitude() { return $this->latitude; }
    public function getLongitude() { return $this->longitude; }
    public function getAddress() { return $this->address; }
    public function getTags() { return $this->tags; }
    public function getScore() { return $this->score; }

    public function setDescription(?string $desc) { $this->description = $desc; return $this; }
    public function setImageUrl(?string $url) { $this->imageUrl = $url; return $this; }
    public function setLocation(float $lat, float $lng) { $this->latitude = $lat; $this->longitude = $lng; return $this; }
    public function setAddress(?string $addr) { $this->address = $addr; return $this; }
    public function setTags(array $tags) { $this->tags = $tags; return $this; }
    public function incrementScore() { $this->score++; return $this; }
}
