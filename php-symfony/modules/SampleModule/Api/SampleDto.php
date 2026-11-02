<?php

declare(strict_types=1);

namespace App\SampleModule\Api\Dto;

/**
 * Sample Data Transfer Object for API layer
 * Used to transfer data between client and server
 */
class SampleDto
{
    private string $name;
    private int $value;
    private ?string $description;

    public function __construct(string $name, int $value, ?string $description = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}