<?php

declare(strict_types=1);

namespace App\Conversation\Service;


/**
 * Group Management Service
 * Handles group roles, permissions, moderation, and analytics.
 */
class GroupManagementService
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

    private const CHANNEL_HIERARCHY = [
        'categories' => [
            [
                'id' => 'cat_general',
                'name' => 'General',
                'position' => 0,
                'channels' => [
                    [
                        'id' => 'chan_general',
                        'name' => 'general',
                        'type' => 'text',
                        'position' => 0,
                        'threads' => [
                            ['id' => 'thread1', 'title' => 'Welcome Thread', 'message_count' => 15],
                            ['id' => 'thread2', 'title' => 'Rules Discussion', 'message_count' => 8],
                        ],
                    ],
                    [
                        'id' => 'chan_announcements',
                        'name' => 'announcements',
                        'type' => 'announcement',
                        'position' => 1,
                    ],
                ],
            ],
            [
                'id' => 'cat_voice',
                'name' => 'Voice Channels',
                'position' => 1,
                'channels' => [
                    [
                        'id' => 'chan_general_vc',
                        'name' => 'General VC',
                        'type' => 'voice',
                        'position' => 0,
                        'user_count' => 3,
                    ],
                    [
                        'id' => 'chan_afk',
                        'name' => 'AFK',
                        'type' => 'voice',
                        'position' => 1,
                        'user_count' => 0,
                    ],
                ],
            ],
        ],
        'subgroups' => [
            [
                'id' => 'sub_dev',
                'name' => 'Development Team',
                'channels' => [
                    ['id' => 'chan_dev_general', 'name' => 'dev-general', 'type' => 'text'],
                    ['id' => 'chan_dev_planning', 'name' => 'dev-planning', 'type' => 'text'],
                ],
            ],
        ],
    ];

    private const TEMPLATES = [
        'team' => [
            'settings' => [
                'allow_guests' => false,
                'require_approval' => false,
                'max_members' => 50,
                'voice_channels_enabled' => true,
                'video_calls_enabled' => true,
            ],
            'default_channels' => ['general', 'random', 'projects', 'meetings'],
        ],
        'community' => [
            'settings' => [
                'allow_guests' => true,
                'require_approval' => true,
                'max_members' => 10000,
                'voice_channels_enabled' => true,
                'video_calls_enabled' => false,
            ],
            'default_channels' => ['welcome', 'general', 'off-topic', 'announcements'],
        ],
        'project' => [
            'settings' => [
                'allow_guests' => false,
                'require_approval' => false,
                'max_members' => 20,
                'voice_channels_enabled' => true,
                'video_calls_enabled' => true,
            ],
            'default_channels' => ['general', 'planning', 'development', 'design', 'testing'],
        ],
        'study' => [
            'settings' => [
                'allow_guests' => true,
                'require_approval' => false,
                'max_members' => 100,
                'voice_channels_enabled' => true,
                'video_calls_enabled' => true,
            ],
            'default_channels' => ['general', 'study-help', 'resources', 'off-topic'],
        ],
        'gaming' => [
            'settings' => [
                'allow_guests' => false,
                'require_approval' => false,
                'max_members' => 1000,
                'voice_channels_enabled' => true,
                'video_calls_enabled' => false,
            ],
            'default_channels' => ['general', 'game-discussion', 'tournaments', 'voice-chat'],
        ],
    ];

    public function __construct(
    ) {
    }

    /**
     * Get all available roles.
     */
    public function getRoles()
    {
        return $this->groupRoles;
    }

    /**
     * Get role by name.
     */
    public function getRole(string $roleName)
    {
        return $this->groupRoles[$roleName] ?? null;
    }

    /**
     * Get all permissions.
     */
    public function getPermissions()
    {
        return $this->groupPermissions;
    }

    /**
     * Get user's role in a group.
     */
    public function getUserRole(string $groupId, string $userId)
    {
        // In a real implementation, this would query the database
        // For now, return default member role
        return $this->groupRoles['member'];
    }

    /**
     * Assign role to user in group.
     */
    public function assignRole(string $groupId, string $userId, string $roleName, string $assignedBy)
    {
        // Check if assigner has permission to manage roles
        if (!$this->hasPermission($groupId, $assignedBy, 'manage_members')) {
            return false;
        }

        // Check if role exists
        if (!isset($this->groupRoles[$roleName])) {
            return false;
        }

        // Check role hierarchy (can't assign higher level role)
        $assignerRole = $this->getUserRole($groupId, $assignedBy);
        $targetRole = $this->groupRoles[$roleName];

        if ($assignerRole['level'] <= $targetRole['level']) {
            return false;
        }

        // In a real implementation, save to database
        // For now, just return success
        return true;
    }

    /**
     * Remove user from group.
     */
    public function removeMember(string $groupId, string $userId, string $removedBy)
    {
        // Check if remover has permission
        if (!$this->hasPermission($groupId, $removedBy, 'manage_members')) {
            return false;
        }

        // Can't remove yourself if you're the only owner
        // Implementation would check this

        // In a real implementation, remove from database
        return true;
    }

    /**
     * Ban user from group.
     */
    public function banMember(string $groupId, string $userId, string $bannedBy, string $reason = '', int $duration = 0)
    {
        // Check if banner has permission
        if (!$this->hasPermission($groupId, $bannedBy, 'ban_members')) {
            return false;
        }

        // Can't ban owners or higher level roles
        $bannerRole = $this->getUserRole($groupId, $bannedBy);
        $targetRole = $this->getUserRole($groupId, $userId);

        if ($targetRole['level'] >= $bannerRole['level']) {
            return false;
        }

        // In a real implementation, add to ban list with duration
        return true;
    }

    /**
     * Unban user from group.
     */
    public function unbanMember(string $groupId, string $userId, string $unbannedBy)
    {
        // Check if unbanned has permission
        if (!$this->hasPermission($groupId, $unbannedBy, 'ban_members')) {
            return false;
        }

        // In a real implementation, remove from ban list
        return true;
    }

    /**
     * Check if user is banned.
     */
    public function isBanned(string $groupId, string $userId)
    {
        // In a real implementation, check ban list
        return false;
    }

    /**
     * Create custom role.
     */
    public function createRole(string $groupId, string $roleName, string $displayName, array $permissions, string $createdBy)
    {
        // Check if creator has permission
        if (!$this->hasPermission($groupId, $createdBy, 'manage_roles')) {
            return null;
        }

        // Validate permissions
        $validPermissions = array_keys($this->groupPermissions);
        $invalidPermissions = array_diff($permissions, $validPermissions);

        if (!empty($invalidPermissions)) {
            return null;
        }

        $role = [
            'name' => $roleName,
            'display_name' => $displayName,
            'permissions' => $permissions,
            'level' => 50, // Custom roles get level 50
            'custom' => true,
            'group_id' => $groupId,
        ];

        // In a real implementation, save to database
        return $role;
    }

    /**
     * Update group settings.
     */
    public function updateGroupSettings(string $groupId, array $settings, string $updatedBy)
    {
        // Check if updater has permission
        if (!$this->hasPermission($groupId, $updatedBy, 'manage_settings')) {
            return false;
        }

        // Validate settings
        $validSettings = [
            'name', 'description', 'avatar', 'privacy', 'join_method',
            'message_retention', 'file_sharing', 'invites_allowed',
        ];

        foreach ($settings as $key => $value) {
            if (!in_array($key, $validSettings)) {
                return false;
            }
        }

        // In a real implementation, save settings
        return true;
    }

    /**
     * Get group analytics.
     */
    public function getGroupAnalytics(string $groupId, string $userId)
    {
        // Check if user has permission to view analytics
        if (!$this->hasPermission($groupId, $userId, 'view_analytics')) {
            return ['error' => 'Access denied'];
        }

        // In a real implementation, calculate from database
        return [
            'total_members' => 150,
            'active_members' => 89,
            'messages_today' => 234,
            'messages_week' => 1456,
            'files_shared' => 67,
            'top_channels' => [
                ['name' => 'general', 'messages' => 456],
                ['name' => 'random', 'messages' => 234],
                ['name' => 'announcements', 'messages' => 123],
            ],
            'member_growth' => [
                ['date' => '2024-01-01', 'count' => 120],
                ['date' => '2024-01-02', 'count' => 135],
                ['date' => '2024-01-03', 'count' => 142],
                ['date' => '2024-01-04', 'count' => 150],
            ],
            'engagement_rate' => 78.5,
        ];
    }

    /**
     * Moderate content (delete message, warn user, etc.).
     */

    /**
     * Create channel in group.
     */
    public function createChannel(string $groupId, string $channelName, string $channelType, array $settings, string $createdBy)
    {
        // Check if creator has permission
        if (!$this->hasPermission($groupId, $createdBy, 'create_channels')) {
            return null;
        }

        $channel = [
            'id' => uniqid('channel_', true),
            'group_id' => $groupId,
            'name' => $channelName,
            'type' => $channelType, // 'text', 'voice', 'announcement'
            'settings' => $settings,
            'created_by' => $createdBy,
            'created_at' => time(),
        ];

        // In a real implementation, save to database
        return $channel;
    }

    /**
     * Update channel settings.
     */
    public function updateChannelSettings(string $groupId, string $channelId, array $settings, string $updatedBy)
    {
        // Check if updater has permission
        if (!$this->hasPermission($groupId, $updatedBy, 'manage_channels')) {
            return false;
        }

        // In a real implementation, update channel
        return true;
    }

    /**
     * Delete channel.
     */
    public function deleteChannel(string $groupId, string $channelId, string $deletedBy)
    {
        // Check if deleter has permission
        if (!$this->hasPermission($groupId, $deletedBy, 'manage_channels')) {
            return false;
        }

        // In a real implementation, delete channel
        return true;
    }

    /**
     * Get group members with roles.
     */
    public function getGroupMembers(string $groupId, string $userId)
    {
        // Check if user has permission to view members
        if (!$this->hasPermission($groupId, $userId, 'manage_members')) {
            return ['error' => 'Access denied'];
        }

        // In a real implementation, return member list with roles
        return [
            [
                'user_id' => 'user1',
                'username' => 'john_doe',
                'role' => 'owner',
                'joined_at' => time() - 86400 * 30,
                'last_active' => time() - 3600,
            ],
            [
                'user_id' => 'user2',
                'username' => 'jane_smith',
                'role' => 'admin',
                'joined_at' => time() - 86400 * 15,
                'last_active' => time() - 1800,
            ],
        ];
    }

    /**
     * Create a new group with templates and settings.
     */
    public function createGroup(string $name, string $description, string $creatorId, bool $isPrivate = false, ?string $template = null, array $initialMembers = [], array $settings = []) {
        $groupId = 'group_'.uniqid();
        $mergedSettings = array_merge(['allow_guests'=>false,'require_approval'=>false,'max_members'=>1000,'message_retention_days'=>365,'file_sharing_enabled'=>true,'voice_channels_enabled'=>true,'video_calls_enabled'=>true,'integrations_enabled'=>true], $settings);
        $totalMembers = 1 + count($initialMembers);
        $group = $this->buildGroupBase($groupId, $name, $description, $creatorId, $isPrivate, $template, $mergedSettings, $totalMembers);
        $group = $this->applyGroupTemplate($group, $template);
        $channels = $this->createDefaultChannels($groupId, $template);
        $members = $this->buildMembers($creatorId, $initialMembers);
        $inviteLink = $isPrivate ? $this->generateInviteLink($groupId) : null;
        return [
            'group'=>$group,
            'channels'=>$channels,
            'members'=>$members,
            'invite_link'=>$inviteLink,
        ];
    }

    private function buildGroupBase(string $groupId, string $name, string $description, string $creatorId, bool $isPrivate, ?string $template, array $settings, int $memberCount): array
    {
        return ['id'=>$groupId,'name'=>$name,'description'=>$description,'creator_id'=>$creatorId,'type'=>'group','privacy'=>$isPrivate?'private':'public','template'=>$template,'settings'=>$settings,'created_at'=>time(),'updated_at'=>time(),'member_count'=>$memberCount,'channel_count'=>1];
    }

    private function buildMembers(string $creatorId, array $initialMembers): array
    {
        $members = [
            $creatorId => ['user_id'=>$creatorId,'role'=>'owner','joined_at'=>time(),'permissions'=>$this->groupRoles['owner']['permissions']],
        ];
        foreach ($initialMembers as $memberId) {
            $members[$memberId] = ['user_id'=>$memberId,'role'=>'member','joined_at'=>time(),'permissions'=>$this->groupRoles['member']['permissions']];
        }
        return $members;
    }

    /**
     * Apply group template settings.
     */
    private function applyGroupTemplate(array $group, ?string $template)
    {
        if ($template !== null && isset(self::TEMPLATES[$template])) {
            $group['settings'] = array_merge($group['settings'], self::TEMPLATES[$template]['settings']);
            $group['template'] = $template;
        }
        return $group;
    }

    /**
     * Create default channels for a group.
     */
    private function createDefaultChannels(string $groupId, ?string $template)
    {
        $channelNames = $template && isset(self::TEMPLATES[$template]['default_channels']) ? self::TEMPLATES[$template]['default_channels'] : ['general'];
        $channels = [];
        foreach ($channelNames as $channelName) {
            $channels[] = ['id'=>'channel_'.uniqid(),'group_id'=>$groupId,'name'=>$channelName,'type'=>'text','description'=>ucfirst($channelName).' discussion','position'=>count($channels),'permissions'=>['read_messages'=>true,'send_messages'=>true,'upload_files'=>true],'created_at'=>time()];
        }
        return $channels;
    }

    /**
     * Generate invite link for private groups.
     */
    private function generateInviteLink(string $groupId)
    {
        $token = bin2hex(random_bytes(16));

        return "https://app.comm-unity.com/invite/{$groupId}?token={$token}";
    }

    /**
     * Get available group templates.
     */
    public function getGroupTemplates()
    {
        return ['team'=>['name'=>'Team Collaboration','description'=>'Perfect for work teams and project collaboration','icon'=>'👥','features'=>['Private by default','Role management','File sharing','Voice channels']],'community'=>['name'=>'Community Hub','description'=>'Great for online communities and social groups','icon'=>'🌍','features'=>['Public access','Member approval','Announcements','Large member capacity']],'project'=>['name'=>'Project Workspace','description'=>'Ideal for focused project teams','icon'=>'🚀','features'=>['Task management','File organization','Meeting spaces','Progress tracking']],'study'=>['name'=>'Study Group','description'=>'Designed for educational collaboration','icon'=>'📚','features'=>['Resource sharing','Study help','Group discussions','Educational tools']],'gaming'=>['name'=>'Gaming Community','description'=>'Perfect for gaming communities with voice channels, tournaments, and leaderboards','icon'=>'🎮','features'=>['Voice channels','Tournaments','Leaderboards','Game integrations']],'custom'=>['name'=>'Custom Group','description'=>'Create a group with your own settings','icon'=>'⚙️','features'=>['Full customization','Advanced permissions','Integration support']]];
    }

    /**
     * Validate group creation parameters.
     */
    public function validateGroupCreation(array $params)
    {
        $errors = [];

        if (empty($params['name']) || strlen($params['name']) < 2) {
            $errors[] = 'Group name must be at least 2 characters long';
        }

        if (strlen($params['name'] ?? '') > 100) {
            $errors[] = 'Group name cannot exceed 100 characters';
        }

        if (!empty($params['description']) && strlen($params['description']) > 500) {
            $errors[] = 'Group description cannot exceed 500 characters';
        }

        if (isset($params['max_members']) && ($params['max_members'] < 2 || $params['max_members'] > 100000)) {
            $errors[] = 'Maximum members must be between 2 and 100,000';
        }

        return $errors;
    }

    /**
     * Create a sub-group within a parent group.
     */
    public function createSubGroup(
        string $parentGroupId,
        string $name,
        string $description,
        string $creatorId,
        array $settings = [],
    ) {
        return $this->buildSubGroupData($parentGroupId, $name, $description, $creatorId, $settings);
    }

    private function buildSubGroupData(string $parentGroupId, string $name, string $description, string $creatorId, array $settings = []): array
    {
        $subGroupId = 'subgroup_'.uniqid();
        return [
            'id' => $subGroupId,
            'parent_group_id' => $parentGroupId,
            'name' => $name,
            'description' => $description,
            'creator_id' => $creatorId,
            'type' => 'subgroup',
            'settings' => array_merge([
                'inherit_permissions' => true,
                'max_members' => 100,
                'allow_public_channels' => false,
            ], $settings),
            'created_at' => time(),
            'updated_at' => time(),
            'member_count' => 1,
            'channel_count' => 0,
        ];
    }

    /**
     * Create a nested channel within a group or sub-group.
     */
    public function createNestedChannel(string $parentId, string $name, string $type = 'text', string $description = '', ?string $parentChannelId = null, array $permissions = [], string $creatorId = '') {
        $channelId = 'channel_'.uniqid();
        $channel = ['id'=>$channelId,'parent_id'=>$parentId,'parent_channel_id'=>$parentChannelId,'name'=>$name,'type'=>$type,'description'=>$description,'position'=>0,'permissions'=>array_merge(['read_messages'=>true,'send_messages'=>true,'upload_files'=>true,'embed_links'=>true,'mention_everyone'=>false,'manage_messages'=>false], $permissions),'settings'=>['slow_mode'=>0,'nsfw'=>false,'require_verification'=>false,'auto_archive_threads'=>true,'thread_archive_duration'=>86400],'created_by'=>$creatorId,'created_at'=>time(),'updated_at'=>time(),'topic'=>'','last_message_id'=>null,'last_message_at'=>null,'message_count'=>0,'thread_count'=>0];
        return $channel;
    }

    /**
     * Create a thread within a channel.
     */
    public function createThread(string $channelId, string $title, string $creatorId, ?string $messageId = null, array $settings = []) {
        $threadId = 'thread_'.uniqid();
        $thread = ['id'=>$threadId,'channel_id'=>$channelId,'title'=>$title,'creator_id'=>$creatorId,'message_id'=>$messageId,'type'=>$messageId?'message_thread':'channel_thread','settings'=>array_merge(['auto_archive_duration'=>86400,'slow_mode'=>0,'require_membership'=>false,'pinned'=>false], $settings),'created_at'=>time(),'updated_at'=>time(),'archived_at'=>null,'last_message_at'=>time(),'message_count'=>0,'member_count'=>1,'members'=>[$creatorId]];
        return $thread;
    }

    /**
     * Organize channels into categories/topics.
     */
    public function createChannelCategory(
        string $groupId,
        string $name,
        string $description = '',
        array $permissions = [],
    ) {
        return $this->buildChannelCategoryData($groupId, $name, $description, $permissions);
    }

    private function buildChannelCategoryData(string $groupId, string $name, string $description = '', array $permissions = []): array
    {
        $categoryId = 'category_'.uniqid();
        return [
            'id' => $categoryId,
            'group_id' => $groupId,
            'name' => $name,
            'description' => $description,
            'type' => 'category',
            'position' => 0,
            'permissions' => array_merge([
                'view_channel' => true,
                'manage_channels' => false,
                'connect' => true,
            ], $permissions),
            'created_at' => time(),
            'channel_count' => 0,
            'is_collapsed' => false,
        ];
    }

    /**
     * Get channel hierarchy for a group.
     */
    public function getChannelHierarchy(string $groupId) {
        return self::CHANNEL_HIERARCHY;
    }

    /**
     * Moderate content in a group.
     */
    public function moderateContent(
        string $groupId,
        string $contentId,
        string $action,
        string $moderatorId,
        string $reason = '',
    ) {
        // Actions: approve, reject, delete, warn, ban, timeout
        $moderationActions = [
            'approve' => 'Content approved',
            'reject' => 'Content rejected',
            'delete' => 'Content deleted',
            'warn' => 'User warned',
            'ban' => 'User banned',
            'timeout' => 'User timed out',
        ];

        if (!isset($moderationActions[$action])) {
            return false;
        }

        // In a real implementation, update content status and log moderation action
        return true;
    }

    /**
     * Get moderation queue for a group.
     */
    public function getModerationQueue(string $groupId, string $moderatorId) {
        if (!$this->hasPermission($groupId, $moderatorId, 'moderate_content')) return ['error'=>'Insufficient permissions'];
        return [['id'=>'mod1','content_id'=>'msg_123','content_type'=>'message','content_preview'=>'This is a potentially inappropriate message...','author_id'=>'user456','author_name'=>'John Doe','channel_id'=>'chan_general','channel_name'=>'general','reason'=>'Potential spam','severity'=>'medium','reported_at'=>time()-3600,'reports_count'=>3],['id'=>'mod2','content_id'=>'msg_124','content_type'=>'message','content_preview'=>'Inappropriate language detected...','author_id'=>'user789','author_name'=>'Jane Smith','channel_id'=>'chan_random','channel_name'=>'random','reason'=>'Harassment','severity'=>'high','reported_at'=>time()-1800,'reports_count'=>1]];
    }

    /**
     * Report content for moderation.
     */
    public function reportContent(
        string $groupId,
        string $contentId,
        string $contentType,
        string $reporterId,
        string $reason,
        string $description = '',
    ) {
        // In a real implementation, add to moderation queue
        return true;
    }

    /**
     * Set up auto-moderation rules for a group.
     */
    public function setAutoModerationRules(string $groupId, array $rules)
    {
        // In a real implementation, save rules to database
        return true;
    }

    /**
     * Get auto-moderation rules for a group.
     */
    public function getAutoModerationRules(string $groupId)
    {
        return [
            'spam_filter' => true,
            'banned_words' => ['spam', 'inappropriate'],
            'link_filter' => false,
            'caps_filter' => [
                'enabled' => true,
                'threshold' => 70,
            ],
            'duplicate_filter' => [
                'enabled' => true,
                'time_window' => 300,
            ],
            'mention_filter' => [
                'enabled' => false,
                'max_mentions' => 5,
            ],
        ];
    }

    /**
     * Check content against auto-moderation rules.
     */
    public function checkAutoModeration(string $groupId, string $content, string $userId)
    {
        $rules = $this->getAutoModerationRules($groupId);
        $violations = array_merge(
            $this->checkBannedWords($rules['banned_words'], $content),
            $this->checkCapsFilter($rules['caps_filter'], $content)
        );
        return [
            'allowed' => empty($violations),
            'violations' => $violations,
            'action' => empty($violations) ? 'allow' : 'flag',
        ];
    }

    private function checkBannedWords(array $bannedWords, string $content): array
    {
        $violations = [];
        if (empty($bannedWords)) {
            return $violations;
        }
        foreach ($bannedWords as $word) {
            if (stripos($content, $word) !== false) {
                $violations[] = [
                    'rule' => 'banned_words',
                    'word' => $word,
                    'severity' => 'high',
                ];
            }
        }
        return $violations;
    }

    private function checkCapsFilter(array $capsFilter, string $content): array
    {
        $violations = [];
        if (empty($capsFilter['enabled'])) {
            return $violations;
        }
        $capsCount = preg_match_all('/[A-Z]/', $content);
        $totalChars = strlen($content);
        if ($totalChars > 10) {
            $capsPercentage = ($capsCount / $totalChars) * 100;
            if ($capsPercentage > $capsFilter['threshold']) {
                $violations[] = [
                    'rule' => 'caps_filter',
                    'percentage' => round($capsPercentage, 1),
                    'severity' => 'medium',
                ];
            }
        }
        return $violations;
    }

    /**
     * Get moderation statistics for a group.
     */
    public function getModerationStats(string $groupId, string $moderatorId)
    {
        if (!$this->hasPermission($groupId, $moderatorId, 'view_moderation_stats')) {
            return ['error' => 'Insufficient permissions'];
        }

        return [
            'total_reports' => 156,
            'resolved_reports' => 142,
            'pending_reports' => 14,
            'auto_moderated' => 89,
            'manual_reviews' => 67,
            'banned_users' => 5,
            'warned_users' => 23,
            'top_violation_types' => [
                'spam' => 45,
                'harassment' => 32,
                'inappropriate_content' => 28,
                'caps_abuse' => 15,
            ],
            'moderation_trends' => [
                'last_7_days' => 23,
                'last_30_days' => 89,
                'last_90_days' => 156,
            ],
        ];
    }

    /**
     * Check if user has specific permission.
     */
    private function hasPermission(string $groupId, string $userId, string $permission)
    {
        // In real implementation, check user's role permissions
        return true; // Mock: assume user has permission
    }
}
