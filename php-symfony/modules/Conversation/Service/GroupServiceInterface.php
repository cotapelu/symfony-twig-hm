<?php

declare(strict_types=1);

namespace App\Conversation\Service;

interface GroupServiceInterface
{
    /**
     * Get detailed information about a group including messages.
     */
    public function getGroupDetails(string $groupId, string $userId): array;

    /**
     * Remove a user from a group.
     */
    public function leaveGroup(string $groupId, string $userId): void;

    /**
     * Add a participant to a group.
     */
    public function addParticipant(string $groupId, string $creatorId, string $newUserId): void;
}
