<?php

declare(strict_types=1);

namespace App\Mapper;

use App\User\Entity\UserProfile;

/**
 * Mapper to convert between UserProfile domain entity and Doctrine entity.
 */
class UserProfileMapper
{
    /**
     * Convert domain UserProfile to Doctrine entity.
     */
    public static function toDoctrine(UserProfile $profile): \App\User\Entity\UserProfile
    {
        $entity = new \App\User\Entity\UserProfile(
            userId: $profile->getUserId(),
            displayName: $profile->getDisplayName(),
            avatarUrl: $profile->getAvatarUrl(),
            bio: $profile->getBio(),
        );
        // Set additional properties via setters
        $entity->setPhoneNumber($profile->getPhoneNumber());
        $entity->setDateOfBirth($profile->getDateOfBirth());
        $entity->setGender($profile->getGender());
        $entity->setLocation($profile->getLocation());
        $entity->setWebsite($profile->getWebsite());
        $entity->setLanguage($profile->getLanguage());
        $entity->setTimezone($profile->getTimezone());
        $entity->setIsPublic($profile->isPublic());
        $entity->setCreatedAt($profile->getCreatedAt());
        $entity->setUpdatedAt($profile->getUpdatedAt());

        return $entity;
    }

    /**
     * Convert Doctrine entity to domain UserProfile.
     */
    public static function toDomain(\App\User\Entity\UserProfile $entity): UserProfile
    {
        $profile = new UserProfile($entity->getUserId(),$entity->getDisplayName(),$entity->getBio(),$entity->getAvatarUrl());
        $m=['setPhoneNumber'=>'getPhoneNumber','setDateOfBirth'=>'getDateOfBirth','setGender'=>'getGender','setLocation'=>'getLocation','setWebsite'=>'getWebsite','setLanguage'=>'getLanguage','setTimezone'=>'getTimezone','setIsPublic'=>'isPublic','setCreatedAt'=>'getCreatedAt','setUpdatedAt'=>'getUpdatedAt'];
        foreach($m as $s=>$g){if(method_exists($profile,$s)&&method_exists($entity,$g))$profile->$s($entity->$g());}
        return $profile;
    }
}
