<?php
declare(strict_types=1);

namespace App\Tests\Unit\Modules\DigitalWallet\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\DigitalWallet\Service\DigitalWalletService;
use App\DigitalWallet\Entity\DigitalWallet;
use App\DigitalWallet\Entity\WalletTransaction;
use App\DigitalWallet\Entity\IdempotencyKey;
use App\DigitalWallet\Repository\DigitalWalletRepository;
use App\DigitalWallet\Repository\WalletTransactionRepository;
use App\DigitalWallet\Repository\IdempotencyKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use DateTime;

/**
 * Comprehensive DigitalWalletService Tests
 * 
 * Coverage: Happy path, edge cases, security, errors, race conditions
 */
#[AllowMockObjectsWithoutExpectations]
class DigitalWalletServiceComprehensiveTest extends TestCase
{
    private DigitalWalletService $service;
    private $em;
    private $walletRepo;
    private $txRepo;
    private $idemRepo;
    private $logger;

    protected function setUp(): void
    {
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

    // ========== HAPPY PATH TESTS ==========

    public function testCreateWalletSuccess(): void
    {
        $userId = 'user-123';
        
        $this->walletRepo->expects($this->once())
            ->method('findByWalletTypeAndUserId')
            ->with('main', $userId)
            ->willReturn(null);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())->method('info');

        $wallet = $this->service->createWallet($userId, 'main', 'VND');
        
        $this->assertEquals($userId, $wallet->getUserId());
        $this->assertEquals('main', $wallet->getWalletType());
        $this->assertEquals(0, $wallet->getBalance());
        $this->assertEquals('VND', $wallet->getCurrency());
    }

    public function testCreateWalletReturnsExisting(): void
    {
        $userId = 'user-123';
        $existingWallet = new DigitalWallet();
        $existingWallet->setUserId($userId);
        
        $this->walletRepo->expects($this->once())
            ->method('findByWalletTypeAndUserId')
            ->willReturn($existingWallet);

        $this->em->expects($this->never())->method('persist');
        
        $result = $this->service->createWallet($userId, 'main', 'VND');
        
        $this->assertSame($existingWallet, $result);
    }

    public function testGetBalanceSuccess(): void
    {
        $walletId = '1';
        $wallet = new DigitalWallet();
        $wallet->setId(1);
        $wallet->setBalance(50000);
        $wallet->setUserId('user-1');

        $this->walletRepo->expects($this->once())
            ->method('find')
            ->with($walletId)
            ->willReturn($wallet);

        $balance = $this->service->getBalance($walletId);
        
        $this->assertEquals(50000, $balance);
    }

    public function testIsWalletOwnedByReturnsTrue(): void
    {
        $walletId = '1';
        $userId = 'user-123';
        
        $wallet = new DigitalWallet();
        $wallet->setUserId($userId);

        $this->walletRepo->expects($this->once())
            ->method('find')
            ->willReturn($wallet);

        $result = $this->service->isWalletOwnedBy($walletId, $userId);
        
        $this->assertTrue($result);
    }

    public function testIsWalletOwnedByReturnsFalseForDifferentUser(): void
    {
        $wallet = new DigitalWallet();
        $wallet->setUserId('other-user');

        $this->walletRepo->expects($this->once())
            ->method('find')
            ->willReturn($wallet);

        $result = $this->service->isWalletOwnedBy('1', 'user-123');
        
        $this->assertFalse($result);
    }

    public function testWalletExistsReturnsTrue(): void
    {
        $wallet = new DigitalWallet();
        
        $this->walletRepo->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($wallet);

        $this->assertTrue($this->service->walletExists('1'));
    }

    public function testWalletExistsReturnsFalse(): void
    {
        $this->walletRepo->expects($this->once())
            ->method('find')
            ->with('999')
            ->willReturn(null);

        $this->assertFalse($this->service->walletExists('999'));
    }

