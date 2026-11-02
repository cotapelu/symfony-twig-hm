<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use App\User\Entity\UserVerification;
use App\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * User Registration Service
 * Handles user registration with email, phone, social login, and biometric options.
 */
class UserRegistrationService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private ?SmsVerificationService $smsVerificationService = null,
    ) {
    }

    /**
     * Generate cryptographically secure user ID.
     * SECURITY: Replaces uniqid() which is not cryptographically secure.
     * uniqid() uses microtime which is predictable and vulnerable to timing attacks.
     * Using random_bytes() provides cryptographic strength.
     */
    private function generateSecureUserId()
    {
        return 'user_' . bin2hex(random_bytes(16));
    }

    /**
     * Register user with email and password.
     */
    public function registerWithEmail(
        string $email,
        string $password,
        string $displayName,
        ?string $phoneNumber = null,
        ?string $preferredLanguage = 'en',
        ?string $timezone = 'UTC',
    ): User {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }
        $this->validatePasswordStrength($password);
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            throw new \InvalidArgumentException('Email already registered');
        }
        $user = $this->buildUserFromEmail($email, $password, $displayName, $phoneNumber);
        $this->validateAndPersistUser($user);
        return $user;
    }



    private function buildUserFromEmail(string $email, string $password, string $displayName, ?string $phoneNumber): User
    {
        $user = new User($this->generateSecureUserId(), $email, ['ROLE_USER']);
        $verification = new \App\User\Entity\UserVerification($user->getId());
        $user->setVerification($verification);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        if ($phoneNumber) {
            $user->setPhoneNumber($phoneNumber);
        }
        $userProfile = new \App\User\Entity\UserProfile($user->getId());
        $userProfile->setDisplayName($displayName);
        $user->setProfile($userProfile);
        $this->entityManager->persist($userProfile);
        return $user;
    }

    private function validateAndPersistUser(User $user): void
    {
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Register user with phone number.
     */
    public function registerWithPhone(
        string $phoneNumber,
        string $verificationCode,
        string $displayName,
        ?string $email = null,
        ?string $preferredLanguage = 'en',
        ?string $timezone = 'UTC',
    ) {
        $this->validatePhoneRegistration($phoneNumber, $verificationCode);
        $existingUser = $this->userRepository->findByPhoneNumber($phoneNumber);
        if ($existingUser) {
            throw new \InvalidArgumentException('Phone number already registered');
        }
        $user = $this->createUserFromPhone($phoneNumber, $email, $displayName);
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }

    private function validatePhoneRegistration(string $phoneNumber, string $verificationCode): void
    {
        if (!$this->isValidPhoneNumber($phoneNumber)) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }
        if ($this->smsVerificationService) {
            if (!$this->smsVerificationService->verifyCode($phoneNumber, $verificationCode)) {
                throw new \InvalidArgumentException('Invalid verification code');
            }
        } else {
            if (!$this->verifySmsCode($phoneNumber, $verificationCode)) {
                throw new \InvalidArgumentException('Invalid verification code');
            }
        }
    }

    private function createUserFromPhone(string $phoneNumber, ?string $email, string $displayName): User
    {
        $user = new User($this->generateSecureUserId(), $email, ['ROLE_USER']);
        $verification = new \App\User\Entity\UserVerification($user->getId());
        $user->setVerification($verification);
        if ($phoneNumber) {
            $user->setPhoneNumber($phoneNumber);
        }
        $userProfile = new \App\User\Entity\UserProfile($user->getId());
        $userProfile->setDisplayName($displayName);
        $user->setProfile($userProfile);
        $this->entityManager->persist($userProfile);
        $randomPassword = bin2hex(random_bytes(16));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);
        return $user;
    }

    /**
     * Register user with social login.
     */
    public function registerWithSocial(string $provider, string $socialId, string $email, string $displayName, ?string $avatarUrl = null, ?string $preferredLanguage = 'en', ?string $timezone = 'UTC'): User {
        $existingUser = $this->userRepository->findBySocial($provider, $socialId);
        if ($existingUser) { throw new \InvalidArgumentException('Social account already linked'); }

        $existingEmailUser = $this->userRepository->findByEmail($email);
        if ($existingEmailUser) {
            $existingEmailUser->setSocialProvider($provider);
            $existingEmailUser->setSocialId($socialId);
            $this->entityManager->flush();
            return $existingEmailUser;
        }

        return $this->createUserWithSocial($provider, $socialId, $email, $displayName, $avatarUrl, $preferredLanguage, $timezone);
    }

    private function createUserWithSocial(string $provider, string $socialId, string $email, string $displayName, ?string $avatarUrl, string $preferredLanguage, string $timezone): User {
        $user = new User($this->generateSecureUserId(), $email, ['ROLE_USER']);
        $verification = new \App\User\Entity\UserVerification($user->getId());
        $user->setVerification($verification);
        $user->setSocialProvider($provider);
        $user->setSocialId($socialId);
        $user->setPreferredLanguage($preferredLanguage);

        $userProfile = new \App\User\Entity\UserProfile($user->getId());
        $userProfile->setDisplayName($displayName);
        if ($avatarUrl) { $userProfile->setAvatar($avatarUrl); }
        $user->setProfile($userProfile);

        $randomPassword = bin2hex(random_bytes(16));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) { throw new \InvalidArgumentException((string) $errors); }

        $this->entityManager->persist($userProfile);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Register user with biometric data.
     */
    public function registerWithBiometric(
        string $biometricData,
        string $email,
        string $displayName,
        ?string $phoneNumber = null,
        ?string $preferredLanguage = 'en',
        ?string $timezone = 'UTC',
    ): User {
        $this->validateBiometricData($biometricData);
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            throw new \InvalidArgumentException('Email already registered');
        }
        $randomPassword = bin2hex(random_bytes(16));
        $user = $this->buildUserFromEmail($email, $randomPassword, $displayName, $phoneNumber);
        $this->validateAndPersistUser($user);
        return $user;
    }

    private function validateBiometricData(string $biometricData): void
    {
        if (empty($biometricData) || strlen($biometricData) < 100) {
            throw new \InvalidArgumentException('Invalid biometric data');
        }
    }

    /**
     * Send SMS verification code.
     */
    public function sendSmsVerification(string $phoneNumber)
    {
        if ($this->smsVerificationService) {
            $result = $this->smsVerificationService->sendVerificationCode($phoneNumber);
            return $result['success'] ?? false;
        }

        // Fallback to old mock behavior for backwards compatibility
        // Mock SMS sending - in real app, integrate with Twilio, AWS SNS, etc.
        return true;
    }

    /**
     * Verify SMS code.
     */
    private function verifySmsCode(string $phoneNumber, string $code)
    {
        // Mock verification - in real app, check against stored codes
        return 6 === strlen($code) && is_numeric($code);
    }

    /**
     * Validate password strength.
     * Requirements:
     * - At least 8 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one number
     * - At least one special character
     * - Not a common password
     */
    private function validatePasswordStrength(string $password): void
    {
        if (strlen($password) < 8) { throw new \InvalidArgumentException('Password must be at least 8 characters long'); }
        if (!preg_match('/[A-Z]/', $password)) { throw new \InvalidArgumentException('Password must contain at least one uppercase letter'); }
        if (!preg_match('/[a-z]/', $password)) { throw new \InvalidArgumentException('Password must contain at least one lowercase letter'); }
        if (!preg_match('/[0-9]/', $password)) { throw new \InvalidArgumentException('Password must contain at least one number'); }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) { throw new \InvalidArgumentException('Password must contain at least one special character'); }
        $commonPasswords = ['password','password123','password1','123456','12345678','123456789','qwerty','abc123','monkey','master','dragon','letmein','login','admin','princess','welcome','football','baseball','iloveyou','trustno1','sunshine','shadow','superman','michael','ninja','mustang','batman','password1!','password123!','admin123','root','toor','passw0rd','p@ssword','p@ssw0rd','welcome1','qwerty123','1q2w3e4r','zaq12wsx','p@ssw0rd!'];
        if (in_array(strtolower($password), $commonPasswords, true)) { throw new \InvalidArgumentException('Password is too common. Please choose a stronger password'); }
    }

    /**
     * Validate phone number format.
     */
    private function isValidPhoneNumber(string $phoneNumber)
    {
        // Basic phone number validation - in real app, use more sophisticated validation
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);

        return strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
    }
}
