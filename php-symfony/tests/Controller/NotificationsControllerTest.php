<?php

declare(strict_types=1);

namespace App\Tests\Controller\Notifications;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\User\Entity\User;

#[AllowMockObjectsWithoutExpectations]
class NotificationsControllerTest extends DatabaseIntegrationTestCase
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

    // SUBSCRIBE Tests (3)
    public function testSubscribe(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/v1/notifications/subscriptions', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'deviceToken' => 'device_token_abc123',
            'deviceType' => 'ios',
            'deviceName' => 'iPhone 15'
        ]));

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function testSubscribeMissingRequiredField(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/v1/notifications/subscriptions', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'deviceToken' => 'device_token_abc123',
            // Missing deviceType
        ]));

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('Missing', $responseData['error']);
    }

    public function testSubscribeWithoutDeviceName(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/v1/notifications/subscriptions', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'deviceToken' => 'device_token_xyz789',
            'deviceType' => 'android'
        ]));

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
    }

    // UNSUBSCRIBE Tests (2)
    public function testUnsubscribe(): void
    {
        $client = self::$client;

        // First subscribe to get a subscription ID
        $client->request('POST', '/api/v1/notifications/subscriptions', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ], json_encode([
            'deviceToken' => 'device_token_unsub',
            'deviceType' => 'ios',
        ]));

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $subscribeData = json_decode($client->getResponse()->getContent(), true);
        $subscriptionId = $subscribeData['data']['id'] ?? null;
        $this->assertNotNull($subscriptionId);

        // Now unsubscribe
        $client->request('DELETE', '/api/v1/notifications/subscriptions/' . $subscriptionId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function testUnsubscribeNotFound(): void
    {
        $client = self::$client;
        $client->request('DELETE', '/api/v1/notifications/subscriptions/nonexistent', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Subscription not found', $responseData['error']);
    }

    // GET SUBSCRIPTIONS Tests (1)
    public function testGetMySubscriptions(): void
    {
        $client = self::$client;
        $client->request('GET', '/api/v1/notifications/subscriptions', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertIsArray($responseData['data']);
    }

    // GET NOTIFICATIONS Tests (3)
    public function testGetMyNotifications(): void
    {
        $client = self::$client;
        $client->request('GET', '/api/v1/notifications', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertIsArray($responseData['data']);
    }

    public function testGetMyNotificationsWithStatus(): void
    {
        $client = self::$client;
        $client->request('GET', '/api/v1/notifications?status=unread', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
    }

    public function testGetMyNotificationsWithLimit(): void
    {
        $client = self::$client;
        $client->request('GET', '/api/v1/notifications?limit=10', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
    }

    // MARK READ Tests (2)
    public function testMarkRead(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/v1/notifications/notif_123/read', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
    }

    public function testMarkReadNotFound(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/v1/notifications/nonexistent/read', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        // Should still return success even if notification doesn't exist (idempotent)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    // PROCESS SCHEDULED Tests (1)
    public function testProcessScheduled(): void
    {
        $client = self::$client;
        $client->request('POST', '/api/v1/notifications/admin/process-scheduled', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Notifications processed', $responseData['message']);
    }
}
