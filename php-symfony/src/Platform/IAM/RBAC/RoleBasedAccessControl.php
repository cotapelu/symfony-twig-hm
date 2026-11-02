<?php

namespace Platform\IAM\RBAC;

use Platform\IAM\Model\User;
use Platform\IAM\Model\Role;
use Platform\IAM\AuthServiceInterface;

class RoleBasedAccessControl
{
    private AuthServiceInterface $authService;
    private array $roleHierarchy;
    private array $permissionHierarchy;

    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
        $this->roleHierarchy = [];
        $this->permissionHierarchy = [];
        $this->initializeHierarchy();
    }

    public function checkPermission(User $user, string $permission, ?string $resource = null): bool
    {
        if ($this->authService->authorize($user, $permission, $resource)) {
            return true;
        }

        $userRoles = $this->authService->getUserRoles($user);
        
        foreach ($userRoles as $roleId) {
            if ($this->hasInheritedPermission($roleId, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function checkRole(User $user, string $role): bool
    {
        $userRoles = $this->authService->getUserRoles($user);
        return in_array($role, $userRoles);
    }

    public function addRoleHierarchy(string $parentRole, string $childRole): bool
    {
        if (!isset($this->roleHierarchy[$parentRole])) {
            $this->roleHierarchy[$parentRole] = [];
        }

        if (!in_array($childRole, $this->roleHierarchy[$parentRole])) {
            $this->roleHierarchy[$parentRole][] = $childRole;
            return true;
        }

        return false;
    }

    public function addPermissionHierarchy(string $parentPermission, string $childPermission): bool
    {
        if (!isset($this->permissionHierarchy[$parentPermission])) {
            $this->permissionHierarchy[$parentPermission] = [];
        }

        if (!in_array($childPermission, $this->permissionHierarchy[$parentPermission])) {
            $this->permissionHierarchy[$parentPermission][] = $childPermission;
            return true;
        }

        return false;
    }

    public function getRoleHierarchy(): array
    {
        return $this->roleHierarchy;
    }

    public function getPermissionHierarchy(): array
    {
        return $this->permissionHierarchy;
    }

    public function getInheritedRoles(string $roleId): array
    {
        $inherited = [];
        $this->collectInheritedRoles($roleId, $inherited);
        return array_unique($inherited);
    }

    public function getInheritedPermissions(string $roleId): array
    {
        $inherited = [];
        $this->collectInheritedPermissions($roleId, $inherited);
        return array_unique($inherited);
    }

    private function hasInheritedPermission(string $roleId, string $permission): bool
    {
        $inheritedPermissions = $this->getInheritedPermissions($roleId);
        return in_array($permission, $inheritedPermissions);
    }

    private function collectInheritedRoles(string $roleId, array &$collected): void
    {
        if (isset($this->roleHierarchy[$roleId])) {
            foreach ($this->roleHierarchy[$roleId] as $childRole) {
                if (!in_array($childRole, $collected)) {
                    $collected[] = $childRole;
                    $this->collectInheritedRoles($childRole, $collected);
                }
            }
        }
    }

    private function collectInheritedPermissions(string $roleId, array &$collected): void
    {
        $role = $this->getRole($roleId);
        if ($role) {
            foreach ($role->permissions as $permission) {
                if (!in_array($permission, $collected)) {
                    $collected[] = $permission;
                }
            }

            $inheritedRoles = $this->getInheritedRoles($roleId);
            foreach ($inheritedRoles as $inheritedRoleId) {
                $this->collectInheritedPermissions($inheritedRoleId, $collected);
            }
        }
    }

    private function initializeHierarchy(): void
    {
        $this->addRoleHierarchy('admin', 'manager');
        $this->addRoleHierarchy('admin', 'user');
        $this->addRoleHierarchy('manager', 'user');

        $this->addPermissionHierarchy('admin', 'read');
        $this->addPermissionHierarchy('admin', 'write');
        $this->addPermissionHierarchy('admin', 'delete');
        $this->addPermissionHierarchy('read', 'list');
        $this->addPermissionHierarchy('write', 'update');
    }

    private function getRole(string $roleId): ?Role
    {
        
        return null;
    }
}