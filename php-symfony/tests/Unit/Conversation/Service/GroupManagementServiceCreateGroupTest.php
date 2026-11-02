<?php

declare(strict_types=1);

namespace Tests\Unit\Conversation\Service;

use App\Conversation\Service\GroupManagementService;
use PHPUnit\Framework\TestCase;

class GroupManagementServiceCreateGroupTest extends TestCase
{
    private GroupManagementService $service;

    protected function setUp(): void
    {
        $this->service = new GroupManagementService();
    }

    // ==================== createGroup (happy paths) ====================

    public function testCreateGroup_Default_ReturnsExpectedStructure(): void
    {
        $result = $this->service->createGroup('My Group', 'Description', 'user1');

        $this->assertArrayHasKey('group', $result);
        $this->assertArrayHasKey('channels', $result);
        $this->assertArrayHasKey('members', $result);
        $this->assertArrayHasKey('invite_link', $result);

        $group = $result['group'];
        $this->assertSame('My Group', $group['name']);
        $this->assertSame('Description', $group['description']);
        $this->assertSame('user1', $group['creator_id']);
        $this->assertSame('public', $group['privacy']); // public by default
        $this->assertNull($group['template']);
        $this->assertIsArray($group['settings']);
        $this->assertSame(1, $group['member_count']);
        $this->assertSame(1, $group['channel_count']);
        $this->assertArrayHasKey('id', $group);
    }

    public function testCreateGroup_Private_GeneratesInviteLink(): void
    {
        $result = $this->service->createGroup('Private Group', 'Desc', 'user1', true);
        $this->assertNotNull($result['invite_link']);
        $this->assertStringStartsWith('https://app.comm-unity.com/invite/', $result['invite_link']);
    }

    public function testCreateGroup_WithTemplate_AppliesSettings(): void
    {
        $result = $this->service->createGroup('Team', 'Team desc', 'user1', false, 'team');
        $group = $result['group'];
        // Team template should override some settings
        $this->assertSame('team', $group['template']);
        // Check that template settings are merged (e.g., allow_guests false, require_approval false)
        $this->assertArrayHasKey('allow_guests', $group['settings']);
        $this->assertArrayHasKey('require_approval', $group['settings']);
    }

    public function testCreateGroup_WithInitialMembers_IncludesAll(): void
    {
        $result = $this->service->createGroup('Group', 'Desc', 'user1', false, null, ['user2', 'user3']);
        $members = $result['members'];
        $this->assertCount(3, $members); // creator + 2 initial
        $this->assertArrayHasKey('user1', $members);
        $this->assertArrayHasKey('user2', $members);
        $this->assertArrayHasKey('user3', $members);
        $this->assertSame('owner', $members['user1']['role']);
        $this->assertSame('member', $members['user2']['role']);
    }

    public function testCreateGroup_WithCustomSettings_Merged(): void
    {
        $custom = ['max_members' => 50, 'custom_key' => 'value'];
        $result = $this->service->createGroup('Group', 'Desc', 'user1', false, null, [], $custom);
        $settings = $result['group']['settings'];
        $this->assertSame(50, $settings['max_members']);
        $this->assertSame('value', $settings['custom_key']);
        // defaults preserved
        $this->assertTrue($settings['file_sharing_enabled']);
    }

    public function testCreateGroup_WithTemplate_DefaultChannels(): void
    {
        $result = $this->service->createGroup('Project', 'Project', 'user1', false, 'project');
        $channels = $result['channels'];
        // project template should have specific default channels like 'general', 'tasks', 'updates'
        $this->assertNotEmpty($channels);
        $names = array_column($channels, 'name');
        $this->assertContains('general', $names);
    }

    public function testCreateGroup_UnknownTemplate_FallsBackToGeneral(): void
    {
        $result = $this->service->createGroup('Group', 'Desc', 'user1', false, 'unknown');
        $channels = $result['channels'];
        $this->assertCount(1, $channels);
        $this->assertSame('general', $channels[0]['name']);
    }

    public function testCreateGroup_ChannelPermissionsDefault(): void
    {
        $result = $this->service->createGroup('Group', 'Desc', 'user1');
        $channels = $result['channels'];
        $this->assertCount(1, $channels);
        $channel = $channels[0];
        $this->assertSame('text', $channel['type']);
        $this->assertArrayHasKey('permissions', $channel);
        $this->assertTrue($channel['permissions']['read_messages']);
        $this->assertTrue($channel['permissions']['send_messages']);
        $this->assertTrue($channel['permissions']['upload_files']);
    }
}
