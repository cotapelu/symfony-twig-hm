<?php

declare(strict_types=1);

namespace App\LiveSupport\Repository;

use App\LiveSupport\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findByConversation(int $conversationId, int $limit = 100, int $offset = 0, ?\DateTime $since = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversationId')
            ->setParameter('conversationId', $conversationId)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($since !== null) {
            $qb->andWhere('m.createdAt > :since')
               ->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByConversation(int $conversationId): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.conversation = :conversationId')
            ->setParameter('conversationId', $conversationId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Message $message): void
    {
        $this->getEntityManager()->persist($message);
        $this->getEntityManager()->flush();
    }

    public function delete(Message $message): void
    {
        $this->getEntityManager()->remove($message);
        $this->getEntityManager()->flush();
    }
}
