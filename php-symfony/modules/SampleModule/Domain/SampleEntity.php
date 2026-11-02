<?php

declare(strict_types=1);

namespace App\SampleModule\Domain;

/**
 * Sample Entity representing a domain object
 * Contains business rules and state
 */
class SampleEntity
{
    private int $id;
    private string $name;
    private int $value;
    private ?string $description;

    public function __construct(string $name, int $value, ?string $description = null, ?int $id = null)
    {
        // Business rules validation
        if (empty($name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }
        
        if ($value < 0) {
            throw new \InvalidArgumentException('Value must be non-negative');
        }

        $this->id = $id ?? 0; // 0 indicates new entity
        $this->name = $name;
        $this->value = $value;
        $this->description = $description;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function update(string $name, int $value, ?string $description = null): void
    {
        // Business rules for update
        if (empty($name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }
        
        if ($value < 0) {
            throw new \InvalidArgumentException('Value must be non-negative');
        }

        $this->name = $name;
        $this->value = $value;
        $this->description = $description;
    }
}