<?php

namespace Platform\IAM\Model;

class User
{
    public string $id;
    public string $email;
    public string $username;
    public string $name;
    public ?string $avatar;
    public bool $active;
    public \DateTime $createdAt;
    public \DateTime $updatedAt;
    public array $roles;
    public array $permissions;
    public array $metadata;

    public function __construct(
        string $id,
        string $email,
        string $username,
        string $name
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->username = $username;
        $this->name = $name;
        $this->active = true;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->roles = [];
        $this->permissions = [];
        $this->metadata = [];
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function addRole(string $role): void
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
            $this->updatedAt = new \DateTime();
        }
    }

    public function removeRole(string $role): void
    {
        $key = array_search($role, $this->roles);
        if ($key !== false) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
            $this->updatedAt = new \DateTime();
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'username' => $this->username,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'active' => $this->active,
            'createdAt' => $this->createdAt->format(\DateTime::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTime::ATOM),
            'roles' => $this->roles,
            'permissions' => $this->permissions,
            'metadata' => $this->metadata
        ];
    }
}