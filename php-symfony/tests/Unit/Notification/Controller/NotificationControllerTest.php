<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Controller;

use PHPUnit\Framework\TestCase;
use App\Notification\Controller\NotificationController;
use App\Notification\Service\NotificationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SimpleNotification { public int $id; public function __construct(int $id) { $this->id = $id; } public function getId(): int { return $this->id; } }

class NotificationControllerTest extends TestCase
{
    private NotificationService $service;
    private NotificationController $controller;

    protected function setUp(): void
    {
        $this->service = $this->createMock(NotificationService::class);
        $this->controller = new NotificationController($this->service);
        $this->setupContainer();
    }

    private function setupContainer(): void
    {
        $container = new ContainerBuilder();
        $normalizer = new ObjectNormalizer();
        $serializer = new Serializer([$normalizer], [new JsonEncoder()]);
        $container->set('serializer', $serializer);
        $this->controller->setContainer($container);
    }

    private function setTestUser(int $userId): void
    {
        $user = new class($userId) implements \Symfony\Component\Security\Core\User\UserInterface {
            private int $id;
            public function __construct(int $id) { $this->id = $id; }
            public function getUserIdentifier(): string { return (string)$this->id; }
            public function getId(): string { return (string)$this->id; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function eraseCredentials() {}
        };
        $this->controller->setTestUser($user);
    }

    private function jsonRequest(array $data = []): Request
    {
        return Request::create('/api/notifications/test', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    // ==================== Tests ====================

    public function testSendNotification_Success(): void
    {
        $this->setTestUser(123);
        $payload = [
            'type' => 'announcement',
            'title' => 'Test Title',
            'message' => 'Test Message',
            'channel' => 'in_app',
        ];
        $this->service->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->equalTo(123),
                $this->equalTo('announcement'),
                $this->equalTo('Test Title'),
                $this->equalTo('Test Message'),
                $this->equalTo('in_app'),
                $this->equalTo([])
            )
            ->willReturn(new SimpleNotification(1));

        $request = $this->jsonRequest($payload);
        $response = $this->controller->sendNotification($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertSame(1, $data['data']['id']);
    }

    public function testSendNotification_InvalidJson(): void
    {
        $this->setTestUser(123);
        $request = Request::create('/api/notifications/send', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        $response = $this->controller->sendNotification($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSendNotification_MissingType(): void
    {
        $this->setTestUser(123);
        $payload = ['title' => 'T', 'message' => 'M'];
        $request = $this->jsonRequest($payload);
        $response = $this->controller->sendNotification($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Type is required', $data['error']);
    }

    public function testSendNotification_InvalidChannel(): void
    {
        $this->setTestUser(123);
        $payload = ['type' => 'announcement', 'title' => 'T', 'message' => 'M', 'channel' => 'carrier_pigeon'];
        $request = $this->jsonRequest($payload);
        $response = $this->controller->sendNotification($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Invalid channel', $data['error']);
    }

    public function testGetNotifications_Success(): void
    {
        $this->setTestUser(123);
        $notifications = [['id' => 1, 'title' => 'N1'], ['id' => 2, 'title' => 'N2']];
        $this->service->expects($this->once())
            ->method('getUserNotificationsPaginated')
            ->with(
                $this->equalTo(123),
                $this->equalTo(false),
                $this->equalTo(1),
                $this->equalTo(20)
            )
            ->willReturn(['data' => $notifications, 'total' => 2]);

        $request = Request::create('/api/notifications', 'GET', [], [], [], ['HTTP_HOST' => 'localhost']);
        $response = $this->controller->getNotifications($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(2, $data['data']);
        $this->assertArrayHasKey('pagination', $data);
    }

    public function testMarkAsRead_Success(): void
    {
        $this->setTestUser(123);
        $this->service->expects($this->once())
            ->method('markAsRead')
            ->with($this->equalTo(1), $this->equalTo(123))
            ->willReturn(new SimpleNotification(1));

        $request = new Request(); // PUT
        $response = $this->controller->markAsRead($request, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertSame(1, $data['data']['id']);
    }
}
