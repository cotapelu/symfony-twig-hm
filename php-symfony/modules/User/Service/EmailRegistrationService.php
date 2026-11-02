<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use App\User\Entity\UserProfile;
use App\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailRegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private UserIdGeneratorService $idGenerator,
        private PasswordValidationService $passwordValidator,
    ) {
    }

    public function register(
        string $email,
        string $password,
        string $displayName,
        ?string $phoneNumber = null,
        ?string $preferredLanguage = 'en',
        ?string $timezone = 'UTC',
    ) {
        $this->validateEmail($email);
        $this->ensureEmailNotExists($email);
        $this->passwordValidator->validate($password);

        $user = new User(
            $this->idGenerator->generate(),
            $email,
            ['ROLE_USER']
        );

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        if ($phoneNumber) {
            $user->setPhoneNumber($phoneNumber);
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $userProfile = new UserProfile(
            $user->getId(),
            $displayName,
            null,
            'offline',
            null,
            $timezone,
            null,
            []
        );
        $this->entityManager->persist($userProfile);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function validateEmail(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }
    }

    private function ensureEmailNotExists(string $email)
    {
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            throw new \InvalidArgumentException('Email already registered');
        }
    }
}
