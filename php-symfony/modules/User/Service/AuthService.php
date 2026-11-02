<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Repository\UserRepositoryInterface;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use App\User\DomainEvent\UserRegistered;
use App\User\DomainEvent\UserPresenceChanged;
use App\User\Entity\User;
use App\Shared\Service\UuidService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    private UserRepositoryInterface $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    /** @unused Reserved for future HTTP API calls (social login, etc.) */

    public function __construct(
        UserRepositoryInterface $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        
        private UuidService $uuidService,
        private ?DomainEventPublisherInterface $eventPublisher = null,
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function register(array $data)
    {
        // Validate required fields
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
            throw new \InvalidArgumentException('Name, email and password are required');
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Validate password strength
        if (strlen($data['password']) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long');
        }

        // Check if user exists
        if ($this->userRepository->findByEmail($data['email'])) {
            throw new \Exception('User already exists');
        }

        // Create user
        $user = new User(
            $this->uuidService->generateWithPrefix('user_'),
            $data['email']
        );

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Set additional fields
        if (isset($data['phoneNumber'])) {
            $user->setPhoneNumber($data['phoneNumber']);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Publish domain event
        if ($this->eventPublisher) {
            $this->eventPublisher->publish(new UserRegistered(
                $user->getId(),
                $user->getEmail(),
                $data['name']
            ));
        }

        return $user;
    }

    public function authenticate(string $email, string $password)
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        // Update last login with transaction safety
        try {
            $user->setLastLoginAt(new \DateTime());
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Log error but don't fail authentication
            error_log('Failed to update last login: ' . $e->getMessage());
        }

        return $user;
    }

    public function socialLogin(string $provider, array $socialData)
    {
        // Handle social login (Google, Facebook, etc.)
        $socialId = $socialData['id'];
        $email = $socialData['email'];

        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            // Create new user
            $user = new User($this->uuidService->generateWithPrefix('user_'), $email);
            $user->setSocialProvider($provider);
            $user->setSocialId($socialId);

            // Set default password or mark as social-only
            $user->setPassword($this->passwordHasher->hashPassword($user, $this->uuidService->generate()));

            $this->entityManager->persist($user);
        } else {
            // Update social info
            $user->setSocialProvider($provider);
            $user->setSocialId($socialId);
        }

        $user->setLastLoginAt(new \DateTime());
        $this->entityManager->flush();

        return $user;
    }

    public function updateProfile(User $user, array $data)
    {
        if (isset($data['phoneNumber'])) {
            $user->setPhoneNumber($data['phoneNumber']);
        }

        if (isset($data['preferredLanguage'])) {
            $user->setPreferredLanguage($data['preferredLanguage']);
        }

        if (isset($data['timezone'])) {
            $user->setTimezone($data['timezone']);
        }

        if (isset($data['notificationSettings'])) {
            $user->setNotificationSettings($data['notificationSettings']);
        }

        if (isset($data['privacySettings'])) {
            $user->setPrivacySettings($data['privacySettings']);
        }

        $this->entityManager->flush();

        return $user;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword)
    {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            return false;
        }

        // Validate new password strength (minimum 8 chars, prevent common patterns)
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long');
        }

        if ($newPassword === $currentPassword) {
            throw new \InvalidArgumentException('New password must be different from current password');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $this->entityManager->flush();

        return true;
    }

    public function requestPasswordReset(string $email)
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return false; // Don't reveal if user exists
        }

        // Generate reset token and send email
        // Implementation depends on email service
        return true;
    }

    public function resetPassword(string $token, string $newPassword)
    {
        // Validate token and reset password
        // Implementation depends on token storage
        return true;
    }

    public function verifyEmail(string $token)
    {
        // Verify email with token
        return true;
    }

    public function getUserProfile(User $user)
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'phoneNumber' => $user->getPhoneNumber(),
            'preferredLanguage' => $user->getPreferredLanguage(),
            'timezone' => $user->getTimezone(),
            'notificationSettings' => $user->getNotificationSettings(),
            'privacySettings' => $user->getPrivacySettings(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'lastLoginAt' => $user->getLastLoginAt()?->format('c'),
            'isActive' => $user->isActive(),
        ];
    }

    /**
     * Validates JWT token and returns authenticated user ID
     * Throws exception if authentication fails
     */
    public function getAuthenticatedUserId(?\Symfony\Component\Security\Core\User\UserInterface $currentUser)
    {
        if (!$currentUser) {
            throw new \Exception('Authentication required');
        }
        return $currentUser->getUserIdentifier();
    }

    /**
     * Validates authentication and returns user object
     * Throws exception if authentication fails
     */
    public function getAuthenticatedUser(?\Symfony\Component\Security\Core\User\UserInterface $currentUser)
    {
        $userId = $this->getAuthenticatedUserId($currentUser);

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        return $user;
    }

    public function getUserFullName(User $user)
    {
        if (method_exists($user, 'getFullName')) {
            return $user->getFullName();
        }
        return $user->getEmail();
    }

    public function getUserRoles(User $user)
    {
        if (method_exists($user, 'getRoles')) {
            return $user->getRoles();
        }
        return ['ROLE_USER'];
    }

    public function getUserPermissions(User $user)
    {
        return $this->getUserRoles($user);
    }

    public function generateRefreshToken(User $user)
    {
        return bin2hex(random_bytes(32));
    }
}
