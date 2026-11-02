<?php

namespace App\Subscription\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Subscription\Repository\SubscriptionPlanRepository::class)]
#[ORM\Table(name: 'subscription_plans')]
#[ORM\Index(columns: ['is_active'], name: 'idx_plan_active')]
class SubscriptionPlan
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'USD';

    #[ORM\Column(type: 'integer')]
    private int $durationDays = 30;

    #[ORM\Column(name: 'is_active', type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer')]
    private int $maxMembers = 100;

    #[ORM\Column(type: 'integer')]
    private int $maxFileSize = 100;

    #[ORM\Column(type: 'integer')]
    private int $maxMessages = 10000;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $features;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(
        string $id,
        string $name,
        string $price,
        ?string $description = null,
        int $durationDays = 30,
        int $maxMembers = 100,
        int $maxFileSize = 100,
        int $maxMessages = 10000,
        ?array $features = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->durationDays = $durationDays;
        $this->maxMembers = $maxMembers;
        $this->maxFileSize = $maxFileSize;
        $this->maxMessages = $maxMessages;
        $this->features = $features;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice(string $price)
    {
        $this->price = $price;
        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
        return $this;
    }

    public function getDurationDays()
    {
        return $this->durationDays;
    }

    public function setDurationDays(int $durationDays)
    {
        $this->durationDays = $durationDays;
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

    public function getMaxMembers(): int
    {
        return $this->maxMembers;
    }

    public function setMaxMembers(int $maxMembers): self
    {
        $this->maxMembers = $maxMembers;
        return $this;
    }

    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    public function setMaxFileSize(int $maxFileSize): self
    {
        $this->maxFileSize = $maxFileSize;
        return $this;
    }

    public function getMaxMessages(): int
    {
        return $this->maxMessages;
    }

    public function setMaxMessages(int $maxMessages): self
    {
        $this->maxMessages = $maxMessages;
        return $this;
    }

    public function getFeatures()
    {
        return $this->features;
    }

    public function setFeatures(?array $features)
    {
        $this->features = $features;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'durationDays' => $this->durationDays,
            'maxMembers' => $this->maxMembers,
            'maxFileSize' => $this->maxFileSize,
            'maxMessages' => $this->maxMessages,
            'features' => $this->features,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}