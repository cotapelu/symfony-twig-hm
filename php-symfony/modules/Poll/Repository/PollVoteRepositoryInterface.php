<?php

declare(strict_types=1);

namespace App\Poll\Repository;

use App\Poll\Entity\PollVote;

interface PollVoteRepositoryInterface
{
    public function save(PollVote $vote): void;
    public function findByPollIdAndUserId(string $pollId, string $userId): ?PollVote;
    public function hasUserVoted(string $pollId, string $userId): bool;
    public function countVotesByPollId(string $pollId): int;
}
