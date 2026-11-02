<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait BlameableTrait
{
    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $createdBy = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $updatedBy = null;

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy)
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $updatedBy)
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }
}