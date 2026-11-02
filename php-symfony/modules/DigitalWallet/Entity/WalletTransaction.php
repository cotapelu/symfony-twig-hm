<?php

namespace App\DigitalWallet\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'wallet_transactions', indexes: [
    new ORM\Index(name: 'idx_wallet_transactions_wallet_id', columns: ['wallet_id']),
    new ORM\Index(name: 'idx_wallet_transactions_created_at', columns: ['created_at']),
    new ORM\Index(name: 'idx_wallet_transactions_reference_id', columns: ['reference_id']),
])]
class WalletTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DigitalWallet::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'wallet_id', referencedColumnName: 'id', nullable: false)]
    private DigitalWallet $wallet;

    #[ORM\Column(type: 'string', length: 255)]
    private string $walletId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $transactionType;

    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $referenceId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $recipientWalletId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $fee = 0;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->currency = 'VND';
        $this->status = 'pending';
        $this->fee = 0;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }


    public function getWalletId()
    {
        return $this->walletId;
    }

    public function setWalletId(string $walletId)
    {
        $this->walletId = $walletId;
        return $this;
    }

    public function getTransactionType()
    {
        return $this->transactionType;
    }

    public function setTransactionType(string $transactionType)
    {
        $this->transactionType = $transactionType;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount(int $amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function getReferenceId()
    {
        return $this->referenceId;
    }

    public function setReferenceId(?string $referenceId)
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    public function getRecipientWalletId()
    {
        return $this->recipientWalletId;
    }

    public function setRecipientWalletId(?string $recipientWalletId)
    {
        $this->recipientWalletId = $recipientWalletId;
        return $this;
    }

    public function getFee()
    {
        return $this->fee;
    }

    public function setFee(?int $fee)
    {
        $this->fee = $fee;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
