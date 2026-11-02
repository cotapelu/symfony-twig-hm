<?php

declare(strict_types=1);

namespace App\Payment\Entity;

use DateTimeImmutable;

class CryptoInvoice
{
    private string $id;
    private string $userId;
    private string $amount;   // e.g., "10.00"
    private string $currency; // BTC, ETH, USDC
    private string $status;   // pending, paid, expired
    private ?DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $paidAt = null;
    private ?string $transactionHash = null;

    public function __construct(string $id, string $userId, string $amount, string $currency)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = 'pending';
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getAmount() { return $this->amount; }
    public function getCurrency() { return $this->currency; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getPaidAt() { return $this->paidAt; }
    public function setPaidAt(?DateTimeImmutable $paidAt) { $this->paidAt = $paidAt; return $this; }
    public function getTransactionHash() { return $this->transactionHash; }
    public function setTransactionHash(?string $tx) { $this->transactionHash = $tx; return $this; }
}
