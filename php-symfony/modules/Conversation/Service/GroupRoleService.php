<?php

declare(strict_types=1);

namespace App\Conversation\Service;

/**
 * Group Role Service
 * Manages group roles and permissions.
 */
class GroupRoleService
{
    private array $groupRoles = [
        'owner' => [
            'name' => 'Owner',
            'permissions' => [
                'manage_group', 'manage_members', 'manage_roles', 'manage_channels',
                'delete_messages', 'ban_members', 'view_analytics', 'manage_integrations',
                'manage_settings', 'send_messages', 'upload_files', 'create_channels',
            ],
            'level' => 100,
        ],
        'admin' => [
            'name' => 'Administrator',
            'permissions' => [
                'manage_members', 'manage_channels', 'delete_messages', 'ban_members',
                'view_analytics', 'manage_integrations', 'manage_settings', 'send_messages',
                'upload_files', 'create_channels', 'moderate_content',
            ],
            'level' => 80,
        ],
        'moderator' => [
            'name' => 'Moderator',
            'permissions' => [
                'delete_messages', 'ban_members', 'moderate_content', 'send_messages',
                'upload_files', 'view_analytics', 'manage_channels',
            ],
            'level' => 60,
        ],
        'member' => [
            'name' => 'Member',
            'permissions' => [
                'send_messages', 'upload_files', 'read_messages', 'react_messages',
            ],
            'level' => 20,
        ],
        'guest' => [
            'name' => 'Guest',
            'permissions' => [
                'read_messages',
            ],
            'level' => 10,
        ],
    ];

    private array $groupPermissions = [
        'manage_group' => 'Full group management',
        'manage_members' => 'Add/remove members, change roles',
        'manage_roles' => 'Create and modify roles',
        'manage_channels' => 'Create, delete, and modify channels',
        'delete_messages' => 'Delete any message',
        'ban_members' => 'Ban/unban members',
        'view_analytics' => 'Access group analytics',
        'manage_integrations' => 'Configure integrations',
        'manage_settings' => 'Modify group settings',
        'send_messages' => 'Send messages',
        'upload_files' => 'Upload files and media',
        'create_channels' => 'Create new channels',
        'moderate_content' => 'Moderate content and reports',
        'read_messages' => 'Read messages',
        'react_messages' => 'Add reactions to messages',
    ];

    public function getRoles()
    {
        return $this->groupRoles;
    }

    public function getRole(string $roleName)
    {
        return $this->groupRoles[$roleName] ?? null;
    }

    public function getPermissions()
    {
        return $this->groupPermissions;
    }

    public function getPermission(string $permission)
    {
        return $this->groupPermissions[$permission] ?? null;
    }

    public function hasPermission(string $groupId, string $userId, string $permission)
    {
        $userRole = $this->getUserRole($groupId, $userId);
        if (!$userRole) {
            return false;
        }
        return in_array($permission, $userRole['permissions'], true);
    }

    public function getUserRole(string $groupId, string $userId)
    {
        return $this->groupRoles['member'];
    }

    public function assignRole(string $groupId, string $userId, string $roleName, string $assignedBy)
    {
        if (!$this->hasPermission($groupId, $assignedBy, 'manage_members')) {
            return false;
        }

        if (!isset($this->groupRoles[$roleName])) {
            return false;
        }

        $assignerRole = $this->getUserRole($groupId, $assignedBy);
        $targetRole = $this->groupRoles[$roleName];

        if ($assignerRole['level'] <= $targetRole['level']) {
            return false;
        }

        return true;
    }

    public function createRole(string $groupId, string $roleName, string $displayName, array $permissions, string $createdBy)
    {
        if (!$this->hasPermission($groupId, $createdBy, 'manage_roles')) {
            return null;
        }

        $validPermissions = array_keys($this->groupPermissions);
        $invalidPermissions = array_diff($permissions, $validPermissions);

        if (!empty($invalidPermissions)) {
            return null;
        }

        return [
            'name' => $roleName,
            'display_name' => $displayName,
            'permissions' => $permissions,
            'level' => 50,
            'custom' => true,
            'group_id' => $groupId,
        ];
    }
}
