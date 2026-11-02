<?php

declare(strict_types=1);

namespace App\Government\Domain\ValueObject;

use Ramsey\Uuid\Uuid;

class DocumentId
{
    private string $id;
    
    public function __construct(?string $id = null)
    {
        $this->id = $id ?? Uuid::uuid4()->toString();
    }
    
    public function toString(): string
    {
        return $this->id;
    }
    
    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
    
    public function __toString(): string
    {
        return $this->id;
    }
}
