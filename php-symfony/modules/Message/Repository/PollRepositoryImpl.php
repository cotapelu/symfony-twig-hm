<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\Poll;
use Doctrine\ORM\EntityManagerInterface;

class PollRepositoryImpl implements PollRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Poll $poll): void
    {
        $this->entityManager->persist($poll);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Poll
    {
        return $this->entityManager->find(Poll::class, $id);
    }

    public function findByMessageId(string $messageId): ?Poll
    {
        return $this->entityManager->getRepository(Poll::class)->findOneBy(['messageId' => $messageId]);
    }

    public function findActivePolls(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Poll::class, 'p')
            ->where('p.isActive = :active')
            ->andWhere('p.expiresAt IS NULL OR p.expiresAt > :now')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}
