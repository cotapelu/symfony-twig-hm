<?php

declare(strict_types=1);

namespace App\Tests\Service\DigitalWallet;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\DigitalWallet\Entity\DigitalWallet;
use App\DigitalWallet\Entity\WalletTransaction;
use App\DigitalWallet\Entity\IdempotencyKey;
use App\DigitalWallet\Repository\DigitalWalletRepository;
use App\DigitalWallet\Repository\WalletTransactionRepository;
use App\DigitalWallet\Repository\IdempotencyKeyRepository;
use App\DigitalWallet\Service\DigitalWalletService;
use App\Tests\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class DigitalWalletServiceTest extends TestCase
{
    private DigitalWalletService $service;
    private DigitalWalletRepository $walletRepository;
    private WalletTransactionRepository $transactionRepository;
    private IdempotencyKeyRepository $idempotencyRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletRepository = $this->createMock(DigitalWalletRepository::class);
        $this->transactionRepository = $this->createMock(WalletTransactionRepository::class);
        $this->idempotencyRepository = $this->createMock(IdempotencyKeyRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new DigitalWalletService(
            $this->entityManager,
            $this->walletRepository,
            $this->transactionRepository,
            $this->idempotencyRepository,
            $this->logger
        );
    }

    private function createWalletEntity(string $userId, string $type = 'default', int $balance = 0): DigitalWallet
    {
        $wallet = new DigitalWallet();
        $wallet->setId(random_int(1, 1000));
        $wallet->setUserId($userId);
        $wallet->setWalletType($type);
        $wallet->setBalance($balance);
        $wallet->setCurrency('VND');
        $wallet->setStatus('active');
        return $wallet;
    }

    private function createTransactionEntity(array $data): WalletTransaction
    {
        $tx = new WalletTransaction();
        $tx->setId($data['id'] ?? random_int(1, 1000));
        $tx->setWalletId($data['wallet_id']);
        $tx->setTransactionType($data['type']);
        $tx->setAmount($data['amount']);
        $tx->setCurrency($data['currency'] ?? 'VND');
        $tx->setStatus($data['status'] ?? 'completed');
        $tx->setDescription($data['description'] ?? '');
        $tx->setReferenceId($data['reference_id'] ?? null);
        return $tx;
    }

    public function testCreateWalletReturnsExistingIfFound(): void
    {
        $userId = 'user123';
        $existingWallet = $this->createWalletEntity($userId);

        $this->walletRepository->expects($this->once())
            ->method('findByWalletTypeAndUserId')
            ->with('default', $userId)
            ->willReturn($existingWallet);

        $result = $this->service->createWallet($userId, 'default');

        $this->assertSame($existingWallet, $result);
    }

    public function testCreateWalletCreatesNewIfNotFound(): void
    {
        $userId = 'user456';
        $this->walletRepository->expects($this->once())
            ->method('findByWalletTypeAndUserId')
            ->with('default', $userId)
            ->willReturn(null);

        $persisted = null;
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($wallet) use (&$persisted, $userId) {
                $persisted = $wallet;
                return $wallet instanceof DigitalWallet && $wallet->getUserId() === $userId;
            }));
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use (&$persisted) {
                if ($persisted) {
                    $ref = new \ReflectionProperty($persisted, 'id');
                    $ref->setAccessible(true);
                    $ref->setValue($persisted, 123);
                }
            });

        $result = $this->service->createWallet($userId, 'default');

        $this->assertInstanceOf(DigitalWallet::class, $result);
        $this->assertEquals($userId, $result->getUserId());
        $this->assertEquals(123, $result->getId());
    }

    public function testDepositThrowsForInvalidPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Deposit amount must be positive');
        $this->service->deposit('wallet1', -1000);
    }

    public function testDepositThrowsForAmountOutOfRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be between 1,000 and 100,000,000');
        $this->service->deposit('wallet1', 500); // too low
    }

    public function testDepositThrowsForUnauthorizedUser(): void
    {
        $wallet = $this->createWalletEntity('owner123', balance: 50000);
        $this->walletRepository->method('find')->willReturn($wallet);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->deposit('wallet1', 10000, userId: 'attacker456');
    }

    public function testDepositUsesTransactionRetryOnOptimisticLockFailure(): void
    {
        $originalBalance = 100000;
        $prototypeWallet = $this->createWalletEntity('user1', balance: $originalBalance);
        $prototypeWallet->setId(1);
        // Return a fresh wallet on each find call
        $this->walletRepository->method('find')->willReturnCallback(function($id) use (&$prototypeWallet) {
            return clone $prototypeWallet;
        });

        $flushCount = 0;
        $persistedTx = null;

        // flush: first two attempts throw OptimisticLockException, third succeeds
        $this->entityManager->method('flush')->willReturnCallback(function () use (&$flushCount, &$persistedTx) {
            $flushCount++;
            if ($flushCount < 3) {
                throw new \Doctrine\ORM\OptimisticLockException('lock failure', null);
            }
            // after successful flush, set ID on persisted transaction if available
            if ($persistedTx) {
                $ref = new \ReflectionProperty($persistedTx, 'id');
                $ref->setAccessible(true);
                $ref->setValue($persistedTx, 1);
            }
        });

        $commitCount = 0;
        $this->entityManager->method('commit')->willReturnCallback(function () use (&$commitCount) {
            $commitCount++;
        });

        $rollbackCount = 0;
        $this->entityManager->method('rollback')->willReturnCallback(function () use (&$rollbackCount) {
            $rollbackCount++;
        });

        // persist: capture the transaction object
        $this->entityManager->method('persist')->with($this->isInstanceOf(WalletTransaction::class))->willReturnCallback(function ($tx) use (&$persistedTx) {
            $persistedTx = $tx;
        });

        $result = $this->service->deposit('wallet1', 10000);

        $this->assertInstanceOf(WalletTransaction::class, $result);
        $this->assertEquals(1, $result->getId());
        // Check transaction details
        $this->assertEquals(10000, $result->getAmount());
        $this->assertEquals('deposit', $result->getTransactionType());
        $this->assertEquals(1, $commitCount);
        $this->assertEquals(2, $rollbackCount);
        $this->assertEquals(3, $flushCount);
    }

    public function testDepositAppliesIdempotencyKeyReturnsExisting(): void
    {
        $idemKey = 'key-123';
        $existingTx = $this->createTransactionEntity(['wallet_id' => 'wallet1', 'type' => 'deposit', 'amount' => 10000, 'id' => 999]);

        $idempotency = new IdempotencyKey($idemKey, 'WalletTransaction', (string)$existingTx->getId(), new \DateTime('+1 day'));

        $this->idempotencyRepository->method('findByKey')->willReturn($idempotency);
        $this->transactionRepository->method('find')->willReturn($existingTx);

        $result = $this->service->deposit('wallet1', 10000, idempotencyKey: $idemKey);

        $this->assertSame($existingTx, $result);
    }

    public function testDepositSuccessCreatesTransactionAndUpdatesWallet(): void
    {
        $wallet = $this->createWalletEntity('user1', balance: 50000);
        $this->walletRepository->method('find')->willReturn($wallet);

        $this->entityManager->method('beginTransaction');
        $this->entityManager->method('persist');
        $this->entityManager->method('flush');
        $this->entityManager->method('commit');

        $this->idempotencyRepository->method('save'); // idempotency key saved if provided

        $result = $this->service->deposit('wallet1', 10000);

        $this->assertInstanceOf(WalletTransaction::class, $result);
        $this->assertEquals(60000, $wallet->getBalance());
        $this->assertEquals('deposit', $result->getTransactionType());
        $this->assertEquals(10000, $result->getAmount());
    }

    public function testWithdrawThrowsForInsufficientBalance(): void
    {
        $wallet = $this->createWalletEntity('user1', balance: 5000);
        $this->walletRepository->method('find')->willReturn($wallet);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        $this->service->withdraw('wallet1', 10000);
    }

    public function testWithdrawSuccessUpdatesBalanceAndCreatesTransaction(): void
    {
        $wallet = $this->createWalletEntity('user1', balance: 50000);
        $this->walletRepository->method('find')->willReturn($wallet);

        $this->entityManager->method('beginTransaction');
        $this->entityManager->method('persist');
        $this->entityManager->method('flush');
        $this->entityManager->method('commit');

        $result = $this->service->withdraw('wallet1', 20000);

        $this->assertInstanceOf(WalletTransaction::class, $result);
        $this->assertEquals(30000, $wallet->getBalance());
        $this->assertEquals('withdraw', $result->getTransactionType());
        $this->assertEquals(-20000, $result->getAmount());
    }

    public function testTransferThrowsForSameWallet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transfer to the same wallet');
        $this->service->transfer('wallet1', 'wallet1', 10000);
    }

    public function testTransferChecksOwnership(): void
    {
        $walletFrom = $this->createWalletEntity('user1', balance: 100000);
        $walletTo = $this->createWalletEntity('user2', balance: 0);
        $this->walletRepository->method('find')->willReturnMap([
            ['wallet1', $walletFrom],
            ['wallet2', $walletTo],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->transfer('wallet1', 'wallet2', 10000, userId: 'user3');
    }

    public function testTransferCalculatesFee(): void
    {
        $amount = 10000;
        $fee = 100; // 1%
        $walletFrom = $this->createWalletEntity('user1', balance: 100000);
        $walletTo = $this->createWalletEntity('user2', balance: 0);

        $this->walletRepository->method('find')->willReturnMap([
            ['wallet1', $walletFrom],
            ['wallet2', $walletTo],
        ]);

        $this->entityManager->method('beginTransaction');
        $this->entityManager->method('persist')->willReturnCallback(function ($tx) use (&$outTx1, &$outTx2) {
            if ($tx->getTransactionType() === 'transfer_out') {
                $outTx1 = $tx;
            } else {
                $outTx2 = $tx;
            }
        });
        $this->entityManager->method('flush');
        $this->entityManager->method('commit');

        $result = $this->service->transfer('wallet1', 'wallet2', $amount);

        $this->assertCount(2, $result);
        $this->assertEquals(-$amount - $fee, $result[0]->getAmount());
        $this->assertEquals($amount, $result[1]->getAmount());
    }

    public function testTransferFailsIfInsufficientBalanceAfterFee(): void
    {
        $walletFrom = $this->createWalletEntity('user1', balance: 10000);
        $walletTo = $this->createWalletEntity('user2', balance: 0);
        $this->walletRepository->method('find')->willReturnMap([
            ['wallet1', $walletFrom],
            ['wallet2', $walletTo],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        $this->service->transfer('wallet1', 'wallet2', 10000); // fee 100 => total 10100 > balance 10000
    }

    public function testGetBalanceThrowsIfWalletNotFound(): void
    {
        $this->walletRepository->method('find')->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wallet not found');
        $this->service->getBalance('nonexistent');
    }

    public function testGetBalanceReturnsBalance(): void
    {
        $wallet = $this->createWalletEntity('user1', balance: 12345);
        $this->walletRepository->method('find')->willReturn($wallet);

        $balance = $this->service->getBalance('wallet1');
        $this->assertEquals(12345, $balance);
    }

    public function testIsWalletOwnedByReturnsTrueForSameUser(): void
    {
        $wallet = $this->createWalletEntity('user123');
        $this->walletRepository->method('find')->willReturn($wallet);

        $result = $this->service->isWalletOwnedBy('wallet1', 'user123');
        $this->assertTrue($result);
    }

    public function testIsWalletOwnedByReturnsFalseForDifferentUser(): void
    {
        $wallet = $this->createWalletEntity('user123');
        $this->walletRepository->method('find')->willReturn($wallet);

        $result = $this->service->isWalletOwnedBy('wallet1', 'user456');
        $this->assertFalse($result);
    }

    public function testWalletExistsReturnsTrueIfFound(): void
    {
        $this->walletRepository->method('find')->willReturn($this->createWalletEntity('user1'));
        $this->assertTrue($this->service->walletExists('wallet1'));
    }

    public function testWalletExistsReturnsFalseIfNotFound(): void
    {
        $this->walletRepository->method('find')->willReturn(null);
        $this->assertFalse($this->service->walletExists('wallet1'));
    }

    public function testDepositThrowsForNonPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->deposit('w', 0);
    }

    public function testWithdrawThrowsForNonPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->withdraw('w', 0);
    }

    public function testTransferThrowsForNonPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->transfer('w1', 'w2', 0);
    }

    public function testDailyLimitExceededThrowsDuringDeposit(): void
    {
        $wallet = $this->createWalletEntity('user1');
        $this->walletRepository->method('find')->willReturn($wallet);
        $this->transactionRepository->method('countByWalletAndDateRange')->willReturn(50);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Daily transaction limit exceeded');
        $this->service->deposit('wallet1', 10000);
    }

    public function testDailyAmountLimitExceededThrowsDuringDeposit(): void
    {
        $wallet = $this->createWalletEntity('user1');
        $this->walletRepository->method('find')->willReturn($wallet);
        $this->transactionRepository->method('countByWalletAndDateRange')->willReturn(10);
        $this->transactionRepository->method('sumAmountByWalletAndDateRange')->willReturn(99900000);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Daily amount limit exceeded');
        $this->service->deposit('wallet1', 2000000);
    }
}
