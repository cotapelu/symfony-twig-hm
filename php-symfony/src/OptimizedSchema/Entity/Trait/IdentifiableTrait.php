<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity\Trait;

trait IdentifiableTrait
{
    public function getId(): string
    {
        return $this->id;
    }
}
