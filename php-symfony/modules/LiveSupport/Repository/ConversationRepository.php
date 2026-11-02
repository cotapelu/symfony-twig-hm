<?php

declare(strict_types=1);

namespace App\LiveSupport\Repository;

use App\LiveSupport\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByParticipant(string $userId): array
    {
        return $this->createQueryBuilder('c')
            ->where(':userId MEMBER OF c.participants')
            ->setParameter('userId', $userId)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAgent(int $agentId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.assignedAgentId = :agentId')
            ->setParameter('agentId', $agentId)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Conversation $conversation): void
    {
        $this->getEntityManager()->persist($conversation);
        $this->getEntityManager()->flush();
    }

    public function delete(Conversation $conversation): void
    {
        $this->getEntityManager()->remove($conversation);
        $this->getEntityManager()->flush();
    }
}
