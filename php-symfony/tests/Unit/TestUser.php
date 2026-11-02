<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Symfony\Component\Security\Core\User\UserInterface;

final class TestUser implements UserInterface
{
    public function __construct(
        private string $id,
        private array $roles = ['ROLE_USER']
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string
    {
        return '';
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    public function eraseCredentials(): void
    {
        // no-op
    }
}
