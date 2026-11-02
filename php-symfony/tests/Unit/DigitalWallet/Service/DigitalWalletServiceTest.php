<?php declare(strict_types=1);

namespace App\Tests\Unit\DigitalWallet\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\DigitalWallet\Service\DigitalWalletService;
use App\DigitalWallet\Entity\DigitalWallet;
use App\DigitalWallet\Entity\WalletTransaction;
use App\DigitalWallet\Entity\IdempotencyKey;
use App\DigitalWallet\Repository\DigitalWalletRepository;
use App\DigitalWallet\Repository\WalletTransactionRepository;
use App\DigitalWallet\Repository\IdempotencyKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DigitalWalletServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private DigitalWalletRepository&MockObject $walletRepo;
    private WalletTransactionRepository&MockObject $txRepo;
    private IdempotencyKeyRepository&MockObject $idRepo;
    private LoggerInterface&MockObject $logger;
    private DigitalWalletService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->walletRepo = $this->createMock(DigitalWalletRepository::class);
        $this->txRepo = $this->createMock(WalletTransactionRepository::class);
        $this->idRepo = $this->createMock(IdempotencyKeyRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new DigitalWalletService(
            $this->em,
            $this->walletRepo,
            $this->txRepo,
            $this->idRepo,
            $this->logger
        );

        // Default stubs for daily limits checks: no transactions
        $this->txRepo->method('countByWalletAndDateRange')->willReturn(0);
        $this->txRepo->method('sumAmountByWalletAndDateRange')->willReturn(0);
        $this->txRepo->method('findRecentByWallet')->willReturn([]);
    }

    public function testCreateWalletReturnsExistingIfFound(): void
    {
        $userId = 'user123';
        $walletType = 'main';
        $existing = $this->createMockWallet('wallet-1', $userId, $walletType, 0);
        $this->walletRepo->expects($this->once())
            ->method('findByWalletTypeAndUserId')
            ->with($walletType, $userId)
            ->willReturn($existing);
        $result = $this->service->createWallet($userId, $walletType);
        $this->assertSame($existing, $result);
        $this->em->expects($this->never())->method('persist');
    }

    public function testCreateWalletCreatesNewIfNotExists(): void
    {
        $userId = 'user456';
        $walletType = 'savings';
        $currency = 'USD';
        $this->walletRepo->expects($this->once())
            ->method('findByWalletTypeAndUserId')
            ->with($walletType, $userId)
            ->willReturn(null);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->createWallet($userId, $walletType, $currency);
        $this->assertInstanceOf(DigitalWallet::class, $result);
        $this->assertEquals($userId, $result->getUserId());
        $this->assertEquals($walletType, $result->getWalletType());
        $this->assertEquals($currency, $result->getCurrency());
        $this->assertEquals(0, $result->getBalance());
        $this->assertEquals('active', $result->getStatus());
    }

    public function testDepositValidatesPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->deposit('w1', -100, 'Test');
    }

    public function testDepositValidatesAmountRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->deposit('w1', 500, 'Too small');
    }

    public function testDepositChecksOwnershipWhenUserIdProvided(): void
    {
        $wallet = $this->createMockWallet('w1', 'owner1', 'main', 1000);
        $this->walletRepo->method('find')->with('w1')->willReturn($wallet);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->deposit('w1', 5000, 'Deposit', null, 'otherUser');
    }

    public function testDepositSuccessWithoutIdempotency(): void
    {
        $wallet = $this->createMockWallet('w1', 'user1', 'main', 1000);
        $this->walletRepo->method('find')->with('w1')->willReturn($wallet);
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('commit');
        $this->em->expects($this->never())->method('rollback');
        $this->em->expects($this->exactly(1))->method('persist'); // transaction
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->deposit('w1', 5000, 'Top-up', null, 'user1');
        $this->assertInstanceOf(WalletTransaction::class, $result);
        $this->assertEquals(5000, $result->getAmount());
        $this->assertEquals('deposit', $result->getTransactionType());
        $this->assertEquals('w1', $result->getWalletId());
    }

    public function testDepositWithIdempotencyReplays(): void
    {
        $wallet = $this->createMockWallet('w1', 'user1', 'main', 1000);
        $this->walletRepo->method('find')->with('w1')->willReturn($wallet);
        $existingTx = $this->createMockTransaction('tx1', 'w1', 'deposit', 5000);
        $idempKey = $this->createMockIdempotencyKey('key-123', 'WalletTransaction', 'tx1', false);
        $this->idRepo->expects($this->once())
            ->method('findByKey')
            ->with('key-123')
            ->willReturn($idempKey);
        $this->txRepo->expects($this->once())
            ->method('find')
            ->with('tx1')
            ->willReturn($existingTx);
        $result = $this->service->deposit('w1', 5000, 'Top-up', 'key-123', 'user1');
        $this->assertSame($existingTx, $result);
        $this->em->expects($this->never())->method('beginTransaction');
    }

    public function testWithdrawValidatesAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->withdraw('w1', 0);
    }

    public function testWithdrawChecksInsufficientBalance(): void
    {
        $wallet = $this->createMockWallet('w1', 'user1', 'main', 1000);
        $this->walletRepo->method('find')->with('w1')->willReturn($wallet);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        $this->service->withdraw('w1', 2000, 'Withdraw', null, 'user1');
    }

    public function testWithdrawSuccess(): void
    {
        $wallet = $this->createMockWallet('w1', 'user1', 'main', 10000);
        $wallet->expects($this->once())->method('deductBalance')->with(3000)->willReturnSelf();
        $this->walletRepo->method('find')->with('w1')->willReturn($wallet);
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('commit');
        $this->em->expects($this->exactly(1))->method('persist');
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->withdraw('w1', 3000, 'Cash out', null, 'user1');
        $this->assertInstanceOf(WalletTransaction::class, $result);
        $this->assertEquals(-3000, $result->getAmount());
        $this->assertEquals('withdraw', $result->getTransactionType());
    }

    public function testTransferValidatesSameWallet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->transfer('w1', 'w1', 1000);
    }

    public function testTransferChecksInsufficientBalanceWithFee(): void
    {
        $from = $this->createMockWallet('w1', 'user1', 'main', 1000);
        $to   = $this->createMockWallet('w2', 'user2', 'main', 0);
        $this->walletRepo->method('find')->willReturnMap([['w1', $from], ['w2', $to]]);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        $this->service->transfer('w1', 'w2', 2000, 'Transfer', null, 'user1');
    }

    public function testTransferSuccess(): void
    {
        $from = $this->createMockWallet('w1', 'user1', 'main', 10000);
        $to   = $this->createMockWallet('w2', 'user2', 'main', 5000);
        // Expect deductBalance on from and addBalance on to
        $from->expects($this->once())->method('deductBalance')->with(1010)->willReturnSelf();
        $to->expects($this->once())->method('addBalance')->with(1000)->willReturnSelf();
        $this->walletRepo->method('find')->willReturnMap([['w1', $from], ['w2', $to]]);
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('commit');
        $this->em->expects($this->exactly(2))->method('persist'); // two transactions
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->transfer('w1', 'w2', 1000, 'Split', null, 'user1');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('transfer_out', $result[0]->getTransactionType());
        $this->assertEquals('transfer_in', $result[1]->getTransactionType());
        $this->assertEquals(-1010, $result[0]->getAmount());
        $this->assertEquals(10, $result[0]->getFee()); // 1% fee
        $this->assertEquals(1000, $result[1]->getAmount());
    }

    public function testGetBalanceReturnsBalance(): void
    {
        $wallet = $this->createMockWallet('w1', 'user1', 'main', 5000);
        $this->walletRepo->method('find')->with('w1')->willReturn($wallet);
        $balance = $this->service->getBalance('w1', 'user1');
        $this->assertEquals(5000, $balance);
    }

    public function testGetBalanceThrowsIfNotFound(): void
    {
        $this->walletRepo->method('find')->with('unknown')->willReturn(null);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wallet not found');
        $this->service->getBalance('unknown');
    }

    public function testWalletExists(): void
    {
        $this->walletRepo->method('find')->willReturnMap([
            ['w1', $this->createMockWallet('w1','u1','main',0)],
            ['w2', null]
        ]);
        $this->assertTrue($this->service->walletExists('w1'));
        $this->assertFalse($this->service->walletExists('w2'));
    }

    public function testIsWalletOwnedBy(): void
    {
        $wallet = $this->createMockWallet('w1', 'user1', 'main', 0);
        $this->walletRepo->method('find')->with('w1')->willReturn($wallet);
        $this->assertTrue($this->service->isWalletOwnedBy('w1', 'user1'));
        $this->assertFalse($this->service->isWalletOwnedBy('w1', 'other'));
    }

    public function testGetTransactionHistoryCallsRepository(): void
    {
        $wallet = $this->createMockWallet('w1', 'user1', 'main', 0);
        $this->walletRepo->method('find')->with('w1')->willReturn($wallet);
        $expectedTxs = [$this->createMockTransaction('tx1','w1','deposit',1000)];
        $this->txRepo->expects($this->once())
            ->method('findByWalletIdWithLimit')
            ->with('w1', 10, 0)
            ->willReturn($expectedTxs);
        $result = $this->service->getTransactionHistory('w1', 10, 0, 'user1');
        $this->assertCount(1, $result);
        $this->assertSame($expectedTxs[0], $result[0]);
    }

    // Helper mocks
    private function createMockWallet(string $id, string $userId, string $type, int $balance, string $currency = 'VND', string $status = 'active'): MockObject&DigitalWallet
    {
        $wallet = $this->createMock(DigitalWallet::class);
        $wallet->method('getId')->willReturn($id);
        $wallet->method('getUserId')->willReturn($userId);
        $wallet->method('getWalletType')->willReturn($type);
        $wallet->method('getBalance')->willReturn($balance);
        $wallet->method('getCurrency')->willReturn($currency);
        $wallet->method('getStatus')->willReturn($status);
        $wallet->method('addBalance')->willReturn($wallet);
        $wallet->method('deductBalance')->willReturn($wallet);
        return $wallet;
    }

    private function createMockTransaction(string $id, string $walletId, string $type, int $amount): MockObject&WalletTransaction
    {
        $tx = $this->createMock(WalletTransaction::class);
        $tx->method('getId')->willReturn($id);
        $tx->method('getWalletId')->willReturn($walletId);
        $tx->method('getTransactionType')->willReturn($type);
        $tx->method('getAmount')->willReturn($amount);
        return $tx;
    }

    private function createMockIdempotencyKey(string $key, string $entityType, string $entityId, bool $expired = false): MockObject&IdempotencyKey
    {
        $idemp = $this->createMock(IdempotencyKey::class);
        $idemp->method('getKey')->willReturn($key);
        $idemp->method('getEntityType')->willReturn($entityType);
        $idemp->method('getEntityId')->willReturn($entityId);
        $idemp->method('isExpired')->willReturn($expired);
        return $idemp;
    }
}
