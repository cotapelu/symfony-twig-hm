<?php

declare(strict_types=1);

namespace App\DigitalWallet\Service;

use App\DigitalWallet\Entity\DigitalWallet;
use App\DigitalWallet\Entity\WalletTransaction;
use App\DigitalWallet\Entity\IdempotencyKey;
use App\DigitalWallet\Repository\DigitalWalletRepository;
use App\DigitalWallet\Repository\WalletTransactionRepository;
use App\DigitalWallet\Repository\IdempotencyKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;

class DigitalWalletService
{
    private EntityManagerInterface $entityManager;
    private DigitalWalletRepository $walletRepository;
    private WalletTransactionRepository $transactionRepository;
    private IdempotencyKeyRepository $idempotencyRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        DigitalWalletRepository $walletRepository,
        WalletTransactionRepository $transactionRepository,
        IdempotencyKeyRepository $idempotencyRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->walletRepository = $walletRepository;
        $this->transactionRepository = $transactionRepository;
        $this->idempotencyRepository = $idempotencyRepository;
        $this->logger = $logger;
    }

    public function createWallet(string $userId, string $walletType, string $currency = 'VND')
    {
        $existing = $this->walletRepository->findByWalletTypeAndUserId($walletType, $userId);
        if ($existing) return $existing;

        $wallet = new DigitalWallet();
        $wallet->setUserId($userId);
        $wallet->setWalletType($walletType);
        $wallet->setCurrency($currency);
        $wallet->setBalance(0);
        $wallet->setStatus('active');

        $this->entityManager->persist($wallet);
        $this->entityManager->flush();
        $this->logger->info('Digital wallet created', ['userId' => $userId, 'walletType' => $walletType]);

        return $wallet;
    }

    public function deposit(string $walletId, int $amount, string $description = 'Deposit', ?string $idempotencyKey = null, ?string $userId = null)
    {
        $this->validateAmount($amount, 'Deposit');
        if ($userId !== null) {
            $this->ensureOwnership($walletId, $userId);
        }
        $this->checkDailyLimits($walletId, $amount);

        if ($idempotencyKey && $existing = $this->findIdempotencyTransaction($idempotencyKey)) {
            return $existing;
        }

        return $this->executeInTransaction(function () use ($walletId, $amount, $description) {
            $wallet = $this->walletRepository->find($walletId);
            if (!$wallet) throw new \Exception('Wallet not found');
            return $this->createDepositTransaction($wallet, $walletId, $amount, $description);
        }, $idempotencyKey, 'WalletTransaction');
    }

    public function withdraw(string $walletId, int $amount, string $description = 'Withdrawal', ?string $idempotencyKey = null, ?string $userId = null)
    {
        $this->validateAmount($amount, 'Withdrawal');
        if ($userId !== null) {
            $this->ensureOwnership($walletId, $userId);
        }
        $this->checkDailyLimits($walletId, $amount);

        if ($idempotencyKey && $existing = $this->findIdempotencyTransaction($idempotencyKey)) {
            return $existing;
        }

        return $this->executeInTransaction(function () use ($walletId, $amount, $description) {
            $wallet = $this->walletRepository->find($walletId);
            if (!$wallet) throw new \Exception('Wallet not found');
            if ($wallet->getBalance() < $amount) throw new \Exception('Insufficient balance');
            return $this->createWithdrawTransaction($wallet, $walletId, $amount, $description);
        }, $idempotencyKey, 'WalletTransaction');
    }

    public function transfer(string $fromWalletId, string $toWalletId, int $amount, string $description = 'Transfer', ?string $idempotencyKey = null, ?string $userId = null)
    {
        if ($fromWalletId === $toWalletId) {
            throw new \InvalidArgumentException('Cannot transfer to the same wallet');
        }

        $this->validateAmount($amount, 'Transfer');
        if ($userId !== null) {
            $this->ensureOwnership($fromWalletId, $userId);
        }
        $this->checkDailyLimits($fromWalletId, $amount + $this->calculateFee($amount));

        if ($idempotencyKey && $existing = $this->findIdempotencyTransfer($idempotencyKey)) {
            return $existing;
        }

        return $this->executeInTransaction(function () use ($fromWalletId, $toWalletId, $amount, $description) {
            $fromWallet = $this->walletRepository->find($fromWalletId);
            if (!$fromWallet) throw new \Exception('Sender wallet not found');
            $toWallet   = $this->walletRepository->find($toWalletId);
            if (!$toWallet) throw new \Exception('Recipient wallet not found');

            $fee = $this->calculateFee($amount);
            $total = $amount + $fee;
            if ($fromWallet->getBalance() < $total) throw new \Exception('Insufficient balance');

            $fromTx = $this->createTransferOutTransaction($fromWallet, $toWallet, $fromWalletId, $toWalletId, $amount, $fee, $description);
            $toTx   = $this->createTransferInTransaction($toWallet, $fromWallet, $toWalletId, $fromWalletId, $amount, $description, $fromTx->getReferenceId());

            return [$fromTx, $toTx];
        }, $idempotencyKey, 'WalletTransfer');
    }

    public function getTransactionHistory(string $walletId, int $limit = 50, int $offset = 0, ?string $userId = null)
    {
        if ($userId !== null) {
            $this->ensureOwnership($walletId, $userId);
        }
        return $this->transactionRepository->findByWalletIdWithLimit($walletId, $limit, $offset);
    }

    public function getBalance(string $walletId, ?string $userId = null)
    {
        if ($userId !== null) {
            $this->ensureOwnership($walletId, $userId);
        }
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet) throw new \Exception('Wallet not found');
        return $wallet->getBalance();
    }

    public function isWalletOwnedBy(string $walletId, string $userId)
    {
        $wallet = $this->walletRepository->find($walletId);
        return $wallet && $wallet->getUserId() === $userId;
    }

    public function walletExists(string $walletId)
    {
        return $this->walletRepository->find($walletId) !== null;
    }

    private function validateAmount(int $amount, string $operation)
    {
        if ($amount <= 0) throw new \InvalidArgumentException("$operation amount must be positive");
        if ($amount < 1000 || $amount > 100000000) throw new \InvalidArgumentException('Amount must be between 1,000 and 100,000,000');
    }

    private function ensureOwnership(string $walletId, string $userId): void
    {
        if (!$this->isWalletOwnedBy($walletId, $userId)) {
            $this->logger->warning('Wallet ownership check failed', [
                'walletId' => $walletId,
                'userId' => $userId,
            ]);
            throw new \InvalidArgumentException('Access denied');
        }
    }

    private function findIdempotencyTransaction(string $key)
    {
        $existing = $this->idempotencyRepository->findByKey($key);
        if ($existing && !$existing->isExpired() && $existing->getEntityType() === 'WalletTransaction') {
            return $this->transactionRepository->find($existing->getEntityId());
        }
        return null;
    }

    private function findIdempotencyTransfer(string $key)
    {
        $existing = $this->idempotencyRepository->findByKey($key);
        if (!$existing || $existing->isExpired() || $existing->getEntityType() !== 'WalletTransfer') {
            return null;
        }
        $ids = json_decode($existing->getEntityId(), true);
        if (!is_array($ids) || !isset($ids['from'], $ids['to'])) {
            return null;
        }
        $fromTx = $this->transactionRepository->find($ids['from']);
        $toTx = $this->transactionRepository->find($ids['to']);
        if ($fromTx && $toTx) {
            return [$fromTx, $toTx];
        }
        return null;
    }

    private function executeInTransaction(callable $operation, ?string $idempotencyKey, string $entityType)
    {
        $maxRetries = 3;
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                $result = $this->executeTransactionWithLock($operation, $idempotencyKey, $entityType);
                return $result;
            } catch (OptimisticLockException $e) {
                $this->entityManager->rollback();
                if ($attempt === $maxRetries - 1) {
                    throw new \Exception('Operation failed due to concurrent updates. Please try again.', 0, $e);
                }
                usleep(10000 * ($attempt + 1));
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        }
        throw new \Exception('Operation failed after retries');
    }

    private function executeTransactionWithLock(callable $operation, ?string $idempotencyKey, string $entityType)
    {
        $this->entityManager->beginTransaction();
        try {
            $result = $operation();
            $this->entityManager->flush();
            $this->entityManager->commit();
            if ($idempotencyKey) {
                $this->storeIdempotency($idempotencyKey, $entityType, $result);
            }
            return $result;
        } catch (\Exception $e) {
            // Let outer handler deal with rollback
            throw $e;
        }
    }

    private function createDepositTransaction(DigitalWallet $wallet, string $walletId, int $amount, string $description)
    {
        $tx = new WalletTransaction();
        $tx->setWalletId($walletId);
        $tx->setTransactionType('deposit');
        $tx->setAmount($amount);
        $tx->setCurrency($wallet->getCurrency());
        $tx->setStatus('completed');
        $tx->setDescription($description);
        $tx->setReferenceId($this->generateReferenceId());

        $wallet->addBalance($amount);
        $this->entityManager->persist($tx);

        return $tx;
    }

    private function createWithdrawTransaction(DigitalWallet $wallet, string $walletId, int $amount, string $description)
    {
        $tx = new WalletTransaction();
        $tx->setWalletId($walletId);
        $tx->setTransactionType('withdraw');
        $tx->setAmount(-$amount);
        $tx->setCurrency($wallet->getCurrency());
        $tx->setStatus('completed');
        $tx->setDescription($description);
        $tx->setReferenceId($this->generateReferenceId());

        $wallet->deductBalance($amount);
        $this->entityManager->persist($tx);

        return $tx;
    }

    private function createTransferOutTransaction(DigitalWallet $fromWallet, DigitalWallet $toWallet, string $fromWalletId, string $toWalletId, int $amount, int $fee, string $description)
    {
        $tx = new WalletTransaction();
        $tx->setWalletId($fromWalletId);
        $tx->setTransactionType('transfer_out');
        $tx->setAmount(-($amount + $fee));
        $tx->setCurrency($fromWallet->getCurrency());
        $tx->setStatus('completed');
        $tx->setDescription($description);
        $tx->setReferenceId($this->generateReferenceId());
        $tx->setRecipientWalletId($toWalletId);
        $tx->setFee($fee);

        $fromWallet->deductBalance($amount + $fee);
        $this->entityManager->persist($tx);

        return $tx;
    }

    private function createTransferInTransaction(DigitalWallet $toWallet, DigitalWallet $fromWallet, string $toWalletId, string $fromWalletId, int $amount, string $description, string $referenceId)
    {
        $tx = new WalletTransaction();
        $tx->setWalletId($toWalletId);
        $tx->setTransactionType('transfer_in');
        $tx->setAmount($amount);
        $tx->setCurrency($toWallet->getCurrency());
        $tx->setStatus('completed');
        $tx->setDescription($description);
        $tx->setReferenceId($referenceId);
        $tx->setRecipientWalletId($fromWalletId);

        $toWallet->addBalance($amount);
        $this->entityManager->persist($tx);

        return $tx;
    }

    private function calculateFee(int $amount)
    {
        return (int)($amount * 0.01);
    }

    private function storeIdempotency(string $key, string $entityType, $entity)
    {
        $expires = new \DateTime('+24 hours');
        $entityId = is_array($entity) ? json_encode(array_map('strval', $entity)) : (string)$entity->getId();
        $idempKey = new IdempotencyKey($key, $entityType, $entityId, $expires);
        $this->idempotencyRepository->save($idempKey);
    }

    private function generateReferenceId()
    {
        return 'TXN-' . strtoupper(uniqid());
    }

    private function checkDailyLimits(string $walletId, int $amount)
    {
        $today = new \DateTime('today');
        $start = (clone $today)->setTime(0, 0, 0);
        $end = (clone $today)->setTime(23, 59, 59);

        $count = $this->transactionRepository->countByWalletAndDateRange($walletId, $start, $end);
        if ($count >= 50) throw new \Exception('Daily transaction limit exceeded (max 50)');

        $sum = $this->transactionRepository->sumAmountByWalletAndDateRange($walletId, $start, $end);
        if ($sum + $amount > 100000000) throw new \Exception('Daily amount limit exceeded (max 100,000,000 VNĐ)');

        $this->checkVelocity($walletId);
    }

    private function checkVelocity(string $walletId)
    {
        $recent = $this->transactionRepository->findRecentByWallet($walletId, 5);
        if (count($recent) < 3) return;

        $now = new \DateTime();
        $firstTxTime = $recent[count($recent) - 1]->getCreatedAt();
        $timeDiff = $now->getTimestamp() - $firstTxTime->getTimestamp();

        if ($timeDiff < 60 && count($recent) >= 5) {
            $this->logger->warning('Velocity check triggered: rapid transactions detected', [
                'walletId' => $walletId,
                'txCount' => count($recent),
                'timeWindow' => $timeDiff
            ]);
            throw new \Exception('Suspicious activity detected: too many transactions in short time');
        }
    }
}
