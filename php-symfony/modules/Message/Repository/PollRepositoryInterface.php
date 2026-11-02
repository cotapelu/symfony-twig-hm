<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\Poll;

interface PollRepositoryInterface
{
    public function save(Poll $poll): void;

    public function findById(string $id): ?Poll;

    public function findByMessageId(string $messageId): ?Poll;

    public function findActivePolls(): array;
}
