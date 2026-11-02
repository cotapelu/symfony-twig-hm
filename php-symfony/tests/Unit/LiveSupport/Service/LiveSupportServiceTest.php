<?php

declare(strict_types=1);

namespace App\Tests\Unit\LiveSupport\Service;

use App\LiveSupport\Service\LiveSupportService;
use App\LiveSupport\Entity\Conversation;
use App\LiveSupport\Entity\Agent;
use App\LiveSupport\Entity\Message;
use App\LiveSupport\Repository\ConversationRepository;
use App\LiveSupport\Repository\MessageRepository;
use App\LiveSupport\Repository\AgentRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use DateTimeImmutable;
use DateTime;
use PHPUnit\Framework\TestCase;

class LiveSupportServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private ConversationRepository $conversationRepo;
    private MessageRepository $messageRepo;
    private AgentRepository $agentRepo;
    private LiveSupportService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->conversationRepo = $this->createMock(ConversationRepository::class);
        $this->messageRepo = $this->createMock(MessageRepository::class);
        $this->agentRepo = $this->createMock(AgentRepository::class);
        $this->service = new LiveSupportService(
            $this->em,
            $this->conversationRepo,
            $this->messageRepo,
            $this->agentRepo
        );
    }

    // Helper to create conversation
    private function createConversation(string $topic, string $creatorId, array $participants = []): Conversation
    {
        if (!in_array($creatorId, $participants, true)) {
            $participants[] = $creatorId;
        }
        return new Conversation($topic, $creatorId, $participants);
    }

    // Helper to create agent
    private function createAgent(string $agentUserId, string $name, ?array $skills = null, string $status = 'offline', int $currentLoad = 0, ?DateTimeImmutable $lastHeartbeat = null): Agent
    {
        $agent = new Agent($agentUserId, $name, $skills);
        $agent->setStatus($status);
        $agent->setCurrentLoad($currentLoad);
        if ($lastHeartbeat) {
            $agent->setLastHeartbeat($lastHeartbeat);
        }
        return $agent;
    }

    // Helper to create message
    private function createMessage(Conversation $conversation, string $senderId, string $content): Message
    {
        return new Message($conversation, $senderId, $content);
    }

    // ================== createConversation ==================
    public function testCreateConversation_Success(): void
    {
        $topic = 'Technical Issue';
        $creatorId = 'user1';
        $participants = ['user2'];

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Conversation::class));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createConversation($creatorId, $topic, $participants);

        $this->assertInstanceOf(Conversation::class, $result);
        $this->assertSame($topic, $result->getTopic());
        $this->assertSame($creatorId, $result->getCreatorId());
        $this->assertContains($creatorId, $result->getParticipants());
        $this->assertContains('user2', $result->getParticipants());
        $this->assertSame('active', $result->getStatus());
    }

    public function testCreateConversation_EmptyTopic_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Topic cannot be empty');
        $this->service->createConversation('user1', '   ', []);
    }

    // ================== getConversation ==================
    public function testGetConversation_Found(): void
    {
        $conversation = $this->createConversation('Help', 'user1', []);
        $this->conversationRepo->expects($this->once())->method('find')->with(123)->willReturn($conversation);

        $result = $this->service->getConversation(123);

        $this->assertSame($conversation, $result);
    }

    public function testGetConversation_NotFound_ReturnsNull(): void
    {
        $this->conversationRepo->expects($this->once())->method('find')->with(456)->willReturn(null);

        $result = $this->service->getConversation(456);

        $this->assertNull($result);
    }

    // ================== getActiveConversations ==================
    public function testGetActiveConversations_WithoutAgent_ReturnsAllActive(): void
    {
        $conv1 = $this->createConversation('A', 'user1', []);
        $conv2 = $this->createConversation('B', 'user2', []);
        $this->conversationRepo->expects($this->once())->method('findActive')->willReturn([$conv1, $conv2]);

        $result = $this->service->getActiveConversations();

        $this->assertCount(2, $result);
        $this->assertSame([$conv1, $conv2], $result);
    }

    public function testGetActiveConversations_WithAgent_ReturnsAgentActiveOnly(): void
    {
        $agentId = 7;
        $conv1 = $this->createConversation('A', 'user1', []);
        $conv1->setAssignedAgentId($agentId);
        $conv2 = $this->createConversation('B', 'user2', []);
        $conv2->setAssignedAgentId(999); // other agent

        $this->conversationRepo->expects($this->once())->method('findBy')
            ->with(['assignedAgentId' => $agentId, 'status' => 'active'])
            ->willReturn([$conv1]);

        // Cast to string as required by method signature
        $result = $this->service->getActiveConversations((string) $agentId);

        $this->assertCount(1, $result);
        $this->assertSame($conv1, $result[0]);
    }

    // ================== getConversationsForParticipant ==================
    public function testGetConversationsForParticipant(): void
    {
        $userId = 'userX';
        $conv = $this->createConversation('Chat', 'userX', ['userX', 'other']);
        $this->conversationRepo->expects($this->once())->method('findByParticipant')->with($userId)->willReturn([$conv]);

        $result = $this->service->getConversationsForParticipant($userId);

        $this->assertCount(1, $result);
        $this->assertSame($conv, $result[0]);
    }

    // ================== endConversation ==================
    public function testEndConversation_Success_ByCreator(): void
    {
        $conversation = $this->createConversation('Topic', 'creator', []);
        $conversation->setStatus('active'); // ensure active
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        // Expect save via conversationRepo (which uses EM)
        $this->conversationRepo->expects($this->once())->method('save')->with($conversation);

        $result = $this->service->endConversation(10, 'creator');

        $this->assertSame('ended', $result->getStatus());
    }

    public function testEndConversation_Success_ByAssignedAgent(): void
    {
        $conversation = $this->createConversation('Topic', 'creator', []);
        $conversation->setAssignedAgentId(5);
        $conversation->setStatus('active');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        $this->conversationRepo->expects($this->once())->method('save')->with($conversation);

        $result = $this->service->endConversation(10, '5');

        $this->assertSame('ended', $result->getStatus());
    }

    public function testEndConversation_NotFound_Throws(): void
    {
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conversation not found');
        $this->service->endConversation(10, 'user1');
    }

    public function testEndConversation_Unauthorized_Throws(): void
    {
        $conversation = $this->createConversation('Topic', 'creator', []);
        $conversation->setStatus('active');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized to end conversation');
        $this->service->endConversation(10, 'otherUser');
    }

    // ================== assignAgent ==================
    public function testAssignAgent_Success(): void
    {
        $conversation = $this->createConversation('Topic', 'creator', []);
        $agent = $this->createAgent('agent1', 'Agent One');
        $conversation->setAssignedAgentId(null); // initially

        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        // Check authorization: creator only
        // We'll pass actingUserId = 'creator' - okay
        $this->agentRepo->expects($this->once())->method('find')->with(5)->willReturn($agent);
        $this->conversationRepo->expects($this->once())->method('save')->with($conversation);

        $result = $this->service->assignAgent(10, 'creator', 5);

        $this->assertSame($conversation, $result);
        $this->assertSame(5, $conversation->getAssignedAgentId());
        $this->assertContains('agent1', $conversation->getParticipants());
    }

    public function testAssignAgent_ConversationNotFound_Throws(): void
    {
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conversation not found');
        $this->service->assignAgent(10, 'creator', 5);
    }

    public function testAssignAgent_Unauthorized_Throws(): void
    {
        $conversation = $this->createConversation('Topic', 'creator', []);
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized to assign agent');
        $this->service->assignAgent(10, 'otherUser', 5);
    }

    public function testAssignAgent_AgentNotFound_Throws(): void
    {
        $conversation = $this->createConversation('Topic', 'creator', []);
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        $this->agentRepo->expects($this->once())->method('find')->with(5)->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent not found');
        $this->service->assignAgent(10, 'creator', 5);
    }

    // ================== joinConversation ==================
    public function testJoinConversation_Success_AgentOnline(): void
    {
        $conversation = $this->createConversation('Topic', 'creator', ['creator']);
        $conversation->setStatus('active');
        $agent = $this->createAgent('agent1', 'Agent', null, 'online');
        // Assume Agent has getAgentUserId method returning string
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with('agent1')->willReturn($agent);
        $this->conversationRepo->expects($this->once())->method('save')->with($conversation);

        $result = $this->service->joinConversation(10, 'agent1');

        $this->assertSame($conversation, $result);
        $this->assertContains('agent1', $conversation->getParticipants());
    }

    public function testJoinConversation_Success_AlreadyParticipant(): void
    {
        $conversation = $this->createConversation('Topic', 'creator', ['creator', 'user2']);
        $conversation->setStatus('active');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        // No agent lookup because user is already participant
        $result = $this->service->joinConversation(10, 'user2');
        $this->assertSame($conversation, $result);
    }

    public function testJoinConversation_NotFound_Throws(): void
    {
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conversation not found');
        $this->service->joinConversation(10, 'user1');
    }

    public function testJoinConversation_NotActive_Throws(): void
    {
        $conversation = $this->createConversation('Topic', 'user1', ['user1']);
        $conversation->setStatus('ended');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot join ended conversation');
        $this->service->joinConversation(10, 'user2');
    }

    public function testJoinConversation_Unauthorized_Throws(): void
    {
        $conversation = $this->createConversation('Topic', 'creator', ['creator']);
        $conversation->setStatus('active');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with('userX')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized to join conversation');
        $this->service->joinConversation(10, 'userX');
    }

    public function testJoinConversation_AgentOffline_Throws(): void
    {
        $conversation = $this->createConversation('Topic', 'creator', ['creator']);
        $conversation->setStatus('active');
        $agent = $this->createAgent('agent1', 'Agent', null, 'offline');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with('agent1')->willReturn($agent);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized to join conversation');
        $this->service->joinConversation(10, 'agent1');
    }

    // ================== leaveConversation ==================
    public function testLeaveConversation_Success(): void
    {
        $conversation = $this->createConversation('Topic', 'user1', ['user1', 'user2']);
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->leaveConversation(10, 'user2');

        $this->assertSame($conversation, $result);
        $this->assertNotContains('user2', $result->getParticipants());
    }

    public function testLeaveConversation_NotFound_Throws(): void
    {
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conversation not found');
        $this->service->leaveConversation(10, 'user1');
    }

    public function testLeaveConversation_NotParticipant_Throws(): void
    {
        $conversation = $this->createConversation('Topic', 'user1', ['user1']);
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User is not a participant');
        $this->service->leaveConversation(10, 'other');
    }

    // ================== sendMessage ==================
    public function testSendMessage_Success(): void
    {
        $conversation = $this->createConversation('Topic', 'user1', ['user1', 'user2']);
        $conversation->setStatus('active');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Message::class));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->sendMessage(10, 'user2', 'Hello');

        $this->assertInstanceOf(Message::class, $result);
        $this->assertSame('Hello', $result->getContent());
        $this->assertSame('user2', $result->getSenderId());
        $this->assertSame($conversation, $result->getConversation());
        $this->assertContains($result, $conversation->getMessages());
    }

    public function testSendMessage_ConversationNotFound_Throws(): void
    {
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conversation not found');
        $this->service->sendMessage(10, 'user1', 'Hi');
    }

    public function testSendMessage_NotActive_Throws(): void
    {
        $conversation = $this->createConversation('Topic', 'user1', ['user1']);
        $conversation->setStatus('ended');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot send message to ended conversation');
        $this->service->sendMessage(10, 'user1', 'Hi');
    }

    public function testSendMessage_SenderNotParticipant_Throws(): void
    {
        $conversation = $this->createConversation('Topic', 'user1', ['user1']);
        $conversation->setStatus('active');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sender is not a participant in this conversation');
        $this->service->sendMessage(10, 'other', 'Hi');
    }

    public function testSendMessage_EmptyContent_Throws(): void
    {
        $conversation = $this->createConversation('Topic', 'user1', ['user1']);
        $conversation->setStatus('active');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message content cannot be empty');
        $this->service->sendMessage(10, 'user1', '   ');
    }

    // ================== getMessages ==================
    public function testGetMessages_Success(): void
    {
        $conversation = $this->createConversation('Topic', 'user1', ['user1']);
        $message1 = $this->createMessage($conversation, 'user1', 'First');
        $message2 = $this->createMessage($conversation, 'user2', 'Second');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        $this->messageRepo->expects($this->once())->method('findByConversation')
            ->with(10, 100, 0, null)
            ->willReturn([$message1, $message2]);

        $result = $this->service->getMessages(10);

        $this->assertCount(2, $result);
        $this->assertSame([$message1, $message2], $result);
    }

    public function testGetMessages_WithLimitOffsetSince(): void
    {
        $conversation = $this->createConversation('Topic', 'user1', ['user1']);
        $since = new DateTime('2025-01-01');
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn($conversation);
        $this->messageRepo->expects($this->once())->method('findByConversation')
            ->with(10, 50, 10, $since)
            ->willReturn([]);

        $result = $this->service->getMessages(10, 50, 10, $since);

        $this->assertEmpty($result);
    }

    public function testGetMessages_ConversationNotFound_Throws(): void
    {
        $this->conversationRepo->expects($this->once())->method('find')->with(10)->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conversation not found');
        $this->service->getMessages(10);
    }

    // ================== getMessageCount ==================
    public function testGetMessageCount(): void
    {
        $this->messageRepo->expects($this->once())->method('countByConversation')->with(10)->willReturn(5);
        $result = $this->service->getMessageCount(10);
        $this->assertSame(5, $result);
    }

    // ================== registerAgent ==================
    public function testRegisterAgent_NewAgent(): void
    {
        $agentUserId = 'user123';
        $name = 'Support Agent';
        $skills = ['tech', 'billing'];
        $agent = $this->createAgent($agentUserId, $name, $skills);

        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with($agentUserId)->willReturn(null);
        $this->agentRepo->expects($this->once())->method('save')->with($this->isInstanceOf(Agent::class));

        $result = $this->service->registerAgent($agentUserId, $name, $skills);

        $this->assertInstanceOf(Agent::class, $result);
        $this->assertSame($agentUserId, $result->getAgentUserId());
        $this->assertSame($name, $result->getName());
        $this->assertSame($skills, $result->getSkills());
    }

    public function testRegisterAgent_Existing_ReturnsExisting(): void
    {
        $existing = $this->createAgent('user123', 'Old Name', ['old']);
        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with('user123')->willReturn($existing);
        $this->agentRepo->expects($this->never())->method('save');

        $result = $this->service->registerAgent('user123', 'New Name', ['new']);

        $this->assertSame($existing, $result);
    }

    // ================== getAgentByUserId ==================
    public function testGetAgentByUserId_Found(): void
    {
        $agent = $this->createAgent('uid', 'Name');
        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with('uid')->willReturn($agent);

        $result = $this->service->getAgentByUserId('uid');

        $this->assertSame($agent, $result);
    }

    public function testGetAgentByUserId_NotFound(): void
    {
        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with('unknown')->willReturn(null);
        $result = $this->service->getAgentByUserId('unknown');
        $this->assertNull($result);
    }

    // ================== getAgentStatus ==================
    public function testGetAgentStatus_ReturnsArray(): void
    {
        $agent = $this->createAgent('uid', 'Name', ['skill1'], 'online', 3, new DateTimeImmutable());
        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with('uid')->willReturn($agent);

        $result = $this->service->getAgentStatus('uid');

        $this->assertIsArray($result);
        $this->assertSame('uid', $result['agent_user_id']);
        $this->assertSame('Name', $result['name']);
        $this->assertSame(['skill1'], $result['skills']);
        $this->assertSame('online', $result['status']);
        $this->assertSame(3, $result['current_load']);
        $this->assertSame($agent->getLastHeartbeat()->format(\DateTimeImmutable::ATOM), $result['last_heartbeat']);
    }

    public function testGetAgentStatus_AgentNotFound_ReturnsNull(): void
    {
        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with('uid')->willReturn(null);
        $result = $this->service->getAgentStatus('uid');
        $this->assertNull($result);
    }

    // ================== setAgentStatus ==================
    public function testSetAgentStatus_Success(): void
    {
        $agent = $this->createAgent('uid', 'Name', null, 'offline', 0);
        // Initially offline
        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with('uid')->willReturn($agent);
        $this->agentRepo->expects($this->once())->method('save')->with($agent);
        // Removed flush expectation as repository save handles persistence.

        $result = $this->service->setAgentStatus('uid', 'online', 5);

        $this->assertSame($agent, $result);
        $this->assertSame('online', $agent->getStatus());
        $this->assertSame(5, $agent->getCurrentLoad());
        $this->assertInstanceOf(\DateTimeImmutable::class, $agent->getLastHeartbeat());
    }

    public function testSetAgentStatus_AgentNotFound_Throws(): void
    {
        $this->agentRepo->expects($this->once())->method('findByAgentUserId')->with('uid')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent not found');
        $this->service->setAgentStatus('uid', 'online');
    }

    // ================== listAgents ==================
    public function testListAgents_ReturnsAll(): void
    {
        $agent1 = $this->createAgent('a1', 'Agent One');
        $agent2 = $this->createAgent('a2', 'Agent Two');
        $this->agentRepo->expects($this->once())->method('findAll')->willReturn([$agent1, $agent2]);

        $result = $this->service->listAgents();

        $this->assertCount(2, $result);
        $this->assertSame([$agent1, $agent2], $result);
    }
}
