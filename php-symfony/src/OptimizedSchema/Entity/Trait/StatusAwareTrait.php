<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity\Trait;

trait StatusAwareTrait
{
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
