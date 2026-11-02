<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity]
#[ORM\Table(name: 'common.feature_flags')]
#[ORM\Index(name: 'idx_feature_flags_key', columns: ['key'])]
#[ORM\Index(name: 'idx_feature_flags_environment', columns: ['environment'])]
#[ORM\UniqueConstraint(name: 'uq_feature_flag_key_environment', columns: ['key', 'environment'])]
class FeatureFlag
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $key;

    #[ORM\Column(type: 'string', length: 50)]
    private string $environment;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $rules = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $key, string $environment)
    {
        $this->id = $id;
        $this->key = $key;
        $this->environment = $environment;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setKey(string $key)
    {
        $this->key = $key;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function setEnvironment(string $environment)
    {
        $this->environment = $environment;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function setRules(?array $rules)
    {
        $this->rules = $rules;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
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

    public function isActive()
    {
        return $this->status === 'ACTIVE' && $this->enabled;
    }

    public function isDisabled()
    {
        return $this->status === 'DISABLED' || !$this->enabled;
    }

    public function isArchived()
    {
        return $this->status === 'ARCHIVED';
    }
}
