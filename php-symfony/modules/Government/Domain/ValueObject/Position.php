<?php

declare(strict_types=1);

namespace App\Government\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final class Position
{
    #[ORM\Column(type: 'string', length: 50)]
    private string $code;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $level;

    public function __construct(string $code, string $name, int $level = 0)
    {
        if (empty($code) || empty($name)) {
            throw new InvalidArgumentException('Position code and name cannot be empty');
        }
        $this->code = $code;
        $this->name = $name;
        $this->level = $level;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function equals(Position $other): bool
    {
        return $this->code === $other->code;
    }
}
