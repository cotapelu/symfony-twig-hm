<?php

namespace App\Command;

use App\DigitalWallet\Repository\DigitalWalletRepository;
use App\DigitalWallet\Repository\WalletTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reconcile:wallets',
    description: 'Reconcile wallet balances by comparing with sum of completed transactions'
)]
class ReconcileWalletsCommand extends Command
{
    public function __construct(
        private DigitalWalletRepository $walletRepository,
        private WalletTransactionRepository $transactionRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Wallet Balance Reconciliation');
        $wallets = $this->getAllWallets();
        $io->progressStart(count($wallets));
        $mismatches = $fixed = 0;
        foreach ($wallets as $wallet) {
            $this->reconcileWallet($wallet, $io, $mismatches, $fixed);
            $io->progressAdvance();
        }
        $io->progressFinish();
        $this->printSummary($io, count($wallets), $mismatches, $fixed);
        $io->success('Reconciliation completed');
        return Command::SUCCESS;
    }

    private function getAllWallets(): array
    {
        return $this->walletRepository->findAll();
    }

    private function calculateTransactionSum(int $walletId): int
    {
        return (int)($this->transactionRepository->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->where('t.walletId = :walletId')
            ->andWhere('t.status = :status')
            ->setParameter('walletId', $walletId)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }

    private function reconcileWallet(object $wallet, SymfonyStyle $io, int &$mismatches, int &$fixed): void
    {
        $sum = $this->calculateTransactionSum($wallet->getId());
        $balance = $wallet->getBalance();
        if ($balance !== $sum) {
            $mismatches++;
            $io->writeln(sprintf(
                '  <error>Mismatch</error>: Wallet ID %s, User %s, Balance %d, Sum %d',
                $wallet->getId(),
                $wallet->getUserId(),
                $balance,
                $sum
            ));
            $wallet->setBalance($sum);
            $this->entityManager->flush();
            $fixed++;
        }
    }

    private function printSummary(SymfonyStyle $io, int $total, int $mismatches, int $fixed): void
    {
        $io->section('Summary');
        $io->writeln(sprintf('Total wallets: %d', $total));
        $io->writeln(sprintf('Mismatches found: %d', $mismatches));
        $io->writeln(sprintf('Fixed automatically: %d', $fixed));
    }
}
