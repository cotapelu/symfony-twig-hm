<?php

declare(strict_types=1);

namespace App\Budgeting\Entity;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Budget entity for tracking spending limits per category.
 */
class Budget
{
    private ?int $id = null;
    private int $userId;
    private int $categoryId;
    private float $amount;
    private string $period; // 'daily', 'weekly', 'monthly', 'yearly'
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;
    private ?string $note = null;
    private bool $isActive = true;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        int $userId,
        int $categoryId,
        float $amount,
        string $period,
        DateTimeInterface $startDate
    ) {
        $this->userId = $userId;
        $this->categoryId = $categoryId;
        $this->amount = $amount;
        $this->period = $period;
        $this->startDate = DateTimeImmutable::createFromInterface($startDate);
        $this->calculateEndDate();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    private function calculateEndDate()
    {
        $end = clone $this->startDate;
        switch ($this->period) {
            case 'daily':
                $end = $end->modify('+1 day');
                break;
            case 'weekly':
                $end = $end->modify('+1 week');
                break;
            case 'monthly':
                $end = $end->modify('+1 month');
                break;
            case 'yearly':
                $end = $end->modify('+1 year');
                break;
        }
        $this->endDate = $end;
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

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getPeriod()
    {
        return $this->period;
    }

    public function getStartDate()
    {
        return \DateTime::createFromInterface($this->startDate);
    }

    public function getEndDate()
    {
        return \DateTime::createFromInterface($this->endDate);
    }

    public function getNote()
    {
        return $this->note;
    }

    public function isActive()
    {
        return $this->isActive;
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
    public function setAmount(float $amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function setPeriod(string $period)
    {
        $this->period = $period;
        $this->calculateEndDate();
        return $this;
    }

    public function setNote(?string $note)
    {
        $this->note = $note;
        return $this;
    }

    public function setActive(bool $isActive)
    {
        $this->isActive = $isActive;
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
