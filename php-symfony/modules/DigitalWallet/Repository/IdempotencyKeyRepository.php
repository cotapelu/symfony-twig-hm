<?php declare(strict_types=1);

namespace App\DigitalWallet\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\DigitalWallet\Entity\IdempotencyKey;

class IdempotencyKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IdempotencyKey::class);
    }

    public function findByKey(string $key): ?IdempotencyKey
    {
        return $this->findOneBy(['key' => $key]);
    }

    public function save(IdempotencyKey $key): void
    {
        $this->getEntityManager()->persist($key);
        $this->getEntityManager()->flush();
    }
}
