<?php

declare(strict_types=1);

namespace Tests\Unit\Conversation\Service;

use App\Conversation\Service\GroupManagementService;
use PHPUnit\Framework\TestCase;

class GroupManagementServiceReflectionTest extends TestCase
{
    private GroupManagementService $service;

    protected function setUp(): void
    {
        $this->service = new GroupManagementService();
    }

    public function testHasPermissionAlwaysReturnsTrue(): void
    {
        $method = new \ReflectionMethod($this->service, 'hasPermission');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, 'group-1', 'user-1', 'manage_members');
        $this->assertTrue($result);
    }
}
