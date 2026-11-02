<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity\Trait;

trait TimestampableTrait
{
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
}
