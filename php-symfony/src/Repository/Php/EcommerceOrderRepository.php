<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\EcommerceOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EcommerceOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EcommerceOrder::class);
    }
}
