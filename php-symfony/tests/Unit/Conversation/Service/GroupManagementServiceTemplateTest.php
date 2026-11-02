<?php

declare(strict_types=1);

namespace Tests\Unit\Conversation\Service;

use App\Conversation\Service\GroupManagementService;
use PHPUnit\Framework\TestCase;

class GroupManagementServiceTemplateTest extends TestCase
{
    private GroupManagementService $service;

    protected function setUp(): void
    {
        $this->service = new GroupManagementService();
    }

    private function invokeApplyGroupTemplate(array $group, string $template): array
    {
        $method = new \ReflectionMethod($this->service, 'applyGroupTemplate');
        $method->setAccessible(true);
        return $method->invoke($this->service, $group, $template);
    }

    public function testApplyGroupTemplate_Team(): void
    {
        $group = ['id'=>'g1','name'=>'Team Group','settings'=>[],'template'=>null];
        $result = $this->invokeApplyGroupTemplate($group, 'team');
        $this->assertSame('team', $result['template']);
        $this->assertSame(false, $result['settings']['allow_guests']);
        $this->assertSame(false, $result['settings']['require_approval']);
        $this->assertSame(50, $result['settings']['max_members']);
        $this->assertSame(true, $result['settings']['voice_channels_enabled']);
        $this->assertSame(true, $result['settings']['video_calls_enabled']);
    }

    public function testApplyGroupTemplate_Community(): void
    {
        $group = ['id'=>'g2','name'=>'Community Group','settings'=>[],'template'=>null];
        $result = $this->invokeApplyGroupTemplate($group, 'community');
        $this->assertSame('community', $result['template']);
        $this->assertSame(true, $result['settings']['allow_guests']);
        $this->assertSame(true, $result['settings']['require_approval']);
        $this->assertSame(10000, $result['settings']['max_members']);
        $this->assertSame(true, $result['settings']['voice_channels_enabled']);
        $this->assertSame(false, $result['settings']['video_calls_enabled']);
    }

    public function testApplyGroupTemplate_Project(): void
    {
        $group = ['id'=>'g3','name'=>'Project Group','settings'=>[],'template'=>null];
        $result = $this->invokeApplyGroupTemplate($group, 'project');
        $this->assertSame('project', $result['template']);
        $this->assertSame(false, $result['settings']['allow_guests']);
        $this->assertSame(false, $result['settings']['require_approval']);
        $this->assertSame(20, $result['settings']['max_members']);
        $this->assertSame(true, $result['settings']['voice_channels_enabled']);
        $this->assertSame(true, $result['settings']['video_calls_enabled']);
    }

    public function testApplyGroupTemplate_Study(): void
    {
        $group = ['id'=>'g4','name'=>'Study Group','settings'=>[],'template'=>null];
        $result = $this->invokeApplyGroupTemplate($group, 'study');
        $this->assertSame('study', $result['template']);
        $this->assertSame(true, $result['settings']['allow_guests']);
        $this->assertSame(false, $result['settings']['require_approval']);
        $this->assertSame(100, $result['settings']['max_members']);
        $this->assertSame(true, $result['settings']['voice_channels_enabled']);
        $this->assertSame(true, $result['settings']['video_calls_enabled']);
    }

    public function testApplyGroupTemplate_Gaming(): void
    {
        $group = ['id'=>'g5','name'=>'Gaming Group','settings'=>[],'template'=>null];
        $result = $this->invokeApplyGroupTemplate($group, 'gaming');
        $this->assertSame('gaming', $result['template']);
        $this->assertSame(false, $result['settings']['allow_guests']);
        $this->assertSame(false, $result['settings']['require_approval']);
        $this->assertSame(1000, $result['settings']['max_members']);
        $this->assertSame(true, $result['settings']['voice_channels_enabled']);
        $this->assertSame(false, $result['settings']['video_calls_enabled']);
    }

    public function testApplyGroupTemplate_UnknownTemplate_NoChanges(): void
    {
        $group = ['id'=>'g7','name'=>'Unknown Group','settings'=>['max_members'=>10],'template'=>null];
        $result = $this->invokeApplyGroupTemplate($group, 'unknown');
        $this->assertNull($result['template']);
        $this->assertSame(['max_members'=>10], $result['settings']);
    }
}
