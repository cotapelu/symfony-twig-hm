<?php

declare(strict_types=1);

namespace App\Tests\Modules\DigitalWallet\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\DigitalWallet\Entity\DigitalWallet;
use App\DigitalWallet\Entity\WalletTransaction;
use App\DigitalWallet\Entity\IdempotencyKey;
use App\DigitalWallet\Repository\DigitalWalletRepository;
use App\DigitalWallet\Repository\WalletTransactionRepository;
use App\DigitalWallet\Repository\IdempotencyKeyRepository;
use App\DigitalWallet\Service\DigitalWalletService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class DigitalWalletServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private DigitalWalletRepository&MockObject $walletRepository;
    private WalletTransactionRepository&MockObject $transactionRepository;
    private IdempotencyKeyRepository&MockObject $idempotencyRepository;
    private LoggerInterface&MockObject $logger;
    private DigitalWalletService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->walletRepository = $this->createMock(DigitalWalletRepository::class);
        $this->transactionRepository = $this->createMock(WalletTransactionRepository::class);
        $this->idempotencyRepository = $this->createMock(IdempotencyKeyRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new DigitalWalletService(
            $this->entityManager,
            $this->walletRepository,
            $this->transactionRepository,
            $this->idempotencyRepository,
            $this->logger
        );
    }

    public function testCreateWalletReturnsExisting(): void
    {
        $existingWallet = new DigitalWallet();
        $existingWallet->setId(1);
        $existingWallet->setUserId('user-123');
        $existingWallet->setWalletType('default');
        $existingWallet->setCurrency('VND');
        $existingWallet->setBalance(0);
        $existingWallet->setStatus('active');

        $this->walletRepository->expects($this->once())
            ->method('findByWalletTypeAndUserId')
            ->with('default', 'user-123')
            ->willReturn($existingWallet);

        $result = $this->service->createWallet('user-123', 'default', 'VND');

        $this->assertSame($existingWallet, $result);
    }

    public function testCreateWalletPersistsNew(): void
    {
        $wallet = new DigitalWallet();

        $this->walletRepository->expects($this->once())
            ->method('findByWalletTypeAndUserId')
            ->with('default', 'user-123')
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DigitalWallet $w) {
                return $w->getUserId() === 'user-123'
                    && $w->getWalletType() === 'default'
                    && $w->getCurrency() === 'VND'
                    && $w->getBalance() === 0
                    && $w->getStatus() === 'active';
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->createWallet('user-123', 'default', 'VND');
    }

    public function testDepositThrowsForNonPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Deposit amount must be positive');

        $this->service->deposit('1', 0, 'Deposit');
    }

    public function testDepositThrowsForAmountBelowMinimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be between 1,000 and 100,000,000');

        $this->service->deposit('1', 999, 'Deposit');
    }

    public function testDepositThrowsForAmountAboveMaximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be between 1,000 and 100,000,000');

        $this->service->deposit('1', 100000001, 'Deposit');
    }

    public function testDepositFailsWhenWalletNotFound(): void
    {
        $this->walletRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wallet not found');

        $this->service->deposit('1', 10000, 'Deposit');
    }

    public function testDepositSuccess(): void
    {
        $wallet = new DigitalWallet();
        $wallet->setId(1);
        $wallet->setUserId('user-123');
        $wallet->setWalletType('default');
        $wallet->setCurrency('VND');
        $wallet->setBalance(0);
        $wallet->setStatus('active');

        $this->walletRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($wallet);

        $this->transactionRepository->expects($this->any())
            ->method('countByWalletAndDateRange')
            ->willReturn(0);

        $this->transactionRepository->expects($this->any())
            ->method('sumAmountByWalletAndDateRange')
            ->willReturn(0);

        $this->transactionRepository->expects($this->any())
            ->method('findRecentByWallet')
            ->willReturn([]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(WalletTransaction::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $transaction = $this->service->deposit('1', 10000, 'Deposit');

        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertSame('1', $transaction->getWalletId());
        $this->assertSame(10000, $transaction->getAmount());
        $this->assertSame('deposit', $transaction->getTransactionType());
        $this->assertSame('completed', $transaction->getStatus());
    }

    public function testDepositWithIdempotencyKeyReturnsExisting(): void
    {
        $wallet = new DigitalWallet();
        $wallet->setId(1);
        $wallet->setUserId('user-123');
        $wallet->setWalletType('default');
        $wallet->setCurrency('VND');
        $wallet->setBalance(0);
        $wallet->setStatus('active');

        $existingTx = new WalletTransaction();
        $existingTx->setId(1);
        $existingTx->setWalletId('1');
        $existingTx->setAmount(10000);
        $existingTx->setTransactionType('deposit');

        $idempotencyKey = new IdempotencyKey(
            'idem-key-123',
            'WalletTransaction',
            '1',
            new \DateTime('+24 hours')
        );

        $this->idempotencyRepository->expects($this->once())
            ->method('findByKey')
            ->with('idem-key-123')
            ->willReturn($idempotencyKey);

        $this->transactionRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($existingTx);

        $result = $this->service->deposit('1', 10000, 'Deposit', 'idem-key-123');

        $this->assertSame($existingTx, $result);
    }

    public function testDepositWithExpiredIdempotencyKeyCreatesNewTransaction(): void
    {
        $wallet = new DigitalWallet();
        $wallet->setId(1);
        $wallet->setUserId('user-123');
        $wallet->setWalletType('default');
        $wallet->setCurrency('VND');
        $wallet->setBalance(0);
        $wallet->setStatus('active');

        $idempotencyKey = new IdempotencyKey(
            'idem-key-123',
            'WalletTransaction',
            '1',
            new \DateTime('-1 hour')
        ); // expired

        $this->walletRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($wallet);

        $this->idempotencyRepository->expects($this->once())
            ->method('findByKey')
            ->with('idem-key-123')
            ->willReturn($idempotencyKey);

        // For expired key, the transactionRepository->find is NOT called because
        // the condition !$existing->isExpired() is false
        $this->transactionRepository->expects($this->any())
            ->method('countByWalletAndDateRange')
            ->willReturn(0);

        $this->transactionRepository->expects($this->any())
            ->method('sumAmountByWalletAndDateRange')
            ->willReturn(0);

        $this->transactionRepository->expects($this->any())
            ->method('findRecentByWallet')
            ->willReturn([]);

        $this->entityManager->expects($this->once())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->deposit('1', 10000, 'Deposit', 'idem-key-123');
    }

    public function testDepositWithOptimisticLockExceptionRetries(): void
    {
        $wallet = new DigitalWallet();
        $wallet->setId(1);
        $wallet->setUserId('user-123');
        $wallet->setWalletType('default');
        $wallet->setCurrency('VND');
        $wallet->setBalance(0);
        $wallet->setStatus('active');

        $this->walletRepository->expects($this->exactly(2))
            ->method('find')
            ->with(1)
            ->willReturn($wallet);

        $this->transactionRepository->expects($this->any())
            ->method('countByWalletAndDateRange')
            ->willReturn(0);

        $this->transactionRepository->expects($this->any())
            ->method('sumAmountByWalletAndDateRange')
            ->willReturn(0);

        $this->transactionRepository->expects($this->any())
            ->method('findRecentByWallet')
            ->willReturn([]);

        $this->entityManager->expects($this->exactly(2))
            ->method('beginTransaction');

        $flushAttempt = 0;
        $this->entityManager->expects($this->exactly(2))
            ->method('flush')
            ->willReturnCallback(function () use (&$flushAttempt) {
                $flushAttempt++;
                if ($flushAttempt === 1) {
                    throw OptimisticLockException::lockFailed(new DigitalWallet());
                }
                return null;
            });

        // rollback is called once for the first attempt's exception
        $this->entityManager->expects($this->once())
            ->method('rollback');

        $commitAttempt = 0;
        $this->entityManager->expects($this->once())
            ->method('commit')
            ->willReturnCallback(function () use (&$commitAttempt) {
                $commitAttempt++;
                return true;
            });

        // First attempt will throw and rollback, second attempt succeeds
        $transaction = $this->service->deposit('1', 10000, 'Deposit');

        $this->assertInstanceOf(WalletTransaction::class, $transaction);
    }
}
