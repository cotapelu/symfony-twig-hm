<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\ProcedureApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProcedureApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProcedureApplication::class);
    }
}
