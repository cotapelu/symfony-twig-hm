<?php

declare(strict_types=1);

namespace App\Government\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final class Department
{
    #[ORM\Column(type: 'string', length: 50)]
    private string $code;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $parentCode;

    public function __construct(string $code, string $name, ?string $parentCode = null)
    {
        if (empty($code) || empty($name)) {
            throw new InvalidArgumentException('Department code and name cannot be empty');
        }
        $this->code = $code;
        $this->name = $name;
        $this->parentCode = $parentCode;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentCode(): ?string
    {
        return $this->parentCode;
    }

    public function equals(Department $other): bool
    {
        return $this->code === $other->code;
    }
}
