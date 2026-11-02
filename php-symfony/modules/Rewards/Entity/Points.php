<?php

declare(strict_types=1);

namespace App\Rewards\Entity;

use App\Attribute\Module;

#[Module(name: 'Rewards', dependencies: ['User'])]
class Points
{
    private ?int $id = null;
    private string $userId;
    private int $balance;
    private int $totalEarned;
    private int $totalWithdrawn;

    public function __construct(string $userId, int $balance = 0, int $totalEarned = 0, int $totalWithdrawn = 0)
    {
        $this->userId = $userId;
        $this->balance = $balance;
        $this->totalEarned = $totalEarned;
        $this->totalWithdrawn = $totalWithdrawn;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getTotalEarned(): int
    {
        return $this->totalEarned;
    }

    public function getTotalWithdrawn(): int
    {
        return $this->totalWithdrawn;
    }

    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'balance' => $this->balance,
            'totalEarned' => $this->totalEarned,
            'totalWithdrawn' => $this->totalWithdrawn,
        ];
    }
}
