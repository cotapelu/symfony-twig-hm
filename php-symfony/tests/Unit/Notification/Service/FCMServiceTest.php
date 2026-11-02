<?php declare(strict_types=1);

namespace App\Tests\Unit\Notification\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notification\Service\FCMService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FCMServiceTest extends TestCase
{
    public function testSendWithDeviceTokenReturnsTrueOnSuccess(): void
    {
        // Create partial mock to stub out the actual curl request
        $service = $this->getMockBuilder(FCMService::class)
            ->onlyMethods(['executeRequest'])
            ->setConstructorArgs(['test_key', 'test_sender'])
            ->getMock();

        $service->expects($this->once())
            ->method('executeRequest')
            ->willReturn(true);

        $notification = $this->createMock(\App\Notifications\Domain\Entity\PushNotification::class);
        $notification->method('getData')->willReturn(['device_token' => 'token123']);
        $notification->method('getTitle')->willReturn('Title');
        $notification->method('getBody')->willReturn('Body');

        $result = $service->send($notification);
        $this->assertTrue($result);
    }

    public function testSendReturnsFalseWhenNoDeviceToken(): void
    {
        $service = new FCMService('key', 'sender');
        $notification = $this->createMock(\App\Notifications\Domain\Entity\PushNotification::class);
        $notification->method('getData')->willReturn([]);
        $notification->method('getTitle')->willReturn('Title');
        $notification->method('getBody')->willReturn('Body');
        // No device_token in data
        $result = $service->send($notification);
        $this->assertFalse($result);
    }
}
