<?php

declare(strict_types=1);

namespace App\MiniPrograms\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'mini_programs')]
#[ORM\Index(name: 'idx_app_id', columns: ['app_id'])]
class MiniProgram
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $appId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $iconUrl = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $coverUrl = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $developerId;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $launchUrl = null;

    #[ORM\Column(type: 'integer')]
    private int $installCount = 0;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $version = '1.0.0';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $config = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = (string) \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId() { return $this->id; }

    public function getAppId() { return $this->appId; }
    public function setAppId(string $appId)
    {
        if (empty(trim($appId))) {
            throw new \InvalidArgumentException('App ID cannot be empty');
        }
        $this->appId = trim($appId);
        return $this;
    }

    public function getName() { return $this->name; }
    public function setName(string $name)
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }
        if (mb_strlen($name) > 255) {
            throw new \InvalidArgumentException('Name exceeds maximum length of 255');
        }
        $this->name = trim($name);
        return $this;
    }

    public function getDescription() { return $this->description; }
    public function setDescription(?string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function getIconUrl() { return $this->iconUrl; }
    public function setIconUrl(?string $iconUrl) { $this->iconUrl = $iconUrl; return $this; }

    public function getCoverUrl() { return $this->coverUrl; }
    public function setCoverUrl(?string $coverUrl) { $this->coverUrl = $coverUrl; return $this; }

    public function getDeveloperId() { return $this->developerId; }
    public function setDeveloperId(string $developerId)
    {
        if (empty(trim($developerId))) {
            throw new \InvalidArgumentException('Developer ID cannot be empty');
        }
        $this->developerId = trim($developerId);
        return $this;
    }

    public function getCategory() { return $this->category; }
    public function setCategory(?string $category)
    {
        // Optional: validate against allowed categories if defined
        $this->category = $category;
        return $this;
    }

    public function getLaunchUrl() { return $this->launchUrl; }
    public function setLaunchUrl(?string $launchUrl) { $this->launchUrl = $launchUrl; return $this; }

    public function getInstallCount() { return $this->installCount; }
    public function setInstallCount(int $count)
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Install count cannot be negative');
        }
        $this->installCount = $count;
        return $this;
    }
    public function incrementInstallCount()
    {
        $this->installCount++;
        return $this;
    }

    public function getVersion() { return $this->version; }
    public function setVersion(?string $version)
    {
        $this->version = $version;
        return $this;
    }

    public function getConfig() { return $this->config; }
    public function setConfig(?array $config)
    {
        if ($config !== null) {
            if (count($config) > 100) {
                throw new \InvalidArgumentException('Config array too large (max 100 keys)');
            }
            // Check serialized size roughly; can be up to 64KB typical
            $json = json_encode($config);
            if ($json && strlen($json) > 65536) {
                throw new \InvalidArgumentException('Config data too large (max 64KB)');
            }
        }
        $this->config = $config;
        return $this;
    }

    public function isActive() { return $this->isActive; }
    public function setActive(bool $isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt() { return $this->createdAt; }
}
