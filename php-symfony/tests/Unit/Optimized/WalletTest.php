<?php

declare(strict_types=1);

namespace App\Tests\Unit\Optimized;

use PHPUnit\Framework\TestCase;
use App\Core\Entity\Wallet;

// Load the file containing all entity classes (non-PSR-4)
require_once __DIR__.'/../../../src/Entity/Optimized/EcommerceWallet.php';

class WalletTest extends TestCase
{
    private function createWallet(?string $id = null, ?string $userId = null): Wallet
    {
        $id = $id ?? 'wallet-id';
        $userId = $userId ?? 'user-1';
        return new Wallet($id, $userId);
    }

    public function testConstructorInitializesProperties(): void
    {
        $wallet = $this->createWallet();

        $this->assertSame('wallet-id', $wallet->getId());
        $this->assertSame('user-1', $wallet->getUserId());
        $this->assertSame(0, $wallet->getBalanceCents());
        $this->assertSame('VND', $wallet->getCurrency());
        $this->assertSame(Wallet::STATUS_ACTIVE, $wallet->getStatus());
        $this->assertSame(0, $wallet->getVersion());
    }

    public function testCreditIncreasesBalance(): void
    {
        $wallet = $this->createWallet();
        $wallet->credit(5000);
        $this->assertSame(5000, $wallet->getBalanceCents());
    }

    public function testDebitDecreasesBalance(): void
    {
        $wallet = $this->createWallet();
        $wallet->credit(10000);
        $wallet->debit(3000);
        $this->assertSame(7000, $wallet->getBalanceCents());
    }

    public function testCanDebitReturnsTrueWhenSufficientBalanceAndActive(): void
    {
        $wallet = $this->createWallet();
        $wallet->credit(10000);
        $this->assertTrue($wallet->canDebit(5000));
        $this->assertTrue($wallet->canDebit(10000));
    }

    public function testCanDebitReturnsFalseWhenInsufficientBalance(): void
    {
        $wallet = $this->createWallet();
        $wallet->credit(5000);
        $this->assertFalse($wallet->canDebit(6000));
    }

    public function testCanDebitReturnsFalseWhenWalletBlocked(): void
    {
        $wallet = $this->createWallet();
        $wallet->credit(10000);
        // Use reflection to set status to BLOCKED
        $reflection = new \ReflectionClass($wallet);
        $prop = $reflection->getProperty('status');
        $prop->setAccessible(true);
        $prop->setValue($wallet, Wallet::STATUS_BLOCKED);
        $this->assertFalse($wallet->canDebit(5000));
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('ACTIVE', Wallet::STATUS_ACTIVE);
        $this->assertSame('BLOCKED', Wallet::STATUS_BLOCKED);
    }
}
