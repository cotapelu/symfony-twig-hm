<?php

namespace App\DigitalWallet\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'wallets')]
class DigitalWallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $walletType;

    #[ORM\Column(type: 'integer')]
    private int $balance;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $bankAccount;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $cardNumber;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private int $version = 0;

    public function __construct()
    {
        $this->balance = 0;
        $this->currency = 'VND';
        $this->status = 'active';
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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


    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(string $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function getWalletType()
    {
        return $this->walletType;
    }

    public function setWalletType(string $walletType)
    {
        $this->walletType = $walletType;
        return $this;
    }

    public function getBalance()
    {
        return $this->balance;
    }

    public function setBalance(int $balance)
    {
        $this->balance = $balance;
        return $this;
    }

    public function addBalance(int $amount)
    {
        $this->balance += $amount;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function deductBalance(int $amount)
    {
        $this->balance -= $amount;
        $this->updatedAt = new \DateTime();
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

    public function getBankAccount()
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?string $bankAccount)
    {
        $this->bankAccount = $bankAccount;
        return $this;
    }

    public function getCardNumber()
    {
        return $this->cardNumber;
    }

    public function setCardNumber(?string $cardNumber)
    {
        $this->cardNumber = $cardNumber;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
