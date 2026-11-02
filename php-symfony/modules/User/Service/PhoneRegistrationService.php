<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use App\User\Entity\UserProfile;
use App\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PhoneRegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private UserIdGeneratorService $idGenerator,
        private SmsVerificationService $smsVerification,
        private PhoneValidationService $phoneValidator,
    ) {
    }

    public function register(
        string $phoneNumber,
        string $verificationCode,
        string $displayName,
        ?string $email = null,
        ?string $preferredLanguage = 'en',
        ?string $timezone = 'UTC',
    ) {
        $this->phoneValidator->validate($phoneNumber);
        $this->ensurePhoneNotExists($phoneNumber);

        if (!$this->smsVerification->verifyCode($phoneNumber, $verificationCode)) {
            throw new \InvalidArgumentException('Invalid verification code');
        }

        $user = new User(
            $this->idGenerator->generate(),
            $email,
            ['ROLE_USER']
        );

        $user->setPhoneNumber($phoneNumber);

        $randomPassword = bin2hex(random_bytes(16));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);

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

    private function ensurePhoneNotExists(string $phoneNumber)
    {
        $existingUser = $this->userRepository->findByPhoneNumber($phoneNumber);
        if ($existingUser) {
            throw new \InvalidArgumentException('Phone number already registered');
        }
    }
}
