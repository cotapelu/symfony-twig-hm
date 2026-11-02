<?php

declare(strict_types=1);

namespace App\Tests\Controller\Call;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Call\Controller\CallController;
use App\Call\Service\CallService;
use App\User\Service\AuthService;
use App\Conversation\Entity\Group;
use App\User\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class CallControllerTest extends TestCase
{
    private CallController $controller;
    private CallService|MockObject $callService;
    private AuthService|MockObject $authService;
    private User|MockObject $currentUser;
    private User|MockObject $authenticatedUser;

    protected function setUp(): void
    {
        $this->callService = $this->createMock(CallService::class);
        $this->authService = $this->createMock(AuthService::class);
        $this->currentUser = $this->createMock(User::class);
        $this->authenticatedUser = $this->createMock(User::class);
        $this->authenticatedUser->method('getId')->willReturn('user123');

        $this->controller = new CallController($this->callService, $this->authService);
        $this->controller->setTestUser($this->currentUser);
    }

    public function testGetActiveCallsSuccess(): void
    {
        $request = new Request();
        $activeCalls = [
            ['callId' => 'call1', 'type' => 'video', 'status' => 'active'],
            ['callId' => 'call2', 'type' => 'audio', 'status' => 'active'],
        ];

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->with($this->currentUser)
            ->willReturn($this->authenticatedUser);

        $this->callService
            ->expects($this->once())
            ->method('getActiveCalls')
            ->with($this->authenticatedUser)
            ->willReturn($activeCalls);

        $result = $this->controller->getActiveCalls($request);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('active_calls', $data);
        $this->assertCount(2, $data['active_calls']);
    }

    public function testGetActiveCallsUnauthorized(): void
    {
        $request = new Request();

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->with($this->currentUser)
            ->willThrowException(new \Exception('Authentication failed'));

        $result = $this->controller->getActiveCalls($request);

        $this->assertEquals(401, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Authentication failed', $data['error']);
    }

    public function testStartCall(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['recipientId' => 'user456', 'type' => 'video'])
        );

        $recipient = $this->createMock(User::class);

        $call = $this->createMock(\App\Call\Entity\Call::class);
        $call->method('getCallId')->willReturn('call123');
        $call->method('getType')->willReturn('video');
        $call->method('getStatus')->willReturn('initiating');

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->with($this->currentUser)
            ->willReturn($this->authenticatedUser);

        $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repo->method('find')->with('user456')->willReturn($recipient);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturn($repo);
        $this->callService->method('getDoctrine')->willReturn($doctrine);

        $this->callService
            ->expects($this->once())
            ->method('initiateCall')
            ->with($this->authenticatedUser, $recipient, null, 'video')
            ->willReturn($call);

        $result = $this->controller->startCall($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('call123', $data['call_id']);
    }

    public function testInitiateCallInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->with($this->currentUser)
            ->willReturn($this->authenticatedUser);

        $result = $this->controller->startCall($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testInitiateCallMissingRecipientAndGroup(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['type' => 'video'])
        );

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->with($this->currentUser)
            ->willReturn($this->authenticatedUser);

        $result = $this->controller->startCall($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Recipient or group ID is required', $data['error']);
    }

    public function testInitiateCallWithRecipientSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['recipientId' => 'user456'])
        );

        $recipient = $this->createMock(User::class);

        $call = $this->createMock(\App\Call\Entity\Call::class);
        $call->method('getCallId')->willReturn('call123');
        $call->method('getType')->willReturn('audio');
        $call->method('getStatus')->willReturn('initiating');

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $userRepo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $userRepo->method('find')->with('user456')->willReturn($recipient);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturn($userRepo);
        $this->callService->method('getDoctrine')->willReturn($doctrine);

        $this->callService
            ->expects($this->once())
            ->method('initiateCall')
            ->with($this->authenticatedUser, $recipient, null, 'audio')
            ->willReturn($call);

        $result = $this->controller->startCall($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testInitiateCallWithRecipientNotFound(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['recipientId' => 'notfound'])
        );

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $userRepo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $userRepo->method('find')->with('notfound')->willReturn(null);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturn($userRepo);
        $this->callService->method('getDoctrine')->willReturn($doctrine);

        $result = $this->controller->startCall($request);

        $this->assertEquals(404, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('User not found', $data['error']);
    }

    public function testInitiateCallWithGroupSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['groupId' => 'group123', 'type' => 'audio'])
        );

        $group = $this->createMock(Group::class);

        $call = $this->createMock(\App\Call\Entity\Call::class);
        $call->method('getCallId')->willReturn('call456');
        $call->method('getType')->willReturn('audio');
        $call->method('getStatus')->willReturn('initiating');

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $groupRepo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $groupRepo->method('find')->with('group123')->willReturn($group);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturn($groupRepo);
        $this->callService->method('getDoctrine')->willReturn($doctrine);

        $this->callService
            ->expects($this->once())
            ->method('initiateCall')
            ->with($this->authenticatedUser, null, $group, 'audio')
            ->willReturn($call);

        $result = $this->controller->startCall($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('call456', $data['call_id']);
        $this->assertEquals('audio', $data['type']);
    }

    public function testInitiateCallWithGroupNotFound(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['groupId' => 'notfound'])
        );

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $groupRepo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $groupRepo->method('find')->with('notfound')->willReturn(null);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturn($groupRepo);
        $this->callService->method('getDoctrine')->willReturn($doctrine);

        $result = $this->controller->startCall($request);

        $this->assertEquals(404, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Group not found', $data['error']);
    }

    public function testJoinCallSuccess(): void
    {
        $request = new Request();
        $call = $this->createMock(\App\Call\Entity\Call::class);

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $this->callService
            ->expects($this->once())
            ->method('getCallById')
            ->with(123)
            ->willReturn($call);

        $this->callService
            ->expects($this->once())
            ->method('joinCall')
            ->with($call, $this->authenticatedUser);

        $result = $this->controller->joinCall(123);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testJoinCallNotFound(): void
    {
        $request = new Request();

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $this->callService
            ->expects($this->once())
            ->method('getCallById')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->joinCall(999);

        $this->assertEquals(404, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Call not found', $data['error']);
    }

    public function testAcceptCallSuccess(): void
    {
        $request = new Request();
        $call = $this->createMock(\App\Call\Entity\Call::class);

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $this->callService
            ->expects($this->once())
            ->method('getCallById')
            ->with(123)
            ->willReturn($call);

        $this->callService
            ->expects($this->once())
            ->method('acceptCall')
            ->with($call, $this->authenticatedUser);

        $result = $this->controller->acceptCall(123);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testAcceptCallNotFound(): void
    {
        $request = new Request();

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $this->callService
            ->expects($this->once())
            ->method('getCallById')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->acceptCall(999);

        $this->assertEquals(404, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Call not found', $data['error']);
    }

    public function testRejectCallSuccess(): void
    {
        $request = new Request();
        $call = $this->createMock(\App\Call\Entity\Call::class);

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $this->callService
            ->expects($this->once())
            ->method('getCallById')
            ->with(123)
            ->willReturn($call);

        $this->callService
            ->expects($this->once())
            ->method('rejectCall')
            ->with($call, $this->authenticatedUser);

        $result = $this->controller->rejectCall(123);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testRejectCallNotFound(): void
    {
        $request = new Request();

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $this->callService
            ->expects($this->once())
            ->method('getCallById')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->rejectCall(999);

        $this->assertEquals(404, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Call not found', $data['error']);
    }

    public function testEndCallSuccess(): void
    {
        $request = new Request();
        $call = $this->createMock(\App\Call\Entity\Call::class);

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $this->callService
            ->expects($this->once())
            ->method('getCallById')
            ->with(123)
            ->willReturn($call);

        $this->callService
            ->expects($this->once())
            ->method('endCall')
            ->with($call, $this->authenticatedUser);

        $result = $this->controller->endCall(123, $request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testEndCallNotFound(): void
    {
        $request = new Request();

        $this->authService
            ->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn($this->authenticatedUser);

        $this->callService
            ->expects($this->once())
            ->method('getCallById')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->endCall(999, $request);

        $this->assertEquals(404, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Call not found', $data['error']);
    }
}
