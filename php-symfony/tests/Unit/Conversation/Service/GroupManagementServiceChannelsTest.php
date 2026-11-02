<?php

declare(strict_types=1);

namespace Tests\Unit\Conversation\Service;

use App\Conversation\Service\GroupManagementService;
use PHPUnit\Framework\TestCase;

class GroupManagementServiceChannelsTest extends TestCase
{
    private GroupManagementService $service;

    protected function setUp(): void
    {
        $this->service = new GroupManagementService();
    }

    private function invokeCreateDefaultChannels(string $groupId, ?string $template): array
    {
        $method = new \ReflectionMethod($this->service, 'createDefaultChannels');
        $method->setAccessible(true);
        return $method->invoke($this->service, $groupId, $template);
    }

    public function testCreateDefaultChannels_Team(): void
    {
        $groupId = 'g1';
        $channels = $this->invokeCreateDefaultChannels($groupId, 'team');
        $this->assertCount(4, $channels);
        $this->assertSame(['general','random','projects','meetings'], array_column($channels, 'name'));
        $this->assertChannelStructure($channels[0], $groupId);
    }

    public function testCreateDefaultChannels_Community(): void
    {
        $groupId = 'g2';
        $channels = $this->invokeCreateDefaultChannels($groupId, 'community');
        $this->assertCount(4, $channels);
        $this->assertSame(['welcome','general','off-topic','announcements'], array_column($channels, 'name'));
        $this->assertChannelStructure($channels[0], $groupId);
    }

    public function testCreateDefaultChannels_Project(): void
    {
        $groupId = 'g3';
        $channels = $this->invokeCreateDefaultChannels($groupId, 'project');
        $this->assertCount(5, $channels);
        $this->assertSame(['general','planning','development','design','testing'], array_column($channels, 'name'));
        $this->assertChannelStructure($channels[0], $groupId);
    }

    public function testCreateDefaultChannels_Study(): void
    {
        $groupId = 'g4';
        $channels = $this->invokeCreateDefaultChannels($groupId, 'study');
        $this->assertCount(4, $channels);
        $this->assertSame(['general','study-help','resources','off-topic'], array_column($channels, 'name'));
        $this->assertChannelStructure($channels[0], $groupId);
    }

    public function testCreateDefaultChannels_Gaming(): void
    {
        $groupId = 'g5';
        $channels = $this->invokeCreateDefaultChannels($groupId, 'gaming');
        $this->assertCount(4, $channels);
        $this->assertSame(['general','game-discussion','tournaments','voice-chat'], array_column($channels, 'name'));
        $this->assertChannelStructure($channels[0], $groupId);
    }

    public function testCreateDefaultChannels_Unknown(): void
    {
        $groupId = 'g6';
        $channels = $this->invokeCreateDefaultChannels($groupId, 'unknown');
        $this->assertCount(1, $channels);
        $this->assertSame(['general'], array_column($channels, 'name'));
        $this->assertChannelStructure($channels[0], $groupId);
    }

    public function testCreateDefaultChannels_Null(): void
    {
        $groupId = 'g7';
        $channels = $this->invokeCreateDefaultChannels($groupId, null);
        $this->assertCount(1, $channels);
        $this->assertSame(['general'], array_column($channels, 'name'));
        $this->assertChannelStructure($channels[0], $groupId);
    }

    private function assertChannelStructure(array $channel, string $groupId): void
    {
        $this->assertArrayHasKey('id', $channel);
        $this->assertSame($groupId, $channel['group_id']);
        $this->assertSame('text', $channel['type']);
        $this->assertArrayHasKey('description', $channel);
        $this->assertSame(0, $channel['position']);
        $this->assertSame(['read_messages'=>true,'send_messages'=>true,'upload_files'=>true], $channel['permissions']);
        $this->assertArrayHasKey('created_at', $channel);
    }
}
