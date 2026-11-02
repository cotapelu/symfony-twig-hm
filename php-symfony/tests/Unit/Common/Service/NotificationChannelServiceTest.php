<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\NotificationChannelService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationChannelServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new NotificationChannelService();
        $result = $service->doSomething('test');
        $this->assertSame('NotificationChannelService: test', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new NotificationChannelService();
        $result = $service->doSomething('');
        $this->assertSame('NotificationChannelService: ', $result);
    }

    public function testDoSomethingHandlesSpecialCharacters(): void
    {
        $service = new NotificationChannelService();
        $result = $service->doSomething('a & b <c>');
        $this->assertSame('NotificationChannelService: a & b <c>', $result);
    }
}