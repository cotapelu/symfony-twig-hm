<?php

declare(strict_types=1);

namespace App\Shared\Mapper;

use App\User\Entity\UserProfile;

class UserProfileMapper
{
    public static function toDoctrine(UserProfile $domain): UserProfile
    {
        $entity = new UserProfile(
            $domain->getUserId(),
            $domain->getDisplayName(),
            $domain->getAvatarUrl(),
            $domain->getStatus(),
            $domain->getBio(),
            $domain->getTimezone(),
            $domain->getLastSeen(),
            $domain->getPreferences()
        );

        return $entity;
    }

    public static function toDomain(UserProfile $entity): UserProfile
    {
        // For now, return the entity directly since they have the same interface
        // In a real domain-driven design, this would map to a domain object
        return $entity;
    }
}
