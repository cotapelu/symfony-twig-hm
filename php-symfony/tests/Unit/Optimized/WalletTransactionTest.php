<?php

declare(strict_types=1);

namespace App\Tests\Unit\Optimized;

use PHPUnit\Framework\TestCase;
use App\Core\Entity\WalletTransaction;

// Load the file containing all entity classes (non-PSR-4)
require_once __DIR__.'/../../../src/Entity/Optimized/EcommerceWallet.php';

class WalletTransactionTest extends TestCase
{
    private function createTransaction(?string $id = null, ?string $walletId = null, ?string $type = null, ?int $amountCents = null, ?int $balanceAfter = null): WalletTransaction
    {
        $id = $id ?? 'tx-id';
        $walletId = $walletId ?? 'wallet-1';
        $type = $type ?? WalletTransaction::TYPE_DEPOSIT;
        $amountCents = $amountCents ?? 1000;
        $balanceAfter = $balanceAfter ?? 1000;
        return new WalletTransaction($id, $walletId, $type, $amountCents, $balanceAfter);
    }

    public function testConstructorInitializesProperties(): void
    {
        $tx = $this->createTransaction();

        $this->assertSame('tx-id', $tx->getId());
        $this->assertSame('wallet-1', $tx->getWalletId());
        $this->assertSame(WalletTransaction::TYPE_DEPOSIT, $tx->getType());
        $this->assertSame(1000, $tx->getAmountCents());
        $this->assertSame(1000, $tx->getBalanceAfter());
        $this->assertNull($tx->getReferenceId());
        $this->assertNull($tx->getIdempotencyKey());
        $this->assertSame(WalletTransaction::STATUS_PENDING, $tx->getStatus());
        $this->assertNull($tx->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $tx->getCreatedAt());
    }

    public function testGettersAndSetterForStatus(): void
    {
        $tx = $this->createTransaction();

        $tx->setStatus(WalletTransaction::STATUS_COMPLETED);
        $this->assertSame(WalletTransaction::STATUS_COMPLETED, $tx->getStatus());

        $tx->setStatus(WalletTransaction::STATUS_FAILED);
        $this->assertSame(WalletTransaction::STATUS_FAILED, $tx->getStatus());
    }

    public function testTypeConstants(): void
    {
        $this->assertSame('DEPOSIT', WalletTransaction::TYPE_DEPOSIT);
        $this->assertSame('WITHDRAW', WalletTransaction::TYPE_WITHDRAW);
        $this->assertSame('TRANSFER_IN', WalletTransaction::TYPE_TRANSFER_IN);
        $this->assertSame('TRANSFER_OUT', WalletTransaction::TYPE_TRANSFER_OUT);
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('PENDING', WalletTransaction::STATUS_PENDING);
        $this->assertSame('COMPLETED', WalletTransaction::STATUS_COMPLETED);
        $this->assertSame('FAILED', WalletTransaction::STATUS_FAILED);
    }
}
