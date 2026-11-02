<?php

declare(strict_types=1);

namespace App\Poll\Repository;

use App\Poll\Entity\PollVote;
use Doctrine\ORM\EntityManagerInterface;

class PollVoteRepositoryImpl implements PollVoteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function save(PollVote $vote): void
    {
        $this->entityManager->persist($vote);
        $this->entityManager->flush();
    }

    public function findByPollIdAndUserId(string $pollId, string $userId): ?PollVote
    {
        return $this->entityManager->getRepository(PollVote::class)->findOneBy([
            'pollId' => $pollId,
            'userId' => $userId,
        ]);
    }

    public function hasUserVoted(string $pollId, string $userId): bool
    {
        return null !== $this->findByPollIdAndUserId($pollId, $userId);
    }

    public function countVotesByPollId(string $pollId): int
    {
        return $this->entityManager->getRepository(PollVote::class)->count([
            'pollId' => $pollId,
        ]);
    }
}
