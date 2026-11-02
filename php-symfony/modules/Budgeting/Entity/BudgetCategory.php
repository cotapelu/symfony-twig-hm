<?php

declare(strict_types=1);

namespace App\Budgeting\Entity;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Budget category entity for tracking income/expense/saving categories.
 */
class BudgetCategory
{
    private ?int $id = null;
    private string $name;
    private string $type; // 'income', 'expense', 'saving'
    private ?string $description = null;
    private string $color;
    private int $userId;
    private ?int $parentId = null;
    private bool $isDefault = false;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        string $name,
        string $type,
        string $color,
        int $userId,
        ?string $description = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->color = $color;
        $this->userId = $userId;
        $this->description = $description;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getParentId()
    {
        return $this->parentId;
    }

    public function isDefault()
    {
        return $this->isDefault;
    }

    public function getCreatedAt()
    {
        return \DateTime::createFromInterface($this->createdAt);
    }

    public function getUpdatedAt()
    {
        return \DateTime::createFromInterface($this->updatedAt);
    }

    public function getDeletedAt()
    {
        return $this->deletedAt ? \DateTime::createFromInterface($this->deletedAt) : null;
    }

    // Setters (fluent interface)
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function setColor(string $color)
    {
        $this->color = $color;
        return $this;
    }

    public function setParentId(?int $parentId)
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function setDefault(bool $isDefault)
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt)
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function restore()
    {
        $this->deletedAt = null;
        return $this;
    }

    // For testing purposes
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }
}
