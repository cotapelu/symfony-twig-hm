<?php

declare(strict_types=1);

namespace App\Budgeting\Entity;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Budget expense entity for tracking actual spending.
 */
class BudgetExpense
{
    private ?int $id = null;
    private int $userId;
    private int $budgetId;
    private int $categoryId;
    private float $amount;
    private ?string $description = null;
    private ?string $merchant = null;
    private ?int $transactionId = null;
    private DateTimeImmutable $expenseDate;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        int $userId,
        int $budgetId,
        int $categoryId,
        float $amount,
        DateTimeInterface $expenseDate
    ) {
        $this->userId = $userId;
        $this->budgetId = $budgetId;
        $this->categoryId = $categoryId;
        $this->amount = $amount;
        $this->expenseDate = DateTimeImmutable::createFromInterface($expenseDate);
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getBudgetId()
    {
        return $this->budgetId;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getMerchant()
    {
        return $this->merchant;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function getExpenseDate()
    {
        return \DateTime::createFromInterface($this->expenseDate);
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
    public function setDescription(?string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function setMerchant(?string $merchant)
    {
        $this->merchant = $merchant;
        return $this;
    }

    public function setTransactionId(?int $transactionId)
    {
        $this->transactionId = $transactionId;
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
