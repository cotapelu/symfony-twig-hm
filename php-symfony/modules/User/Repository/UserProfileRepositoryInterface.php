<?php

namespace App\User\Repository;

use App\User\Entity\UserProfile;

interface UserProfileRepositoryInterface
{
    /**
     * Legacy method: find by numeric ID (not used in new code, kept for compatibility).
     * Note: The UserProfile entity uses user_id (string) as primary key, so this method
     * may return null as there is no integer id column.
     */
    public function findById(int $id): ?UserProfile;

    public function findByUserId(string $userId): ?UserProfile;
    public function save(UserProfile $profile): UserProfile;
    public function delete(string $userId): bool;
}
