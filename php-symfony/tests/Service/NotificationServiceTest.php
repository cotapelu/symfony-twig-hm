<?php

declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notification\Service\NotificationService;
use App\Tests\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationServiceTest extends TestCase
{
    private $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = new NotificationService();
    }

    public function testSendNotification(): void
    {
        $result = $this->notificationService->sendNotification(
            'user_123',
            'test_notification',
            ['key' => 'value'],
            'Test Title',
            'Test Body'
        );

        $this->assertTrue($result);
    }

    public function testSendBulkNotification(): void
    {
        $userIds = ['user_1', 'user_2', 'user_3'];

        $result = $this->notificationService->sendBulkNotification(
            $userIds,
            'bulk_notification',
            ['key' => 'value'],
            'Bulk Title',
            'Bulk Body'
        );

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertTrue($result['user_1']);
        $this->assertTrue($result['user_2']);
        $this->assertTrue($result['user_3']);
    }

    public function testNotifyNewMessage(): void
    {
        $result = $this->notificationService->notifyNewMessage(
            'conv_123',
            'user_1',
            'Test message content',
            ['user_1', 'user_2', 'user_3']
        );

        $this->assertNull($result);
    }

    public function testNotifyReactionAdded(): void
    {
        $result = $this->notificationService->notifyReactionAdded(
            'msg_123',
            'user_2',
            '👍',
            'user_1'
        );

        $this->assertNull($result);
    }

    public function testNotifyReactionAddedToSelf(): void
    {
        $result = $this->notificationService->notifyReactionAdded(
            'msg_123',
            'user_1',
            '👍',
            'user_1'
        );

        $this->assertNull($result);
    }

    public function testNotifyParticipantChange(): void
    {
        $result = $this->notificationService->notifyParticipantChange(
            'conv_123',
            'user_2',
            'added',
            ['user_1', 'user_3']
        );

        $this->assertNull($result);
    }

    public function testRegisterDeviceToken(): void
    {
        $result = $this->notificationService->registerDeviceToken(
            'user_123',
            'device_token_here',
            'web'
        );

        $this->assertTrue($result);
    }

    public function testUnregisterDeviceToken(): void
    {
        $result = $this->notificationService->unregisterDeviceToken(
            'user_123',
            'device_token_here'
        );

        $this->assertTrue($result);
    }

    public function testGetNotificationTitle(): void
    {
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('getNotificationTitle');
        $method->setAccessible(true);

        $result = $method->invoke($this->notificationService, 'new_message');

        $this->assertNotEmpty($result);
    }

    public function testGenerateNotificationBodyForMessage(): void
    {
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('generateNotificationBody');
        $method->setAccessible(true);

        $result = $method->invoke($this->notificationService, 'message', [
            'sender_name' => 'John Doe',
            'message' => 'Hello there! How are you doing today?',
        ]);

        $this->assertStringContainsString('John Doe', $result);
        $this->assertStringContainsString('Hello there!', $result);
    }

    public function testGenerateNotificationBodyForCall(): void
    {
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('generateNotificationBody');
        $method->setAccessible(true);

        $result = $method->invoke($this->notificationService, 'call', [
            'caller_name' => 'Jane Smith',
            'call_type' => 'video',
        ]);

        $this->assertStringContainsString('Jane Smith', $result);
        $this->assertStringContainsString('video', $result);
    }

    public function testGenerateNotificationBodyForGroup(): void
    {
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('generateNotificationBody');
        $method->setAccessible(true);

        $result = $method->invoke($this->notificationService, 'group', [
            'group_name' => 'Engineering Team',
            'action' => 'updated',
        ]);

        $this->assertStringContainsString('Engineering Team', $result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testGenerateNotificationBodyForMention(): void
    {
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('generateNotificationBody');
        $method->setAccessible(true);

        $result = $method->invoke($this->notificationService, 'mention', [
            'sender_name' => 'Bob',
            'message' => 'Hey Alice, can you check this out?',
        ]);

        $this->assertStringContainsString('Bob', $result);
        $this->assertStringContainsString('mentioned you', $result);
    }

    public function testGenerateNotificationBodyForSystem(): void
    {
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('generateNotificationBody');
        $method->setAccessible(true);

        $result = $method->invoke($this->notificationService, 'system', [
            'message' => 'Server maintenance scheduled for tonight',
        ]);

        $this->assertEquals('Server maintenance scheduled for tonight', $result);
    }

    public function testGenerateNotificationBodyForUnknownType(): void
    {
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('generateNotificationBody');
        $method->setAccessible(true);

        $result = $method->invoke($this->notificationService, 'unknown_type', []);

        $this->assertEquals('You have a new notification', $result);
    }
}
