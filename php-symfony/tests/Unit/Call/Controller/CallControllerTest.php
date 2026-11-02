<?php

declare(strict_types=1);

namespace Tests\Unit\Call\Controller;

use App\Call\Controller\CallController;
use App\Call\Service\CallService;
use App\User\Service\AuthService;
use App\User\Entity\User;
use App\Conversation\Entity\Group;
use App\Call\Entity\CallRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;

class CallControllerTest extends TestCase
{
    private CallService $serviceMock;
    private AuthService $authServiceMock;
    private CallController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(CallService::class);
        $this->authServiceMock = $this->createMock(AuthService::class);
        $this->controller = new CallController($this->serviceMock, $this->authServiceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setAuthenticatedUser(?User $user = null): void
    {
        $this->controller->setTestUser($user);
        if ($user !== null) {
            $this->authServiceMock->method('getAuthenticatedUser')
                ->willReturn($user);
        }
    }

    private function createUser(int $id, string $email): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getUserIdentifier')->willReturn($email);
        $user->method('getEmail')->willReturn($email);
        return $user;
    }

    private function createGroup(int $id): Group
    {
        $group = $this->createMock(Group::class);
        $group->method('getId')->willReturn($id);
        return $group;
    }

    private function createCallRecord(int $id, int $callerId, ?int $receiverId, string $type, string $status = 'initiated'): CallRecord
    {
        $call = new CallRecord($callerId, $receiverId, $type);
        $call->setId($id);
        // Adjust status if needed
        if ($status === 'answered') {
            $call->setAnswered();
        } elseif ($status === 'ended') {
            $call->end();
        } elseif ($status === 'missed') {
            $call->markMissed();
        }
        return $call;
    }

    // ==================== initiateCall ====================

    public function testInitiateCall_WithRecipient_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $recipient = $this->createUser(2, 'user2@example.com');
        $call = $this->createCallRecord(100, 1, 2, 'video', 'initiated');

        $this->setAuthenticatedUser($user);


        $doctrine = $this->createMock(ManagerRegistry::class);
        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->with(2)->willReturn($recipient);
        $doctrine->method('getRepository')->with(User::class)->willReturn($repo);
        $this->serviceMock->method('getDoctrine')->willReturn($doctrine);
        $this->serviceMock->method('initiateCall')->with($user, $recipient, null, 'video')->willReturn($call);

