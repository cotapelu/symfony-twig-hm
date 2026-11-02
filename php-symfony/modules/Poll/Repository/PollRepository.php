<?php

declare(strict_types=1);

namespace App\Poll\Repository;

use App\Poll\Entity\Poll;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Poll::class);
    }

    public function findActivePolls(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.expiresAt IS NULL OR p.expiresAt > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByConversation(string $conversationId): array
    {
        return $this->findBy(['conversationId' => $conversationId]);
    }
}
