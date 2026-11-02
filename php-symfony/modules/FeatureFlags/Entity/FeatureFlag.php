<?php

declare(strict_types=1);

namespace App\FeatureFlags\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'feature_flags')]
#[ORM\Index(name: 'idx_feature_flag_name', columns: ['name'])]
class FeatureFlag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $name, bool $enabled = false, ?string $description = null)
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId() { return $this->id; }

    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }

    public function isEnabled() { return $this->enabled; }
    public function setEnabled(bool $enabled) { $this->enabled = $enabled; return $this; }

    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }

    public function getCreatedAt() { return $this->createdAt; }
}
