<?php

declare(strict_types=1);

namespace App\Poll\Repository;

use App\Poll\Entity\Poll;
use Doctrine\ORM\EntityManagerInterface;

class PollRepositoryImpl implements PollRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function save(Poll $poll): void
    {
        $this->entityManager->persist($poll);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Poll
    {
        return $this->entityManager->getRepository(Poll::class)->find($id);
    }

    /** @return Poll[] */
    public function findByConversationId(string $conversationId): array
    {
        return $this->entityManager->getRepository(Poll::class)->findBy([
            'conversationId' => $conversationId,
        ]);
    }

    public function findByMessageId(string $messageId): ?Poll
    {
        return $this->entityManager->getRepository(Poll::class)->findOneBy([
            'messageId' => $messageId,
        ]);
    }

    public function delete(string $id): void
    {
        $poll = $this->findById($id);
        if ($poll) {
            $this->entityManager->remove($poll);
            $this->entityManager->flush();
        }
    }
}
