<?php

declare(strict_types=1);

namespace App\Tests\Controller\Realtime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Controller\PresenceController;
use App\Realtime\Service\PresenceService;
use App\User\Service\AuthService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\User\Entity\User;

#[AllowMockObjectsWithoutExpectations]
class PresenceControllerTest extends TestCase
{
    private PresenceController $controller;
    private PresenceService|MockObject $presenceService;
    private AuthService|MockObject $authService;
    private User|MockObject $currentUser;

    protected function setUp(): void
    {
        $this->presenceService = $this->createMock(PresenceService::class);
        $this->authService = $this->createMock(AuthService::class);
        $this->currentUser = $this->createMock(User::class);
        $this->currentUser->method('getId')->willReturn('user123');

        $this->controller = new PresenceController($this->presenceService, $this->authService);
    }

    public function testSetUserOnlineSuccess(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUserId')
            ->with($this->currentUser)
            ->willReturn('user123');

        $this->presenceService
            ->expects($this->once())
            ->method('setUserOnline')
            ->with('user123')
            ->willReturn(['success' => true, 'status' => 'online']);

        $request = new Request();
        $result = $this->controller->setUserOnline($request);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('online', $data['status']);
    }

    public function testSetUserOnlineUnauthorized(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUserId')
            ->with($this->currentUser)
            ->willThrowException(new \Exception('Authentication required'));

        $this->presenceService
            ->expects($this->never())
            ->method('setUserOnline');

        $request = new Request();
        $result = $this->controller->setUserOnline($request);

        $this->assertEquals(401, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Authentication required', $data['error']);
    }

    public function testSetUserOnlineReturnsPresenceData(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUserId')
            ->willReturn('user123');

        $this->presenceService
            ->expects($this->once())
            ->method('setUserOnline')
            ->with('user123')
            ->willReturn([
                'success' => true,
                'userId' => 'user123',
                'status' => 'online',
                'lastSeen' => '2024-01-01 10:00:00',
            ]);

        $request = new Request();
        $result = $this->controller->setUserOnline($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('user123', $data['userId']);
        $this->assertEquals('online', $data['status']);
        $this->assertArrayHasKey('lastSeen', $data);
    }

    public function testSetUserOnlineAlreadyOnline(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUserId')
            ->willReturn('user123');

        $this->presenceService
            ->expects($this->once())
            ->method('setUserOnline')
            ->with('user123')
            ->willReturn(['success' => true, 'status' => 'online', 'message' => 'Already online']);

        $request = new Request();
        $result = $this->controller->setUserOnline($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->arrayHasKey('message', $data);
    }

    public function testSetUserOnlineWithDifferentUser(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUserId')
            ->willReturn('user456');

        $this->presenceService
            ->expects($this->once())
            ->method('setUserOnline')
            ->with('user456')
            ->willReturn(['success' => true, 'status' => 'online']);

        $request = new Request();
        $result = $this->controller->setUserOnline($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testSetUserOnlineReturnsCorrectStructure(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUserId')
            ->willReturn('user123');

        $this->presenceService
            ->expects($this->once())
            ->method('setUserOnline')
            ->willReturn(['success' => true]);

        $request = new Request();
        $result = $this->controller->setUserOnline($request);

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertIsBool($data['success']);
    }

    public function testSetUserOnlineHandlesExceptionFromAuthService(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUserId')
            ->willThrowException(new \RuntimeException('Token expired'));

        $request = new Request();
        $result = $this->controller->setUserOnline($request);

        $this->assertEquals(401, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Token expired', $data['error']);
    }
}