        $request = Request::create('/calls', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => 2,
            'type' => 'video',
        ]));

        $response = $this->controller->startCall($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(100, $data['call_id']);
        $this->assertSame('video', $data['type']);
        $this->assertSame('initiated', $data['status']);
    }

    public function testInitiateCall_WithGroup_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $group = $this->createGroup(10);
        $call = $this->createCallRecord(101, 1, null, 'audio', 'initiated');

        $this->setAuthenticatedUser($user);
        $this->authServiceMock->method('getAuthenticatedUser')->willReturn($user);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $groupRepo = $this->createMock(ObjectRepository::class);
        $groupRepo->method('find')->with(10)->willReturn($group);
        $doctrine->method('getRepository')->with(Group::class)->willReturn($groupRepo);
        $this->serviceMock->method('getDoctrine')->willReturn($doctrine);
        $this->serviceMock->method('initiateCall')->with($user, null, $group, 'audio')->willReturn($call);

        $request = Request::create('/calls', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'groupId' => 10,
            'type' => 'audio',
        ]));

        $response = $this->controller->startCall($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(101, $data['call_id']);
    }

    public function testInitiateCall_MissingRecipientAndGroup_Returns400(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);
        $this->authServiceMock->method('getAuthenticatedUser')->willReturn($user);

        $request = Request::create('/calls', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['type' => 'video']));

        $response = $this->controller->startCall($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Recipient or group ID is required', $data['error']);
    }

    public function testInitiateCall_RecipientNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);
        $this->authServiceMock->method('getAuthenticatedUser')->willReturn($user);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->with(999)->willReturn(null);
        $doctrine->method('getRepository')->with(User::class)->willReturn($repo);
        $this->serviceMock->method('getDoctrine')->willReturn($doctrine);

        $request = Request::create('/calls', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['recipientId' => 999]));

        $response = $this->controller->startCall($request);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('User not found', $data['error']);
    }

    public function testInitiateCall_GroupNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);
        $this->authServiceMock->method('getAuthenticatedUser')->willReturn($user);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->with(999)->willReturn(null);
        $doctrine->method('getRepository')->with(Group::class)->willReturn($repo);
        $this->serviceMock->method('getDoctrine')->willReturn($doctrine);

        $request = Request::create('/calls', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['groupId' => 999]));

        $response = $this->controller->startCall($request);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Group not found', $data['error']);
    }

    public function testInitiateCall_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null); // no user

        $request = Request::create('/calls', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['recipientId' => 2]));

        $response = $this->controller->startCall($request);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testInitiateCall_ServiceThrowsException_Returns500(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);
        $this->authServiceMock->method('getAuthenticatedUser')->willReturn($user);

        $recipient = $this->createUser(2, 'recipient@example.com');
        $doctrine = $this->createMock(ManagerRegistry::class);
        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->with(2)->willReturn($recipient);
        $doctrine->method('getRepository')->with(User::class)->willReturn($repo);
        $this->serviceMock->method('getDoctrine')->willReturn($doctrine);

        $this->serviceMock->method('initiateCall')->willThrowException(new \Exception('DB error'));

        $request = Request::create('/calls', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['recipientId' => 2]));

        $response = $this->controller->startCall($request);
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    // ==================== startCall ====================

    public function testStartCall_WithRecipient_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $recipient = $this->createUser(2, 'user2@example.com');
        $call = $this->createCallRecord(200, 1, 2, 'audio', 'initiated');

        $this->setAuthenticatedUser($user);
        $this->authServiceMock->method('getAuthenticatedUser')->willReturn($user);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->with(2)->willReturn($recipient);
        $doctrine->method('getRepository')->with(User::class)->willReturn($repo);
        $this->serviceMock->method('getDoctrine')->willReturn($doctrine);
        $this->serviceMock->method('initiateCall')->with($user, $recipient, null, 'audio')->willReturn($call);

        $request = Request::create('/calls/start', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => 2,
            'type' => 'audio',
        ]));

        $response = $this->controller->startCall($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(200, $data['call_id']);
    }

    public function testStartCall_WithGroup_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $group = $this->createGroup(20);
        $call = $this->createCallRecord(201, 1, null, 'video', 'initiated');

        $this->setAuthenticatedUser($user);
        $this->authServiceMock->method('getAuthenticatedUser')->willReturn($user);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $groupRepo = $this->createMock(ObjectRepository::class);
        $groupRepo->method('find')->with(20)->willReturn($group);
        $doctrine->method('getRepository')->with(Group::class)->willReturn($groupRepo);
        $this->serviceMock->method('getDoctrine')->willReturn($doctrine);
        $this->serviceMock->method('initiateCall')->with($user, null, $group, 'video')->willReturn($call);

        $request = Request::create('/calls/start', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'groupId' => 20,
            'type' => 'video',
        ]));

        $response = $this->controller->startCall($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(201, $data['call_id']);
    }

    public function testStartCall_MissingRecipientAndGroup_Returns400(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);
        $this->authServiceMock->method('getAuthenticatedUser')->willReturn($user);

        $request = Request::create('/calls/start', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['type' => 'audio']));

        $response = $this->controller->startCall($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Recipient or group ID is required', $data['error']);
    }

    // ==================== getCall ====================

    public function testGetCall_Success_AsCaller(): void
    {
        $call = $this->createCallRecord(100, 1, 2, 'video', 'ended');

        $this->serviceMock->method('getCall')->with(100)->willReturn($call);

        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $response = $this->controller->getCall(100);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
    }

    public function testGetCall_Success_AsReceiver(): void
    {
        $call = $this->createCallRecord(100, 1, 2, 'video', 'ended');
        $this->serviceMock->method('getCall')->with(100)->willReturn($call);

        $user = $this->createUser(2, 'user2@example.com');
        $this->setAuthenticatedUser($user);

        $response = $this->controller->getCall(100);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetCall_NotFound_Returns404(): void
    {
        $this->serviceMock->method('getCall')->with(999)->willReturn(null);
        $this->setAuthenticatedUser($this->createUser(1, 'u1@ex.com'));

        $response = $this->controller->getCall(999);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testGetCall_AccessDenied_Returns403(): void
    {
        $call = $this->createCallRecord(100, 1, 2, 'video', 'ended');
        $this->serviceMock->method('getCall')->with(100)->willReturn($call);
        $user = $this->createUser(3, 'user3@example.com'); // not involved
        $this->setAuthenticatedUser($user);

        $response = $this->controller->getCall(100);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testGetCall_Unauthenticated_Returns401(): void
    {
        $this->serviceMock->method('getCall')->willReturn($this->createCallRecord(100, 1, 2, 'video'));
        $this->setAuthenticatedUser(null);

        $response = $this->controller->getCall(100);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    // ==================== getUserCalls ====================

    public function testGetUserCalls_Success(): void
    {
        $calls = [
            $this->createCallRecord(1, 5, 6, 'video', 'ended'),
            $this->createCallRecord(2, 5, 7, 'audio', 'missed'),
        ];
        $this->serviceMock->method('getUserCalls')->with(5)->willReturn($calls);

        $user = $this->createUser(5, 'user5@example.com');
        $this->setAuthenticatedUser($user);

        $response = $this->controller->getUserCalls(5);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertCount(2, $data['data']);
    }

    public function testGetUserCalls_UserMismatch_Returns403(): void
    {
        $this->setAuthenticatedUser($this->createUser(1, 'u1@ex.com')); // current user 1, requested userId 5
        $response = $this->controller->getUserCalls(5);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testGetUserCalls_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);
        $response = $this->controller->getUserCalls(5);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    // ==================== getRecentCalls ====================

    public function testGetRecentCalls_Success(): void
    {
        $calls = [
            $this->createCallRecord(1, 5, 6, 'video', 'ended'),
            $this->createCallRecord(2, 5, 7, 'audio', 'missed'),
        ];
        $this->serviceMock->method('getRecentCalls')->with(5, 5)->willReturn($calls);

        $user = $this->createUser(5, 'user5@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/calls/user/5/recent', 'GET', ['limit' => '5']);
        $response = $this->controller->getRecentCalls(5, $request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertCount(2, $data['data']);
    }

    public function testGetRecentCalls_DefaultLimit(): void
    {
        $calls = [];
        $this->serviceMock->method('getRecentCalls')->with(5, 10)->willReturn($calls);
        $user = $this->createUser(5, 'user5@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/calls/user/5/recent', 'GET'); // no limit
        $response = $this->controller->getRecentCalls(5, $request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetRecentCalls_InvalidLimitTooSmall_Returns400(): void
    {
        $user = $this->createUser(5, 'user5@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/calls/user/5/recent', 'GET', ['limit' => '0']);
        $response = $this->controller->getRecentCalls(5, $request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testGetRecentCalls_InvalidLimitTooLarge_Returns400(): void
    {
        $user = $this->createUser(5, 'user5@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/calls/user/5/recent', 'GET', ['limit' => '101']);
        $response = $this->controller->getRecentCalls(5, $request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // ==================== getMissedCalls ====================

    public function testGetMissedCalls_Success(): void
    {
        $calls = [$this->createCallRecord(1, 6, 5, 'video', 'missed')];
        $this->serviceMock->method('getMissedCalls')->with(6)->willReturn($calls);

        $user = $this->createUser(6, 'user6@example.com');
        $this->setAuthenticatedUser($user);

        $response = $this->controller->getMissedCalls(6);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertCount(1, $data['data']);
    }

    // ==================== getCallStats ====================

    public function testGetCallStats_Success(): void
    {
        $stats = [
            'total_calls' => 50,
            'answered' => 45,
            'missed' => 5,
            'total_duration_seconds' => 3000,
            'avg_duration_seconds' => 60,
        ];
        $this->serviceMock->method('getCallStats')->with(7)->willReturn($stats);

        $user = $this->createUser(7, 'user7@example.com');
        $this->setAuthenticatedUser($user);

        $response = $this->controller->getCallStats(7);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($stats, $data['data']);
    }

    // ==================== answerCall ====================

    public function testAnswerCall_Success(): void
    {
        $call = $this->createCallRecord(100, 1, 2, 'video', 'answered');
        $this->serviceMock->method('answerCall')->with(100)->willReturn($call);

        $response = $this->controller->answerCall(100);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
    }

    public function testAnswerCall_NotFound_Returns404(): void
    {
        $this->serviceMock->method('answerCall')->with(999)->willReturn(null);
        $response = $this->controller->answerCall(999);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    // ==================== endCall ====================

    public function testEndCall_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $call = $this->createCallRecord(100, 1, 2, 'video', 'ended');

        $this->setAuthenticatedUser($user);
        $this->authServiceMock->method('getAuthenticatedUser')->willReturn($user);
        $this->serviceMock->method('getCallById')->with(100)->willReturn($call);
        $this->serviceMock->expects($this->once())->method('endCall')->with($call, $user);

        $response = $this->controller->endCall(100, new Request());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testEndCall_NotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);
        $this->serviceMock->method('getCallById')->with(999)->willReturn(null);

        $response = $this->controller->endCall(999, new Request());
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    // ==================== getActiveCalls ====================

    public function testGetActiveCalls_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $calls = [
            $this->createCallRecord(1, 1, 2, 'video', 'initiated'),
            $this->createCallRecord(2, 1, 3, 'audio', 'initiated'),
        ];
        $this->serviceMock->method('getActiveCalls')->with($user)->willReturn($calls);
        $this->setAuthenticatedUser($user);

        $response = $this->controller->getActiveCalls(new Request());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('active_calls', $data);
        $this->assertIsArray($data['active_calls']);
        $this->assertCount(2, $data['active_calls']);
    }

    // ==================== joinCall ====================

    public function testJoinCall_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $call = $this->createCallRecord(100, 1, 2, 'video', 'initiated');

        $this->setAuthenticatedUser($user);
        $this->serviceMock->method('getCallById')->with(100)->willReturn($call);
        $this->serviceMock->expects($this->once())->method('joinCall')->with($call, $user);

        $response = $this->controller->joinCall(100);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testJoinCall_NotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);
        $this->serviceMock->method('getCallById')->with(999)->willReturn(null);

        $response = $this->controller->joinCall(999);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    // ==================== acceptCall ====================

    public function testAcceptCall_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $call = $this->createCallRecord(100, 1, 2, 'video', 'ringing'); // adjust status? 'initiated' is fine, but accept might set to 'connected'? We'll assume any non-null works.
        // We'll just check it returns success. The service will modify the call; we can return the call.
        $this->setAuthenticatedUser($user);
        $this->serviceMock->method('getCallById')->with(100)->willReturn($call);
        $this->serviceMock->expects($this->once())->method('acceptCall')->with($call, $user);

        $response = $this->controller->acceptCall(100);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    // ==================== rejectCall ====================

    public function testRejectCall_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $call = $this->createCallRecord(100, 1, 2, 'video', 'ringing');
        $this->setAuthenticatedUser($user);
        $this->serviceMock->method('getCallById')->with(100)->willReturn($call);
        $this->serviceMock->expects($this->once())->method('rejectCall')->with($call, $user);

        $response = $this->controller->rejectCall(100);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }
}
