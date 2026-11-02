<?php

declare(strict_types=1);

namespace Tests\Unit\Message\Controller;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use App\Message\Controller\MessageController;
use App\Message\Entity\Message;
use App\Message\Service\AIService;
use App\Message\Service\TranslationService;
use App\Message\Service\ConversationInsightsService;
use App\Message\Service\MessageServiceInterface;
use App\Conversation\Service\ConversationService;
use App\Shared\Infrastructure\RateLimitService;
use App\Message\Repository\MessageRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[AllowMockObjectsWithoutExpectations]
class MessageControllerTest extends TestCase
{
    private MessageServiceInterface $messageServiceMock;
    private ConversationService $conversationServiceMock;
    private AIService $aiServiceMock;
    private TranslationService $translationServiceMock;
    private ConversationInsightsService $insightsServiceMock;
    private RateLimitService $rateLimitServiceMock;
    private MessageRepositoryInterface $messageRepositoryMock;
    private MessageController $controller;

    protected function setUp(): void
    {
        $this->messageServiceMock = $this->createMock(MessageServiceInterface::class);
        $this->conversationServiceMock = $this->createMock(ConversationService::class);
        $this->aiServiceMock = $this->createMock(AIService::class);
        $this->translationServiceMock = $this->createMock(TranslationService::class);
        $this->insightsServiceMock = $this->createMock(ConversationInsightsService::class);
        $this->rateLimitServiceMock = $this->createMock(RateLimitService::class);
        $this->messageRepositoryMock = $this->createMock(MessageRepositoryInterface::class);

        $this->controller = new MessageController(
            $this->messageServiceMock,
            $this->conversationServiceMock,
            $this->aiServiceMock,
            $this->translationServiceMock,
            $this->insightsServiceMock,
            $this->rateLimitServiceMock,
            $this->messageRepositoryMock,
            null
        );
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setTokenStorageUser(string $userId = '123'): void
    {
        $user = new class($userId) implements \Symfony\Component\Security\Core\User\UserInterface {
            private string $id;
            public function __construct(string $id) { $this->id = $id; }
            public function getId(): string { return $this->id; }
            public function getUserIdentifier(): string { return $this->id; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function getPassword(): ?string { return null; }
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
        };
        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);
        $refProp = new \ReflectionProperty($this->controller, 'tokenStorage');
        $refProp->setAccessible(true);
        $refProp->setValue($this->controller, $tokenStorage);
    }

    private function createMessage(
        string $id,
        string $conversationId,
        string $senderId,
        string $content,
        string $type = 'text',
        ?string $threadId = null,
        ?\DateTime $createdAt = null
    ): Message {
        $msg = new Message($id, $conversationId, $senderId, $content, $type, $threadId);
        if ($createdAt) {
            $refProp = new \ReflectionProperty($msg, 'createdAt');
            $refProp->setAccessible(true);
            $refProp->setValue($msg, $createdAt);
        }
        return $msg;
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $method = new \ReflectionMethod($obj, $method);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    // ==================== GET /messages ====================

    public function testGetMessages_RateLimited_Returns429(): void
    {
        $this->setTokenStorageUser('1');
        $request = Request::create('/api/messages', 'GET');
        $this->rateLimitServiceMock->expects($this->once())
            ->method('checkRateLimit')
            ->with($this->anything(), 'messages_list')
            ->willReturn(false);
        $this->rateLimitServiceMock->expects($this->once())
            ->method('getResetTime')
            ->with($this->anything(), 'messages_list')
            ->willReturn(['minute'=>60]);
        $response = $this->controller->getMessages($request);
        $this->assertSame(429, $response->getStatusCode());
    }

    public function testGetMessages_Unauthenticated_Returns401(): void
    {
        $this->rateLimitServiceMock->expects($this->once())
            ->method('checkRateLimit')
            ->with($this->anything(), 'messages_list')
            ->willReturn(true);
        $request = Request::create('/api/messages', 'GET');
        $response = $this->controller->getMessages($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetMessages_Success_Returns200(): void
    {
        $this->setTokenStorageUser('1');
        $msg1 = $this->createMessage('m1', 'c1', 'u1', 'Hello');
        $msg2 = $this->createMessage('m2', 'c1', 'u2', 'World');
        $this->messageRepositoryMock->expects($this->once())
            ->method('findMessagesPaginated')
            ->with(1, 20, null)
            ->willReturn(['data'=>[$msg1, $msg2], 'total'=>2]);
        $this->rateLimitServiceMock->expects($this->once())
            ->method('checkRateLimit')
            ->with($this->anything(), 'messages_list')
            ->willReturn(true);
        $request = Request::create('/api/messages', 'GET');
        $response = $this->controller->getMessages($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(2, $content['messages']);
        $this->assertSame('m1', $content['messages'][0]['id']);
    }

    public function testGetMessages_WithConversationFilter_Returns200(): void
    {
        $this->setTokenStorageUser('1');
        $msg = $this->createMessage('m1', 'c1', 'u1', 'Test');
        $this->messageRepositoryMock->expects($this->once())
            ->method('findMessagesPaginated')
            ->with(1, 20, 'c1')
            ->willReturn(['data'=>[$msg], 'total'=>1]);
        $this->rateLimitServiceMock->expects($this->once())
            ->method('checkRateLimit')
            ->with($this->anything(), 'messages_list')
            ->willReturn(true);
        $request = Request::create('/api/messages', 'GET', [], [], [], ['QUERY_STRING' => 'conversation_id=c1']);
        $request->query->replace(['conversation_id' => 'c1']);
        $response = $this->controller->getMessages($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== POST /messages ====================

    public function testSendMessage_RateLimited_Returns429(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['conversationId'=>'c1','content'=>'Hi']);
        $request = Request::create('/api/messages', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->rateLimitServiceMock->expects($this->once())
            ->method('checkRateLimit')
            ->with($this->anything(), 'messages_send')
            ->willReturn(false);
        $this->rateLimitServiceMock->expects($this->once())
            ->method('getResetTime')
            ->with($this->anything(), 'messages_send')
            ->willReturn(['minute'=>60]);
        $response = $this->controller->sendMessage($request);
        $this->assertSame(429, $response->getStatusCode());
    }

    public function testSendMessage_Unauthenticated_Returns401(): void
    {
        $this->rateLimitServiceMock->expects($this->once())
            ->method('checkRateLimit')
            ->with($this->anything(), 'messages_send')
            ->willReturn(true);
        $payload = json_encode(['conversationId'=>'c1','content'=>'Hi']);
        $request = Request::create('/api/messages', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->sendMessage($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testSendMessage_InvalidJson_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $this->rateLimitServiceMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $request = Request::create('/api/messages', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->sendMessage($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSendMessage_ValidationError_MissingConversationId_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $this->rateLimitServiceMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $payload = json_encode(['content'=>'Hi']);
        $request = Request::create('/api/messages', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->sendMessage($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Validation failed', json_decode($response->getContent(), true)['error']);
    }

    public function testSendMessage_ValidationError_MissingContent_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $this->rateLimitServiceMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $payload = json_encode(['conversationId'=>'c1']);
        $request = Request::create('/api/messages', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->sendMessage($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSendMessage_ValidationError_InvalidConversationId_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $this->rateLimitServiceMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $payload = json_encode(['conversationId'=>0,'content'=>'Hi']);
        $request = Request::create('/api/messages', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->sendMessage($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSendMessage_ValidationError_EmptyContent_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $this->rateLimitServiceMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $payload = json_encode(['conversationId'=>'c1','content'=>'   ']);
        $request = Request::create('/api/messages', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->sendMessage($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSendMessage_Success_Returns200(): void
    {
        $this->setTokenStorageUser('u1');
        $this->rateLimitServiceMock->expects($this->once())->method('checkRateLimit')->willReturn(true);
        $payload = json_encode(['conversationId'=>'c1','content'=>'Hello']);
        $request = Request::create('/api/messages', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $sentMessage = $this->createMessage('m1', 'c1', 'u1', 'Hello');
        $this->messageServiceMock->expects($this->once())
            ->method('sendMessage')
            ->with(['conversationId'=>'c1','content'=>'Hello','files'=>null], 'u1')
            ->willReturn($sentMessage);
        $response = $this->controller->sendMessage($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('m1', $content['id']);
        $this->assertSame('Hello', $content['content']);
    }

    // ==================== GET /messages/{conversationId} ====================

    public function testGetConversationMessages_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/messages/c1', 'GET');
        $response = $this->controller->getConversationMessages($request, 'c1');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetConversationMessages_InvalidLimit_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $request = Request::create('/api/messages/c1', 'GET');
        $request->query->replace(['limit' => '0']);
        $response = $this->controller->getConversationMessages($request, 'c1');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetConversationMessages_Success_Returns200(): void
    {
        $this->setTokenStorageUser('u1');
        $msg = $this->createMessage('m1', 'c1', 'u2', 'Hi');
        $this->messageServiceMock->expects($this->once())
            ->method('getConversationMessages')
            ->with('c1', 'u1', 1, 50)
            ->willReturn([$msg]);
        $request = Request::create('/api/messages/c1', 'GET');
        $request->query->replace(['page' => '1', 'limit' => '50']);
        $response = $this->controller->getConversationMessages($request, 'c1');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(1, $content['messages']);
    }

    public function testGetConversationMessages_WithCursor_Success_Returns200(): void
    {
        $this->setTokenStorageUser('u1');
        $msg = $this->createMessage('m1', 'c1', 'u2', 'Hi');
        $result = ['data'=>[$msg], 'next_cursor'=>'cursor123', 'has_more'=>false];
        $this->messageServiceMock->expects($this->once())
            ->method('getConversationMessagesCursor')
            ->with('c1', 'u1', 'cursor123', 50)
            ->willReturn($result);
        $request = Request::create('/api/messages/c1', 'GET');
        $request->query->replace(['use_cursor' => 'true', 'cursor' => 'cursor123', 'limit' => '50']);
        $response = $this->controller->getConversationMessages($request, 'c1');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('next_cursor', $content['pagination']);
    }

    // ==================== POST /messages/{messageId}/translate ====================

    public function testTranslateMessage_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/messages/m1/translate', 'POST');
        $response = $this->controller->translateMessage($request, 'm1');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testTranslateMessage_InvalidJson_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $request = Request::create('/api/messages/m1/translate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->translateMessage($request, 'm1');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testTranslateMessage_MissingContent_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $payload = json_encode([]);
        $request = Request::create('/api/messages/m1/translate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->translateMessage($request, 'm1');
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Content is required', json_decode($response->getContent(), true)['error']);
    }

    public function testTranslateMessage_Success_Returns200(): void
    {
        $this->setTokenStorageUser('u1');
        $this->translationServiceMock->expects($this->once())
            ->method('detectLanguage')
            ->with('Hello')
            ->willReturn('en');
        $this->translationServiceMock->expects($this->once())
            ->method('translate')
            ->with('Hello', 'en', 'es')
            ->willReturn('Hola');
        $payload = json_encode(['content'=>'Hello','to_lang'=>'es']);
        $request = Request::create('/api/messages/m1/translate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->translateMessage($request, 'm1');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('Hola', $content['translated']);
        $this->assertSame('en', $content['detected_language']);
    }

    // ==================== POST /messages/smart-reply ====================

    public function testGenerateSmartReply_Unauthenticated_Returns401(): void
    {
        $payload = json_encode(['message'=>'Hi']);
        $request = Request::create('/api/messages/smart-reply', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->generateSmartReply($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGenerateSmartReply_InvalidJson_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $request = Request::create('/api/messages/smart-reply', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->generateSmartReply($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGenerateSmartReply_MissingMessage_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $payload = json_encode([]);
        $request = Request::create('/api/messages/smart-reply', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->generateSmartReply($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Message is required', json_decode($response->getContent(), true)['error']);
    }

    public function testGenerateSmartReply_Success_Returns200(): void
    {
        $this->setTokenStorageUser('u1');
        $this->aiServiceMock->expects($this->once())
            ->method('generateSmartReplies')
            ->with('Hello')
            ->willReturn(['Hi there!', 'How are you?']);
        $payload = json_encode(['message'=>'Hello']);
        $request = Request::create('/api/messages/smart-reply', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->generateSmartReply($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame(['Hi there!', 'How are you?'], $content['smartReplies']);
    }

    // ==================== POST /messages/moderate ====================

    public function testModerateContent_Unauthenticated_Returns401(): void
    {
        $payload = json_encode(['content'=>'Hello']);
        $request = Request::create('/api/messages/moderate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->moderateContent($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testModerateContent_InvalidJson_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $request = Request::create('/api/messages/moderate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->moderateContent($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testModerateContent_MissingContent_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $payload = json_encode([]);
        $request = Request::create('/api/messages/moderate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->moderateContent($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Content is required', json_decode($response->getContent(), true)['error']);
    }

    public function testModerateContent_Success_Returns200(): void
    {
        $this->setTokenStorageUser('u1');
        $this->aiServiceMock->expects($this->once())
            ->method('moderateContent')
            ->with('Hello')
            ->willReturn(['severity'=>'low','flags'=>[]]);
        $payload = json_encode(['content'=>'Hello']);
        $request = Request::create('/api/messages/moderate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->moderateContent($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('low', $content['moderation']['severity']);
        $this->assertTrue($content['approved']);
    }

    // ==================== POST /messages/{messageId}/reactions ====================

    public function testAddReaction_Unauthenticated_Returns401(): void
    {
        $payload = json_encode(['reaction'=>'👍']);
        $request = Request::create('/api/messages/m1/reactions', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->addReaction($request, 'm1');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAddReaction_InvalidJson_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $request = Request::create('/api/messages/m1/reactions', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->addReaction($request, 'm1');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddReaction_MissingReaction_Returns400(): void
    {
        $this->setTokenStorageUser('u1');
        $payload = json_encode([]);
        $request = Request::create('/api/messages/m1/reactions', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->addReaction($request, 'm1');
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Reaction is required', json_decode($response->getContent(), true)['error']);
    }

    public function testAddReaction_Success_Returns200(): void
    {
        $this->setTokenStorageUser('u1');
        $payload = json_encode(['reaction'=>'👍']);
        $request = Request::create('/api/messages/m1/reactions', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->addReaction($request, 'm1');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('m1', $content['messageId']);
        $this->assertSame('👍', $content['reaction']);
        $this->assertSame('u1', $content['userId']);
        $this->assertTrue($content['added']);
    }

    // ==================== PUT /messages/{messageId}/read ====================

    public function testMarkMessageRead_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/messages/m1/read', 'PUT');
        $response = $this->controller->markMessageRead($request, 'm1');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testMarkMessageRead_Success_Returns200(): void
    {
        $this->setTokenStorageUser('u1');
        $request = Request::create('/api/messages/m1/read', 'PUT');
        $response = $this->controller->markMessageRead($request, 'm1');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('m1', $content['messageId']);
        $this->assertSame('u1', $content['userId']);
        $this->assertNotEmpty($content['readAt']);
    }

    // ==================== Validation helpers ====================

    public function testValidateSendMessageData_Valid_NoException(): void
    {
        $data = ['conversationId'=>'c1','content'=>'Hello'];
        $this->invokePrivate($this->controller, 'validateSendMessageData', [$data]);
        $this->assertTrue(true);
    }

    public function testValidateSendMessageData_MissingConversationId_ReturnsErrors(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateSendMessageData', [['content'=>'Hello']]);
        $this->assertContains('Conversation ID and content are required', $errors);
    }

    public function testValidateSendMessageData_MissingContent_ReturnsErrors(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateSendMessageData', [['conversationId'=>'c1']]);
        $this->assertContains('Conversation ID and content are required', $errors);
    }

    public function testValidateSendMessageData_InvalidConversationId_ReturnsErrors(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateSendMessageData', [['conversationId'=>0,'content'=>'Hello']]);
        $this->assertContains('Invalid conversation ID', $errors);
    }

    public function testValidateSendMessageData_EmptyContent_ReturnsErrors(): void
    {
        $errors = $this->invokePrivate($this->controller, 'validateSendMessageData', [['conversationId'=>'c1','content'=>'   ']]);
        $this->assertContains('Content must be a non-empty string', $errors);
    }

    public function testSerializeMessage_ReturnsArray(): void
    {
        $msg = $this->createMessage('m1', 'c1', 'u1', 'Hello', 'text', null, new \DateTime('2025-01-01 12:00:00'));
        $result = $this->invokePrivate($this->controller, 'serializeMessage', [$msg]);
        $this->assertSame('m1', $result['id']);
        $this->assertSame('c1', $result['conversationId']);
        $this->assertSame('u1', $result['senderId']);
        $this->assertSame('Hello', $result['content']);
        $this->assertSame('text', $result['type']);
        $this->assertSame('2025-01-01 12:00:00', $result['created_at']);
        $this->assertFalse($result['is_edited']);
        $this->assertNull($result['reply_to_message_id']);
    }
}
