<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity\Generated;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'wallets')]
#[ORM\UniqueConstraint(name: 'uq_wallets_user_id', columns: ['user_id'])]
class Wallet
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid', unique: true)]
    private string $userId;

    #[ORM\Column(name: 'balance_cents', type: 'integer')]
    private int $balanceCents = 0;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'VND';

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\Column(type: 'integer')]
    private int $version = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getBalanceCents(): int { return $this->balanceCents; }
    public function setBalanceCents(int $balanceCents): self { $this->balanceCents = $balanceCents; return $this; }
    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): self { $this->currency = $currency; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getVersion(): int { return $this->version; }
    public function incrementVersion(): self { $this->version++; return $this; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'wallet_transactions')]
#[ORM\Index(name: 'idx_wallet_transactions_wallet_id', columns: ['wallet_id'])]
#[ORM\Index(name: 'idx_wallet_transactions_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_wallet_transactions_idempotency_key', columns: ['idempotency_key'])]
class WalletTransaction
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'wallet_id', type: 'uuid')]
    private string $walletId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'amount_cents', type: 'integer')]
    private int $amountCents;

    #[ORM\Column(name: 'balance_after', type: 'integer')]
    private int $balanceAfter;

    #[ORM\Column(name: 'reference_id', type: 'string', length: 100, nullable: true)]
    private ?string $referenceId;

    #[ORM\Column(name: 'idempotency_key', type: 'string', length: 36, unique: true, nullable: true)]
    private ?string $idempotencyKey;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'completed';

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public const TYPE_DEPOSIT = 'DEPOSIT';
    public const TYPE_WITHDRAW = 'WITHDRAW';
    public const TYPE_TRANSFER = 'TRANSFER';

    public function __construct(string $id, string $walletId, string $type, int $amountCents, int $balanceAfter)
    {
        $this->id = $id;
        $this->walletId = $walletId;
        $this->type = $type;
        $this->amountCents = $amountCents;
        $this->balanceAfter = $balanceAfter;
        $this->createdAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getWalletId(): string { return $this->walletId; }
    public function getType(): string { return $this->type; }
    public function getAmountCents(): int { return $this->amountCents; }
    public function getBalanceAfter(): int { return $this->balanceAfter; }
    public function getReferenceId(): ?string { return $this->referenceId; }
    public function setReferenceId(?string $referenceId): self { $this->referenceId = $referenceId; return $this; }
    public function getIdempotencyKey(): ?string { return $this->idempotencyKey; }
    public function setIdempotencyKey(?string $idempotencyKey): self { $this->idempotencyKey = $idempotencyKey; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getMetadata(): array { return $this->metadata; }
    public function setMetadata(array $metadata): self { $this->metadata = $metadata; return $this; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'idempotency_keys')]
class IdempotencyKey
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $key;

    #[ORM\Column(type: 'json')]
    private array $response;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $key, array $response)
    {
        $this->key = $key;
        $this->response = $response;
        $this->createdAt = new \DateTime();
    }

    public function getKey(): string { return $this->key; }
    public function getResponse(): array { return $this->response; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
}