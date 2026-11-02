<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\DeliveryPerson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeliveryPersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryPerson::class);
    }

    public function findByUserId(string $userId): ?DeliveryPerson
    {
        return $this->findOneBy(['userId' => $userId]);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findAvailable(): array
    {
        return $this->findBy(['status' => 'ONLINE', 'isAvailable' => true]);
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type]);
    }
}