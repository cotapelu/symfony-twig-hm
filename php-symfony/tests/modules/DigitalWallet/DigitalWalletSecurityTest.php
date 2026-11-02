<?php
declare(strict_types=1);

namespace App\Tests\Modules\DigitalWallet;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\DigitalWallet\Service\DigitalWalletService;
use App\DigitalWallet\Entity\DigitalWallet;
use App\DigitalWallet\Repository\DigitalWalletRepository;
use App\DigitalWallet\Repository\WalletTransactionRepository;
use App\DigitalWallet\Repository\IdempotencyKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * DigitalWallet Security Tests
 *
 * Tests for critical security scenarios:
 * - IDOR prevention on wallet access
 * - Transfer ownership validation
 */
#[AllowMockObjectsWithoutExpectations]
class DigitalWalletSecurityTest extends TestCase
{
    private DigitalWalletService $service;
    private $em;
    private $walletRepo;
    private $txRepo;
    private $idemRepo;
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->walletRepo = $this->createMock(DigitalWalletRepository::class);
        $this->txRepo = $this->createMock(WalletTransactionRepository::class);
        $this->idemRepo = $this->createMock(IdempotencyKeyRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new DigitalWalletService(
            $this->em,
            $this->walletRepo,
            $this->txRepo,
            $this->idemRepo,
            $this->logger
        );
    }

    public function testIsWalletOwnedByReturnsFalseForDifferentUser(): void
    {
        $wallet = new DigitalWallet();
        $wallet->setUserId('user1');

        $this->walletRepo->expects($this->once())
            ->method('find')
            ->with('wallet-1')
            ->willReturn($wallet);

        $result = $this->service->isWalletOwnedBy('wallet-1', 'user2');

        $this->assertFalse($result);
    }

    public function testIsWalletOwnedByReturnsTrueForSameUser(): void
    {
        $wallet = new DigitalWallet();
        $wallet->setUserId('user1');

        $this->walletRepo->expects($this->once())
            ->method('find')
            ->with('wallet-1')
            ->willReturn($wallet);

        $result = $this->service->isWalletOwnedBy('wallet-1', 'user1');

        $this->assertTrue($result);
    }

    public function testDepositAmountBelowMinimumRejects(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be between 1,000 and 100,000,000');

        $this->service->deposit('1', 100);
    }

    public function testDepositAmountAboveMaximumRejects(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be between 1,000 and 100,000,000');

        $this->service->deposit('1', 200000000);
    }

    public function testTransferSameWalletRejects(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transfer to the same wallet');

        $this->service->transfer('wallet-1', 'wallet-1', 1000);
    }

    public function testTransferNegativeAmountRejects(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->transfer('wallet-1', 'wallet-2', -1000);
    }

    public function testWithdrawNegativeAmountRejects(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Withdrawal amount must be positive');

        $this->service->withdraw('1', -1000);
    }
}