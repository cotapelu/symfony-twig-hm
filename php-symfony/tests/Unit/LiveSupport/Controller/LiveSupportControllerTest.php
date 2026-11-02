<?php

declare(strict_types=1);

namespace Tests\Unit\LiveSupport\Controller;

use App\LiveSupport\Controller\LiveSupportController;
use App\LiveSupport\Service\LiveSupportService;
use App\LiveSupport\Entity\Conversation;
use App\LiveSupport\Entity\Message;
use App\LiveSupport\Entity\Agent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\User\UserInterface;
use PHPUnit\Framework\TestCase;

class LiveSupportControllerTest extends TestCase
{
    private LiveSupportService $serviceMock;
    private LiveSupportController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(LiveSupportService::class);
        $this->controller = new LiveSupportController($this->serviceMock);
    }

    private function setAuthenticatedUser(string $userId = 'user-123'): void
    {
        $user = new class($userId) implements UserInterface {
            private string $id;
            public function __construct(string $id) { $this->id = $id; }
            public function getId(): string { return $this->id; }
            public function getUserIdentifier(): string { return $this->id; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function getPassword(): ?string { return null; }
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
        };
        $refProp = new \ReflectionProperty($this->controller, 'testUser');
        $refProp->setAccessible(true);
        $refProp->setValue($this->controller, $user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createConversation(int $id, string $creatorId, string $topic, string $status = 'active'): Conversation
    {
        $conv = $this->createMock(Conversation::class);
        $conv->method('getId')->willReturn($id);
        $conv->method('getCreatorId')->willReturn($creatorId);
        $conv->method('getTopic')->willReturn($topic);
        $conv->method('getStatus')->willReturn($status);
        $conv->method('getAssignedAgentId')->willReturn(null);
        $now = new \DateTimeImmutable();
        $conv->method('getCreatedAt')->willReturn($now);
        $conv->method('getUpdatedAt')->willReturn($now);
        $conv->method('getParticipants')->willReturn([]);
        $conv->method('hasParticipant')->willReturn(true);
        return $conv;
    }

    private function createAgent(int $userId, string $status = 'online'): Agent
    {
        $agent = $this->createMock(Agent::class);
        $agent->method('getId')->willReturn($userId);
        $agent->method('getAgentUserId')->willReturn((string)$userId);
        $agent->method('getName')->willReturn('Agent '.$userId);
        $agent->method('getSkills')->willReturn([]);
        $agent->method('getCurrentLoad')->willReturn(0);
        $now = new \DateTimeImmutable();
        $agent->method('getCreatedAt')->willReturn($now);
        $agent->method('getUpdatedAt')->willReturn($now);
        $agent->method('getLastHeartbeat')->willReturn(null);
        $agent->method('getStatus')->willReturn($status);
        return $agent;
    }

    private function createMessage(int $id, int $conversationId, string $senderId, string $content): Message
    {
        $msg = $this->createMock(Message::class);
        $msg->method('getId')->willReturn($id);
        $convMock = $this->createMock(Conversation::class);
        $convMock->method('getId')->willReturn($conversationId);
        $msg->method('getConversation')->willReturn($convMock);
        $msg->method('getSenderId')->willReturn($senderId);
        $msg->method('getContent')->willReturn($content);
        $msg->method('isRead')->willReturn(false);
        $msg->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        return $msg;
    }

    // ==================== createConversation ====================

    public function testCreateConversation_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->createConversation($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateConversation_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->createConversation($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateConversation_EmptyPayload_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = Request::create('', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $response = $this->controller->createConversation($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateConversation_Success_Returns201(): void
    {
        $this->setAuthenticatedUser('u1');
        $conv = $this->createConversation(1, 'u1', 'Support');
        $this->serviceMock->expects($this->once())
            ->method('createConversation')
            ->with('u1', 'Support', [])
            ->willReturn($conv);
        $payload = json_encode(['topic' => 'Support']);
        $request = Request::create('', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->createConversation($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    // ==================== getConversation ====================

    public function testGetConversation_Unauthenticated_Returns401(): void
    {
        $conv = $this->createConversation(1, 'u1', 'Topic');
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($conv);
        $request = new Request();
        $response = $this->controller->getConversation($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetConversation_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('u1');
        $this->serviceMock->expects($this->once())->method('getConversation')->with(1)->willReturn(null);
        $request = new Request();
        $response = $this->controller->getConversation($request, 1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetConversation_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $conv = $this->createConversation(1, 'u1', 'Topic');
        $this->serviceMock->expects($this->once())->method('getConversation')->with(1)->willReturn($conv);
        $request = new Request();
        $response = $this->controller->getConversation($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getActiveConversations ====================

    public function testGetActiveConversations_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->getActiveConversations($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetActiveConversations_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $convs = [$this->createConversation(1, 'u1', 'T')];
        $this->serviceMock->expects($this->once())->method('getActiveConversations')->with(null)->willReturn($convs);
        $request = new Request();
        $response = $this->controller->getActiveConversations($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== joinConversation ====================

    public function testJoinConversation_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->joinConversation($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testJoinConversation_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = new Request();
        $response = $this->controller->joinConversation($request, 0);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testJoinConversation_NotFound_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $this->serviceMock->expects($this->once())
            ->method('joinConversation')
            ->with(1, 'u1')
            ->willThrowException(new \InvalidArgumentException('Conversation not found'));
        $request = new Request();
        $response = $this->controller->joinConversation($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testJoinConversation_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $conv = $this->createConversation(1, 'u2', 'T');
        $this->serviceMock->expects($this->once())
            ->method('joinConversation')
            ->with(1, 'u1')
            ->willReturn($conv);
        $request = new Request();
        $response = $this->controller->joinConversation($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== leaveConversation ====================

    public function testLeaveConversation_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->leaveConversation($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testLeaveConversation_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = new Request();
        $response = $this->controller->leaveConversation($request, 0);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testLeaveConversation_NotFound_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $this->serviceMock->expects($this->once())
            ->method('leaveConversation')
            ->with(1, 'u1')
            ->willThrowException(new \InvalidArgumentException('Conversation not found'));
        $request = new Request();
        $response = $this->controller->leaveConversation($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testLeaveConversation_NotAParticipant_Returns403(): void
    {
        $this->setAuthenticatedUser('u1');
        // Service will throw when user not participant
        $this->serviceMock->expects($this->once())
            ->method('leaveConversation')
            ->with(1, 'u1')
            ->willThrowException(new \InvalidArgumentException('User is not a participant'));
        $request = new Request();
        $response = $this->controller->leaveConversation($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testLeaveConversation_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $conv = $this->createConversation(1, 'u1', 'T');
        $this->serviceMock->expects($this->once())
            ->method('leaveConversation')
            ->with(1, 'u1')
            ->willReturn($conv);
        $request = new Request();
        $response = $this->controller->leaveConversation($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== endConversation ====================

    public function testEndConversation_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->endConversation($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testEndConversation_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = new Request();
        $response = $this->controller->endConversation($request, 0);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testEndConversation_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('u1');
        $this->serviceMock->expects($this->once())
            ->method('endConversation')
            ->with(1, 'u1')
            ->willThrowException(new \InvalidArgumentException('Conversation not found'));
        $request = new Request();
        $response = $this->controller->endConversation($request, 1);
        $this->assertSame(400, $response->getStatusCode()); // InvalidArgumentException maps to 400
    }

    public function testEndConversation_Unauthorized_Returns403(): void
    {
        $this->setAuthenticatedUser('u1');
        $this->serviceMock->expects($this->once())->method('endConversation')->with(1, 'u1')->willThrowException(new \InvalidArgumentException('Unauthorized to end conversation'));
        $request = new Request();
        $response = $this->controller->endConversation($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testEndConversation_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $conv = $this->createConversation(1, 'u1', 'T');
        $this->serviceMock->expects($this->once())->method('endConversation')->with(1, 'u1')->willReturn($conv);
        $request = new Request();
        $response = $this->controller->endConversation($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== assignAgent ====================

    public function testAssignAgent_Unauthenticated_Returns401(): void
    {
        $payload = json_encode(['agentId' => 123]);
        $request = Request::create('', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->assignAgent($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignAgent_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->assignAgent($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignAgent_MissingAgentId_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = Request::create('', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $response = $this->controller->assignAgent($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignAgent_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('u1');
        $payload = json_encode(['agent_id' => 123]);
        $request = Request::create('', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->serviceMock->expects($this->once())->method('assignAgent')->with(1, 'u1', 123)->willThrowException(new \InvalidArgumentException('Conversation not found'));
        $response = $this->controller->assignAgent($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignAgent_Unauthorized_Returns403(): void
    {
        $this->setAuthenticatedUser('u1');
        $payload = json_encode(['agent_id' => 123]);
        $request = Request::create('', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->serviceMock->expects($this->once())->method('assignAgent')->with(1, 'u1', 123)->willThrowException(new \InvalidArgumentException('Unauthorized to assign agent'));
        $response = $this->controller->assignAgent($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignAgent_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $conv = $this->createConversation(1, 'u1', 'Topic');
        $this->serviceMock->expects($this->once())
            ->method('assignAgent')
            ->with(1, 'u1', 123)
            ->willReturn($conv);
        $payload = json_encode(['agent_id' => 123]);
        $request = Request::create('', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->assignAgent($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== sendMessage ====================

    public function testSendMessage_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->sendMessage($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSendMessage_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->sendMessage($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSendMessage_MissingFields_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = Request::create('', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $response = $this->controller->sendMessage($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSendMessage_Success_Returns201(): void
    {
        $this->setAuthenticatedUser('u1');
        $msg = $this->createMessage(1, 1, 'u1', 'Hello');
        $this->serviceMock->expects($this->once())
            ->method('sendMessage')
            ->with(1, 'u1', 'Hello')
            ->willReturn($msg);
        $payload = json_encode(['conversation_id' => 1, 'content' => 'Hello']);
        $request = Request::create('', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->sendMessage($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    // ==================== getMessages ====================

    public function testGetMessages_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->getMessages($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetMessages_AccessDenied_Returns403(): void
    {
        $this->setAuthenticatedUser('u1');
        $conv = $this->createMock(\App\LiveSupport\Entity\Conversation::class);
        $conv->method('hasParticipant')->with('u1')->willReturn(false);
        $conv->method('getAssignedAgentId')->willReturn(null);
        $conv->method('getCreatorId')->willReturn('other');
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($conv);
        $request = new Request();
        $response = $this->controller->getMessages($request, 1);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetMessages_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $conv = $this->createConversation(1, 'u1', 'Topic');
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($conv);
        $messages = [$this->createMessage(1, 1, 'u1', 'Hi')];
        $this->serviceMock->expects($this->once())
            ->method('getMessages')
            ->with(1, 100, 0, null)
            ->willReturn($messages);
        $request = new Request();
        $response = $this->controller->getMessages($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== registerAgent ====================

    public function testRegisterAgent_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->registerAgent($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRegisterAgent_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->registerAgent($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRegisterAgent_MissingUserId_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = Request::create('', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $response = $this->controller->registerAgent($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRegisterAgent_Success_Returns201(): void
    {
        $this->setAuthenticatedUser('admin');
        $agent = $this->createAgent(123);
        $this->serviceMock->expects($this->once())
            ->method('registerAgent')
            ->with('123', 'Online Support', ['Online Support'])
            ->willReturn($agent);
        $payload = json_encode([
            'agent_user_id' => '123',
            'name' => 'Online Support',
            'skills' => ['Online Support']
        ]);
        $request = Request::create('', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->registerAgent($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    // ==================== getAgent ====================

    public function testGetAgent_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->getAgent($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetAgent_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('u1');
        $this->serviceMock->expects($this->once())->method('getAgentByUserId')->with('123')->willReturn(null);
        $request = new Request();
        $response = $this->controller->getAgent($request, '123');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetAgent_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $agent = $this->createAgent(123);
        $this->serviceMock->expects($this->once())->method('getAgentByUserId')->with('123')->willReturn($agent);
        $request = new Request();
        $response = $this->controller->getAgent($request, '123');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getAgentStatus ====================

    public function testGetAgentStatus_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->getAgentStatus($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetAgentStatus_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('u1');
        $this->serviceMock->expects($this->once())->method('getAgentStatus')->with('123')->willReturn(null);
        $request = new Request();
        $response = $this->controller->getAgentStatus($request, '123');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetAgentStatus_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $agent = $this->createAgent(123, 'busy');
        $this->serviceMock->expects($this->once())->method('getAgentStatus')->with('123')->willReturn($agent);
        $request = new Request();
        $response = $this->controller->getAgentStatus($request, '123');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== setAgentStatus ====================

    public function testSetAgentStatus_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->setAgentStatus($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSetAgentStatus_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->setAgentStatus($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSetAgentStatus_MissingStatus_Returns400(): void
    {
        $this->setAuthenticatedUser('u1');
        $request = Request::create('', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $response = $this->controller->setAgentStatus($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSetAgentStatus_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('u1');
        $payload = json_encode(['status' => 'online']);
        $request = Request::create('', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->serviceMock->expects($this->once())
            ->method('setAgentStatus')
            ->with('123', 'online')
            ->willThrowException(new \InvalidArgumentException('Agent not found'));
        $response = $this->controller->setAgentStatus($request, '123');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSetAgentStatus_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $agent = $this->createAgent(123, 'online');
        $this->serviceMock->expects($this->once())
            ->method('setAgentStatus')
            ->with('123', 'online')
            ->willReturn($agent);
        $payload = json_encode(['status' => 'online']);
        $request = Request::create('', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->setAgentStatus($request, '123');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== listAgents ====================

    public function testListAgents_Unauthenticated_Returns401(): void
    {
        $request = new Request();
        $response = $this->controller->listAgents($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testListAgents_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('u1');
        $agents = [$this->createAgent(123), $this->createAgent(456)];
        $this->serviceMock->expects($this->once())->method('listAgents')->willReturn($agents);
        $request = new Request();
        $response = $this->controller->listAgents($request);
        $this->assertSame(200, $response->getStatusCode());
    }
}
