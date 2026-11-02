<?php

namespace App\DigitalWallet\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\DigitalWallet\Entity\DigitalWallet;

class DigitalWalletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DigitalWallet::class);
    }

    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.user', 'u')
            ->addSelect('u')
            ->andWhere('w.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByUserId(string $userId): ?DigitalWallet
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.userId = :userId')
            ->andWhere('w.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByWalletTypeAndUserId(string $walletType, string $userId): ?DigitalWallet
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.walletType = :walletType')
            ->andWhere('w.userId = :userId')
            ->setParameter('walletType', $walletType)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOrFail(string $id, string $message = 'Wallet not found'): DigitalWallet
    {
        $wallet = $this->find($id);
        if (!$wallet) throw new \Exception($message);
        return $wallet;
    }
}
