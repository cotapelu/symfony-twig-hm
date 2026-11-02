<?php

declare(strict_types=1);

namespace Tests\Unit\Conversation\Service;

use App\Conversation\Service\GroupManagementService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class GroupManagementServiceBuildersTest extends TestCase
{
    private GroupManagementService $service;

    protected function setUp(): void
    {
        $this->service = new GroupManagementService();
    }

    private function invokePrivate(string $method, array $args = [])
    {
        $ref = new ReflectionMethod($this->service, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->service, ...$args);
    }

    // ==================== buildGroupBase ====================

    public function testBuildGroupBase_PrivateFalse_ReturnsPublicPrivacy(): void
    {
        $result = $this->invokePrivate('buildGroupBase', [
            'g1', 'Group A', 'Desc', 'user1', false, null, [], 10
        ]);
        $this->assertSame('g1', $result['id']);
        $this->assertSame('Group A', $result['name']);
        $this->assertSame('public', $result['privacy']);
        $this->assertSame(1, $result['channel_count']);
        $this->assertSame(10, $result['member_count']);
    }

    public function testBuildGroupBase_PrivateTrue_ReturnsPrivatePrivacy(): void
    {
        $result = $this->invokePrivate('buildGroupBase', [
            'g2', 'Group B', 'Desc', 'user2', true, 'team', ['setting'=>'value'], 5
        ]);
        $this->assertSame('private', $result['privacy']);
        $this->assertSame('team', $result['template']);
        $this->assertSame(['setting'=>'value'], $result['settings']);
    }

    // ==================== buildMembers ====================

    public function testBuildMembers_CreatorAndInitialMembers(): void
    {
        $members = $this->invokePrivate('buildMembers', ['user1', ['user2','user3']]);
        $this->assertCount(3, $members);
        $this->assertSame('owner', $members['user1']['role']);
        $this->assertSame('member', $members['user2']['role']);
        $this->assertSame('member', $members['user3']['role']);
        $this->assertArrayHasKey('permissions', $members['user1']);
        $this->assertArrayHasKey('joined_at', $members['user1']);
    }

    // ==================== generateInviteLink ====================

    public function testGenerateInviteLink_ReturnsCorrectFormat(): void
    {
        $link = $this->invokePrivate('generateInviteLink', ['g123']);
        $this->assertStringStartsWith('https://app.comm-unity.com/invite/g123?token=', $link);
        $parts = parse_url($link, PHP_URL_QUERY);
        parse_str($parts, $query);
        $this->assertArrayHasKey('token', $query);
        $this->assertSame(32, strlen($query['token'])); // 16 bytes -> 32 hex chars
    }

    // ==================== buildSubGroupData ====================

    public function testBuildSubGroupData_Defaults(): void
    {
        $result = $this->invokePrivate('buildSubGroupData', ['parent1', 'Sub', 'Sub desc', 'user1']);
        $this->assertStringStartsWith('subgroup_', $result['id']);
        $this->assertSame('parent1', $result['parent_group_id']);
        $this->assertSame('subgroup', $result['type']);
        $this->assertTrue($result['settings']['inherit_permissions']);
        $this->assertSame(100, $result['settings']['max_members']);
        $this->assertFalse($result['settings']['allow_public_channels']);
        $this->assertSame(1, $result['member_count']);
        $this->assertSame(0, $result['channel_count']);
    }

    public function testBuildSubGroupData_OverridesSettings(): void
    {
        $result = $this->invokePrivate('buildSubGroupData', ['parent1', 'Sub', 'Desc', 'user1', [
            'max_members' => 50,
            'custom_setting' => true
        ]]);
        $this->assertSame(50, $result['settings']['max_members']);
        $this->assertTrue($result['settings']['custom_setting']);
        $this->assertTrue($result['settings']['inherit_permissions']); // default preserved
    }

    // ==================== buildChannelCategoryData ====================

    public function testBuildChannelCategoryData_Defaults(): void
    {
        $result = $this->invokePrivate('buildChannelCategoryData', ['g1', 'Category A']);
        $this->assertStringStartsWith('category_', $result['id']);
        $this->assertSame('g1', $result['group_id']);
        $this->assertSame('Category A', $result['name']);
        $this->assertSame('category', $result['type']);
        $this->assertSame(0, $result['position']);
        $this->assertTrue($result['permissions']['view_channel']);
        $this->assertFalse($result['permissions']['manage_channels']);
        $this->assertTrue($result['permissions']['connect']);
        $this->assertSame(0, $result['channel_count']);
        $this->assertFalse($result['is_collapsed']);
    }

    public function testBuildChannelCategoryData_WithDescriptionAndOverrides(): void
    {
        $result = $this->invokePrivate('buildChannelCategoryData', ['g1', 'Cat', 'Description', [
            'view_channel' => false,
            'custom' => true
        ]]);
        $this->assertSame('Description', $result['description']);
        $this->assertFalse($result['permissions']['view_channel']);
        $this->assertTrue($result['permissions']['custom']);
        $this->assertFalse($result['permissions']['manage_channels']); // default preserved
    }

    // ==================== checkBannedWords ====================

    public function testCheckBannedWords_EmptyBannedWords_ReturnsEmpty(): void
    {
        $violations = $this->invokePrivate('checkBannedWords', [[], 'hello world']);
        $this->assertEmpty($violations);
    }

    public function testCheckBannedWords_SingleWord_Found(): void
    {
        $violations = $this->invokePrivate('checkBannedWords', [['spam'], 'this is spam']);
        $this->assertCount(1, $violations);
        $this->assertSame('banned_words', $violations[0]['rule']);
        $this->assertSame('spam', $violations[0]['word']);
        $this->assertSame('high', $violations[0]['severity']);
    }

    public function testCheckBannedWords_CaseInsensitive_Multiple(): void
    {
        $violations = $this->invokePrivate('checkBannedWords', [['bad', 'evil'], 'This BAD and eVil content']);
        $this->assertCount(2, $violations);
        $words = array_column($violations, 'word');
        $this->assertContains('bad', $words);
        $this->assertContains('evil', $words);
    }

    public function testCheckBannedWords_NoMatch_ReturnsEmpty(): void
    {
        $violations = $this->invokePrivate('checkBannedWords', [['spam'], 'clean content']);
        $this->assertEmpty($violations);
    }

    // ==================== checkCapsFilter ====================

    public function testCheckCapsFilter_Disabled_ReturnsEmpty(): void
    {
        $violations = $this->invokePrivate('checkCapsFilter', [
            ['enabled' => false, 'threshold' => 50],
            'HELLO WORLD'
        ]);
        $this->assertEmpty($violations);
    }

    public function testCheckCapsFilter_ShortContent_NoViolation(): void
    {
        $violations = $this->invokePrivate('checkCapsFilter', [
            ['enabled' => true, 'threshold' => 50],
            'ABC' // 3 chars <=10
        ]);
        $this->assertEmpty($violations);
    }

    public function testCheckCapsFilter_BelowThreshold_NoViolation(): void
    {
        $violations = $this->invokePrivate('checkCapsFilter', [
            ['enabled' => true, 'threshold' => 80],
            'Hello World' // 2 caps / 11 chars = 18% <80
        ]);
        $this->assertEmpty($violations);
    }

    public function testCheckCapsFilter_AboveThreshold_ReturnsViolation(): void
    {
        $violations = $this->invokePrivate('checkCapsFilter', [
            ['enabled' => true, 'threshold' => 50],
            'HELLO WORLD!' // all caps, 12 chars, 5 letters? Actually 'HELLOWORL' (excluding non letters?) Let's just craft: 12 chars, 9 caps -> 75% >50
        ]);
        $this->assertCount(1, $violations);
        $this->assertSame('caps_filter', $violations[0]['rule']);
        $this->assertGreaterThan(50, $violations[0]['percentage']);
        $this->assertSame('medium', $violations[0]['severity']);
    }
}
