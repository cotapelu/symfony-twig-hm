<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\EcommerceTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EcommerceTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EcommerceTransaction::class);
    }

    public function findByOrderId(string $orderId): ?EcommerceTransaction
    {
        return $this->findOneBy(['orderId' => $orderId]);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findByPaymentMethod(string $paymentMethod): array
    {
        return $this->findBy(['paymentMethod' => $paymentMethod]);
    }

    public function findSuccessfulTransactions(): array
    {
        return $this->findBy(['status' => 'COMPLETED']);
    }
}