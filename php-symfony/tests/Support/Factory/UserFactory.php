<?php

declare(strict_types=1);

namespace App\Tests\Support\Factory;

use App\User\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFactory
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function create(array $overrides = []): User
    {
        $user = new User(
            username: $overrides['username'] ?? 'test_user_' . uniqid(),
            email: $overrides['email'] ?? 'test_' . uniqid() . '@example.com',
            roles: $overrides['roles'] ?? ['ROLE_USER']
        );
        $password = $overrides['password'] ?? 'password123';
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        // Apply other fields if provided
        if (isset($overrides['isVerified'])) {
            $user->setIsVerified($overrides['isVerified']);
        }

        return $user;
    }

    public function createAdmin(array $overrides = []): User
    {
        return $this->create(array_merge($overrides, [
            'roles' => ['ROLE_ADMIN']
        ]));
    }

    public function createCitizen(array $overrides = []): User
    {
        return $this->create(array_merge($overrides, [
            'roles' => ['ROLE_CITIZEN']
        ]));
    }
}
