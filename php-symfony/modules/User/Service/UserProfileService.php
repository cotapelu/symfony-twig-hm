<?php

namespace App\User\Service;

use App\User\Entity\UserProfile;
use App\User\Repository\UserProfileRepositoryInterface;

class UserProfileService
{
    public function __construct(
        private UserProfileRepositoryInterface $repository
    ) {}

    public function createProfile(string $userId)
    {
        $profile = new UserProfile($userId);
        return $this->repository->save($profile);
    }

    public function getUserProfile(string $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function getProfileByUserId(string $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function updateProfile(string $userId, array $data)
    {
        $profile = $this->repository->findByUserId($userId);
        
        if (!$profile) {
            $profile = $this->createProfile($userId);
        }

        if (isset($data['displayName'])) {
            $profile->setDisplayName($data['displayName']);
        }
        if (isset($data['bio'])) {
            $profile->setBio($data['bio']);
        }
        if (isset($data['avatar'])) {
            $profile->setAvatar($data['avatar']);
        }
        if (isset($data['email'])) {
            $profile->setEmail($data['email']);
        }
        if (isset($data['phone'])) {
            $profile->setPhone($data['phone']);
        }
        if (isset($data['address'])) {
            $profile->setAddress($data['address']);
        }
        if (isset($data['city'])) {
            $profile->setCity($data['city']);
        }
        if (isset($data['country'])) {
            $profile->setCountry($data['country']);
        }

        return $this->repository->save($profile);
    }

    public function deleteProfile(string $userId): bool
    {
        // Check profile exists first (optional semantics)
        $profile = $this->repository->findByUserId($userId);
        if (!$profile) {
            return false;
        }

        // Delete by user_id (the identifier)
        return $this->repository->delete($userId);
    }
}