    // ========== VALIDATION TESTS ==========

    public function testDepositRejectsZeroAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Deposit amount must be positive');
        
        $this->service->deposit('1', 0);
    }

    public function testDepositRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->deposit('1', -100);
    }

    public function testDepositRejectsAmountBelowMinimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be between 1,000 and 100,000,000');
        
        $this->service->deposit('1', 100);
    }

    public function testDepositRejectsAmountAboveMaximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->deposit('1', 200000000);
    }

    public function testWithdrawRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Withdrawal amount must be positive');
        
        $this->service->withdraw('1', -1000);
    }

    public function testTransferRejectsSameWallet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transfer to the same wallet');
        
        $this->service->transfer('1', '1', 1000);
    }

    public function testTransferRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->transfer('1', '2', -1000);
    }

    // ========== IDEMPOTENCY TESTS ==========

    public function testDepositReturnsExistingTransactionForIdempotencyKey(): void
    {
        $existingTx = new WalletTransaction();
        $existingTx->setId(100);
        $existingTx->setAmount(5000);

        $idemKey = new IdempotencyKey('key-123', 'WalletTransaction', '100', new DateTime('+24 hours'));

        $this->idemRepo->expects($this->once())
            ->method('findByKey')
            ->with('key-123')
            ->willReturn($idemKey);

        $this->txRepo->expects($this->once())
            ->method('find')
            ->with('100')
            ->willReturn($existingTx);

        $result = $this->service->deposit('1', 5000, 'test', 'key-123');
        
        $this->assertSame($existingTx, $result);
    }

    public function testTransferReturnsExistingTransactionsForIdempotencyKey(): void
    {
        $fromTx = new WalletTransaction();
        $fromTx->setId(101);
        $toTx = new WalletTransaction();
        $toTx->setId(102);

        $idemKey = new IdempotencyKey('key-xyz', 'WalletTransfer', json_encode(['from'=>101,'to'=>102]), new DateTime('+24 hours'));

        $this->idemRepo->expects($this->once())->method('findByKey')->willReturn($idemKey);
        $this->txRepo->expects($this->exactly(2))->method('find')->willReturnOnConsecutiveCalls($fromTx, $toTx);

        $result = $this->service->transfer('1', '2', 10000, 'test', 'key-xyz');
        
        $this->assertCount(2, $result);
        $this->assertSame($fromTx, $result[0]);
        $this->assertSame($toTx, $result[1]);
    }

    // ========== DAILY LIMIT TESTS ==========

    public function testDepositRejectsExcessiveDailyTransactions(): void
    {
        $wallet = new DigitalWallet();
        $wallet->setId(1);
        $wallet->setUserId('user-1');

        // checkDailyLimits needs walletRepository call inside the transactional loop
        // but it fails before we get there because count is 50
        $this->walletRepo->expects($this->any())
            ->method('find')
            ->willReturn($wallet);

        $this->txRepo->expects($this->atLeastOnce())
            ->method('countByWalletAndDateRange')
            ->willReturn(50); // Max reached

        $this->txRepo->expects($this->any())
            ->method('sumAmountByWalletAndDateRange')
            ->willReturn(0);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Daily transaction limit exceeded');

        $this->service->deposit('1', 5000);
    }

    // ========== ERROR HANDLING TESTS ==========

    public function testGetBalanceThrowsExceptionForNonexistentWallet(): void
    {
        $this->walletRepo->expects($this->once())
            ->method('find')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wallet not found');

        $this->service->getBalance('nonexistent');
    }

    public function testDepositThrowsExceptionForNonexistentWallet(): void
    {
        $this->walletRepo->expects($this->atLeastOnce())
            ->method('find')
            ->with('nonexistent')
            ->willReturn(null);

        $this->em->expects($this->atLeastOnce())->method('beginTransaction');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wallet not found');

        $this->service->deposit('nonexistent', 5000);
    }
}