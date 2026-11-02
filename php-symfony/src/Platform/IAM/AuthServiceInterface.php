<?php

namespace Platform\IAM;

use Platform\IAM\Model\User;
use Platform\IAM\Model\Role;
use Platform\IAM\Model\Permission;

interface AuthServiceInterface
{
    public function authenticate(string $username, string $password): ?User;
    
    public function authorize(User $user, string $permission, ?string $resource = null): bool;
    
    public function generateToken(User $user): string;
    
    public function validateToken(string $token): ?User;
    
    public function refreshToken(string $refreshToken): ?string;
    
    public function revokeToken(string $token): bool;
    
    public function getUserRoles(User $user): array;
    
    public function getUserPermissions(User $user): array;
    
    public function assignRole(User $user, Role $role): bool;
    
    public function revokeRole(User $user, Role $role): bool;
    
    public function grantPermission(Role $role, Permission $permission): bool;
    
    public function revokePermission(Role $role, Permission $permission): bool;
}