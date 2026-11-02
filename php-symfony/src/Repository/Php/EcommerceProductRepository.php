<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\EcommerceProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EcommerceProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EcommerceProduct::class);
    }
}
