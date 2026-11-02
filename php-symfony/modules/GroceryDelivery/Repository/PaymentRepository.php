<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\GroceryDelivery\Entity\Payment;

class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function save(Payment $p): void { $this->getEntityManager()->persist($p); $this->getEntityManager()->flush(); }
}
