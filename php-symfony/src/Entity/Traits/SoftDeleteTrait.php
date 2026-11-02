<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait SoftDeleteTrait
{
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $deletedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted = false;

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt)
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function isDeleted()
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted)
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }

    public function softDelete()
    {
        $this->deletedAt = new \DateTime();
        $this->isDeleted = true;
        return $this;
    }

    public function restore()
    {
        $this->deletedAt = null;
        $this->isDeleted = false;
        return $this;
    }
}