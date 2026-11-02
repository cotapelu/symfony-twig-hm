<?php

declare(strict_types=1);

namespace App\Tests\Integration\Notification;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\User\Entity\User;

#[AllowMockObjectsWithoutExpectations]
class NotificationIntegrationTest extends DatabaseIntegrationTestCase
{
    private string $authToken;
    private ?User $testUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        $client = self::$client;
        $entityManager = self::$entityManager;

        // Register a test user
        $email = 'test.notif.' . time() . '@example.com';
        $password = 'StrongPass123!';

        $client->request('POST', '/api/register', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
            'name' => 'Notification Test User',
            'phoneNumber' => '0123456789',
        ]));

        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->authToken = $data['token'];
        $userId = $data['user']['id'] ?? null;
        $this->assertNotNull($userId, 'Register response missing user.id');

        // Load user entity for cleanup
        $this->testUser = $entityManager->find(User::class, $userId);
        $this->assertInstanceOf(User::class, $this->testUser);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testSendNotification(): void
    {
        $client = self::$client;

        $client->request('POST', '/api/notifications/send', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'type' => 'info',
            'title' => 'Test Title',
            'message' => 'Test message body',
            'channel' => 'email',
        ]));

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame(201, $data['status']);
        $this->assertArrayHasKey('data', $data);
    }

    public function testGetUserNotifications(): void
    {
        $client = self::$client;

        // Send notifications first
        $client->request('POST', '/api/notifications/send', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'type' => 'alert',
            'title' => 'Alert 1',
            'message' => 'Msg1',
            'channel' => 'push',
        ]));

        $client->request('POST', '/api/notifications/send', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'type' => 'info',
            'title' => 'Info 2',
            'message' => 'Msg2',
            'channel' => 'email',
        ]));

        $client->request('GET', '/api/notifications', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame(200, $data['status']);
        $this->assertArrayHasKey('data', $data);
        $this->assertGreaterThanOrEqual(2, count($data['data']));
    }

    public function testMarkAsRead(): void
    {
        $client = self::$client;

        // Send a notification
        $client->request('POST', '/api/notifications/send', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'type' => 'info',
            'title' => 'Read me',
            'message' => 'Please read',
            'channel' => 'in_app',
        ]));

        $sendData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $sendData);
        $notificationId = $sendData['data']['id'] ?? null;
        $this->assertNotNull($notificationId);

        // Mark as read
        $client->request('PUT', '/api/notifications/' . $notificationId . '/read', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame(200, $data['status']);
    }

    public function testMarkAllAsRead(): void
    {
        $client = self::$client;

        // Send notifications
        $client->request('POST', '/api/notifications/send', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'type' => 'info',
            'title' => 'A',
            'message' => 'A',
            'channel' => 'push',
        ]));

        $client->request('POST', '/api/notifications/send', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'type' => 'info',
            'title' => 'B',
            'message' => 'B',
            'channel' => 'push',
        ]));

        $client->request('PUT', '/api/notifications/read-all', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame(200, $data['status']);
        $this->assertArrayHasKey('data', $data);
        $this->assertGreaterThanOrEqual(0, $data['data']['marked'] ?? 0);
    }

    public function testDeleteNotification(): void
    {
        $client = self::$client;

        // Send a notification
        $client->request('POST', '/api/notifications/send', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'type' => 'info',
            'title' => 'Delete me',
            'message' => 'Delete',
            'channel' => 'push',
        ]));

        $sendData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $sendData);
        $notificationId = $sendData['data']['id'] ?? null;
        $this->assertNotNull($notificationId);

        // Delete it
        $client->request('DELETE', '/api/notifications/' . $notificationId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }

    public function testSetAndGetPreferences(): void
    {
        $client = self::$client;

        // Set preference
        $client->request('POST', '/api/notifications/preferences', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'type' => 'email',
            'enabled' => false,
        ]));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Get preferences
        $client->request('GET', '/api/notifications/preferences?type=email', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame(200, $data['status']);
        $this->assertArrayHasKey('data', $data);
    }
}
