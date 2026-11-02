<?php declare(strict_types=1);

namespace App\Tests\Unit\DigitalWallet;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\DigitalWallet\Entity\DigitalWallet;
use App\DigitalWallet\Entity\WalletTransaction;
use App\DigitalWallet\Service\DigitalWalletService;
use App\DigitalWallet\Repository\DigitalWalletRepository;
use App\DigitalWallet\Repository\WalletTransactionRepository;
use App\DigitalWallet\Repository\IdempotencyKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DigitalWalletServiceTest extends TestCase
{
    private DigitalWalletService $service;
    private EntityManagerInterface $em;
    private DigitalWalletRepository $walletRepo;
    private WalletTransactionRepository $txRepo;
    private IdempotencyKeyRepository $idempotencyRepo;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->walletRepo = $this->createMock(DigitalWalletRepository::class);
        $this->txRepo = $this->createMock(WalletTransactionRepository::class);
        $this->idempotencyRepo = $this->createMock(IdempotencyKeyRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new DigitalWalletService(
            $this->em,
            $this->walletRepo,
            $this->txRepo,
            $this->idempotencyRepo,
            $this->logger
        );
    }

    private function createWallet(int $id, string $userId, int $balance = 0, string $currency = 'VND', string $status = 'active'): DigitalWallet
    {
        $wallet = new DigitalWallet();
        $wallet->setId($id);
        $wallet->setUserId($userId);
        $wallet->setBalance($balance);
        $wallet->setCurrency($currency);
        $wallet->setStatus($status);
        return $wallet;
    }

    public function testDepositAddsBalanceAndCreatesTransaction(): void
    {
        $walletId = 1;
        $userId = 'user-1';
        $amount = 10000;

        $wallet = $this->createWallet($walletId, $userId, 5000);

        $this->walletRepo->expects($this->exactly(2))->method('find')->with((string)$walletId)->willReturn($wallet);
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');
        $this->em->expects($this->never())->method('rollback');

        $tx = $this->service->deposit((string)$walletId, $amount, 'Test deposit', null, $userId);

        $this->assertInstanceOf(WalletTransaction::class, $tx);
        $this->assertEquals($amount, $tx->getAmount());
        $this->assertEquals('deposit', $tx->getTransactionType());
        $this->assertEquals(15000, $wallet->getBalance());
    }

    public function testDepositFailsWithInvalidAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->deposit('1', -1000, 'Test', null, 'user-1');
    }

    public function testWithdrawDeductsBalance(): void
    {
        $walletId = 1;
        $userId = 'user-1';
        $amount = 3000;

        $wallet = $this->createWallet($walletId, $userId, 10000);

        $this->walletRepo->expects($this->exactly(2))->method('find')->with((string)$walletId)->willReturn($wallet);
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $tx = $this->service->withdraw((string)$walletId, $amount, 'Test withdraw', null, $userId);

        $this->assertEquals(-$amount, $tx->getAmount());
        $this->assertEquals(7000, $wallet->getBalance());
    }

    public function testWithdrawFailsIfInsufficientBalance(): void
    {
        $walletId = 1;
        $userId = 'user-1';
        $amount = 15000;

        $wallet = $this->createWallet($walletId, $userId, 5000);

        $this->walletRepo->expects($this->exactly(2))->method('find')->with((string)$walletId)->willReturn($wallet);
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        $this->service->withdraw((string)$walletId, $amount, 'Test', null, $userId);
    }

    public function testTransferMovesFundsBetweenWallets(): void
    {
        $fromWalletId = 1;
        $toWalletId = 2;
        $userId = 'user-1';
        $amount = 5000;

        $fromWallet = $this->createWallet($fromWalletId, $userId, 20000);
        $toWallet = $this->createWallet($toWalletId, 'user-2', 1000);

        // 3 find calls: ensureOwnership (from), inside tx (from), inside tx (to)
        $this->walletRepo->expects($this->exactly(3))->method('find')
            ->willReturnMap([
                [(string)$fromWalletId, $fromWallet],
                [(string)$fromWalletId, $fromWallet],
                [(string)$toWalletId, $toWallet],
            ]);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        [$fromTx, $toTx] = $this->service->transfer((string)$fromWalletId, (string)$toWalletId, $amount, 'Transfer', null, $userId);

        $this->assertEquals('transfer_out', $fromTx->getTransactionType());
        $fee = (int)($amount * 0.01);
        $this->assertEquals(-($amount + $fee), $fromTx->getAmount());
        $this->assertEquals('transfer_in', $toTx->getTransactionType());
        $this->assertEquals($amount, $toTx->getAmount());
        $this->assertEquals(20000 - $amount - $fee, $fromWallet->getBalance());
        $this->assertEquals(1000 + $amount, $toWallet->getBalance());
    }

    public function testTransferFailsIfSameWallet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transfer to the same wallet');
        $this->service->transfer('1', '1', 1000, 'Transfer', null, 'user-1');
    }

    public function testOwnershipCheckThrowsIfNotOwner(): void
    {
        $walletId = 1;
        $userId = 'user-1';
        $otherUserId = 'user-2';

        $wallet = $this->createWallet($walletId, $otherUserId, 1000);

        $this->walletRepo->expects($this->once())->method('find')->with((string)$walletId)->willReturn($wallet);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->deposit((string)$walletId, 1000, 'Test', null, $userId);
    }

    public function testIdempotencyKeyReturnsExistingTransaction(): void
    {
        $walletId = 1;
        $userId = 'user-1';
        $amount = 5000;
        $idempotencyKey = 'idemp-key-123';

        $wallet = $this->createWallet($walletId, $userId, 10000);

        $existingTx = new WalletTransaction();
        $existingTx->setId(123);
        $existingTx->setWalletId((string)$walletId);
        $existingTx->setAmount($amount);
        $existingTx->setTransactionType('deposit');

        $idempotencyEntity = new \App\DigitalWallet\Entity\IdempotencyKey(
            $idempotencyKey,
            'WalletTransaction',
            '123',
            new \DateTime('+1 hour')
        );

        $this->walletRepo->expects($this->once())->method('find')->with((string)$walletId)->willReturn($wallet);
        $this->idempotencyRepo->expects($this->once())->method('findByKey')->with($idempotencyKey)->willReturn($idempotencyEntity);
        $this->txRepo->expects($this->once())->method('find')->with('123')->willReturn($existingTx);
        $this->em->expects($this->never())->method('beginTransaction');

        $result = $this->service->deposit((string)$walletId, $amount, 'Test', $idempotencyKey, $userId);

        $this->assertSame($existingTx, $result);
    }

    public function testGetBalanceChecksOwnership(): void
    {
        $walletId = 1;
        $userId = 'user-1';
        $balance = 15000;

        $wallet = $this->createWallet($walletId, $userId, $balance);

        $this->walletRepo->expects($this->exactly(2))->method('find')->with((string)$walletId)->willReturn($wallet);

        $result = $this->service->getBalance((string)$walletId, $userId);

        $this->assertEquals($balance, $result);
    }

    public function testGetBalanceFailsIfNotOwner(): void
    {
        $walletId = 1;
        $userId = 'user-1';
        $otherUserId = 'user-2';

        $wallet = $this->createWallet($walletId, $otherUserId, 1000);

        $this->walletRepo->expects($this->once())->method('find')->with((string)$walletId)->willReturn($wallet);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->getBalance((string)$walletId, $userId);
    }

    public function testGetTransactionHistoryChecksOwnership(): void
    {
        $walletId = 1;
        $userId = 'user-1';
        $transactions = [new WalletTransaction()];

        $wallet = $this->createWallet($walletId, $userId, 0);

        $this->walletRepo->expects($this->once())->method('find')->with((string)$walletId)->willReturn($wallet);
        $this->txRepo->expects($this->once())->method('findByWalletIdWithLimit')
            ->with($this->equalTo((string)$walletId), $this->equalTo(20), $this->equalTo(0))
            ->willReturn($transactions);

        $result = $this->service->getTransactionHistory((string)$walletId, 20, 0, $userId);

        $this->assertEquals($transactions, $result);
    }
}
