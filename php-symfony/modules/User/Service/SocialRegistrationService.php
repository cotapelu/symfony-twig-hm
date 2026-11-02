<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use App\User\Entity\UserProfile;
use App\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SocialRegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private UserIdGeneratorService $idGenerator,
    ) {
    }

    public function register(string $provider, string $socialId, string $email, string $displayName, ?string $avatarUrl = null, ?string $preferredLanguage = 'en', ?string $timezone = 'UTC'): User {
        $existingUser = $this->userRepository->findBySocial($provider, $socialId);
        if ($existingUser) { throw new \InvalidArgumentException('Social account already linked'); }

        $existingEmailUser = $this->userRepository->findByEmail($email);
        if ($existingEmailUser) {
            $existingEmailUser->setSocialProvider($provider);
            $existingEmailUser->setSocialId($socialId);
            $this->entityManager->flush();
            return $existingEmailUser;
        }

        return $this->createUserFromSocial($provider, $socialId, $email, $displayName, $avatarUrl, $preferredLanguage, $timezone);
    }

    private function createUserFromSocial(string $provider, string $socialId, string $email, string $displayName, ?string $avatarUrl, string $preferredLanguage, string $timezone): User {
        $user = new User($this->idGenerator->generate(), $email, ['ROLE_USER']);
        $user->setSocialProvider($provider);
        $user->setSocialId($socialId);
        $user->setPreferredLanguage($preferredLanguage);

        $randomPassword = bin2hex(random_bytes(16));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) { throw new \InvalidArgumentException((string) $errors); }

        $userProfile = new UserProfile($user->getId(), $displayName, $avatarUrl ?? null, 'offline', null, $timezone, null, []);
        $this->entityManager->persist($userProfile);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
