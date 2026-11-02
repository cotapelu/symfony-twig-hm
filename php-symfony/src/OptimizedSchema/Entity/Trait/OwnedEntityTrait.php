<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity\Trait;

trait OwnedEntityTrait
{
    public function getOwnerId(): string
    {
        return $this->ownerId;
    }
}
