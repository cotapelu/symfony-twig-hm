<?php

namespace Platform\IAM\Model;

class Role
{
    public string $id;
    public string $name;
    public string $description;
    public array $permissions;
    public bool $active;
    public \DateTime $createdAt;
    public \DateTime $updatedAt;
    public int $priority;

    public function __construct(
        string $id,
        string $name,
        string $description
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->permissions = [];
        $this->active = true;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->priority = 0;
    }

    public function addPermission(string $permission): void
    {
        if (!in_array($permission, $this->permissions)) {
            $this->permissions[] = $permission;
            $this->updatedAt = new \DateTime();
        }
    }

    public function removePermission(string $permission): void
    {
        $key = array_search($permission, $this->permissions);
        if ($key !== false) {
            unset($this->permissions[$key]);
            $this->permissions = array_values($this->permissions);
            $this->updatedAt = new \DateTime();
        }
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->permissions,
            'active' => $this->active,
            'createdAt' => $this->createdAt->format(\DateTime::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTime::ATOM),
            'priority' => $this->priority
        ];
    }
}

class Permission
{
    public string $id;
    public string $name;
    public string $resource;
    public string $action;
    public string $description;
    public array $conditions;
    public bool $active;
    public \DateTime $createdAt;

    public function __construct(
        string $id,
        string $name,
        string $resource,
        string $action
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->resource = $resource;
        $this->action = $action;
        $this->description = '';
        $this->conditions = [];
        $this->active = true;
        $this->createdAt = new \DateTime();
    }

    public function addCondition(string $key, $value): void
    {
        $this->conditions[$key] = $value;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'resource' => $this->resource,
            'action' => $this->action,
            'description' => $this->description,
            'conditions' => $this->conditions,
            'active' => $this->active,
            'createdAt' => $this->createdAt->format(\DateTime::ATOM)
        ];
    }
}