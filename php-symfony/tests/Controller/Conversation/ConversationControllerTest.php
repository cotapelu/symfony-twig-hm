<?php

declare(strict_types=1);

namespace App\Tests\Controller\Conversation;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use App\Conversation\Controller\ConversationController;
use App\Conversation\Entity\Conversation;
use App\Conversation\Service\ConversationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

#[AllowMockObjectsWithoutExpectations]
class ConversationControllerTest extends TestCase
{
    private ConversationController $controller;
    private ConversationService|MockObject $conversationService;
    private UserInterface|MockObject $currentUser;
    private int $userId = 123; // fixed authenticated user ID

    protected function setUp(): void
    {
        $this->conversationService = $this->createMock(ConversationService::class);
        $this->currentUser = $this->createMock(UserInterface::class);
        $this->currentUser->method('getUserIdentifier')->willReturn((string) $this->userId);

        $this->controller = new ConversationController($this->conversationService);
        $this->controller->setTestUser($this->currentUser);
    }

    // ==================== createConversation ====================

    public function testCreateConversation_Success_Returns201(): void
    {
        $data = [
            'participant_ids' => [2, 3],
            'type' => 'group',
            'title' => 'Team Chat',
        ];
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(123);

        $this->conversationService->expects($this->once())
            ->method('createConversation')
            ->with($data['participant_ids'], $data['type'], $data['title'])
            ->willReturn($conversation);

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $result = $this->controller->createConversation($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
    }

    public function testCreateConversation_InvalidJson_Returns400(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        $result = $this->controller->createConversation($request);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testCreateConversation_MissingParticipantIds_Returns400(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['type' => 'group']));
        $result = $this->controller->createConversation($request);
        $this->assertEquals(400, $result->getStatusCode());
    }

    // ==================== getConversation ====================

    public function testGetConversation_Success_Returns200(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(123);
        $conversation->method('hasParticipant')->willReturn(true);

        $this->conversationService->expects($this->once())
            ->method('getConversation')
            ->with(123)
            ->willReturn($conversation);

        $request = new Request();
        $result = $this->controller->getConversation(123, $request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetConversation_NotFound_Returns404(): void
    {
        $this->conversationService->expects($this->once())
            ->method('getConversation')
            ->with(999)
            ->willReturn(null);

        $request = new Request();
        $result = $this->controller->getConversation(999, $request);

        $this->assertEquals(404, $result->getStatusCode());
    }

    // ==================== getUserConversations ====================

    public function testGetUserConversations_Success_Returns200(): void
    {
        $conversations = [$this->createMock(Conversation::class)];
        $this->conversationService->expects($this->once())
            ->method('getUserConversations')
            ->with($this->userId)
            ->willReturn($conversations);

        $request = new Request();
        $result = $this->controller->getUserConversations($this->userId, $request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    // ==================== updateTitle ====================

    public function testUpdateTitle_Success_Returns200(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(123);
        $conversation->method('hasParticipant')->willReturn(true);

        $this->conversationService->expects($this->once())
            ->method('getConversation')
            ->with(123)
            ->willReturn($conversation);
        $this->conversationService->expects($this->once())
            ->method('updateTitle')
            ->with(123, 'New Title')
            ->willReturn($conversation);

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['title' => 'New Title']));
        $result = $this->controller->updateTitle(123, $request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    // ==================== updateAvatar ====================

    public function testUpdateAvatar_Success_Returns200(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(123);
        $conversation->method('hasParticipant')->willReturn(true);

        $this->conversationService->expects($this->once())
            ->method('getConversation')
            ->with(123)
            ->willReturn($conversation);
        $this->conversationService->expects($this->once())
            ->method('updateAvatar')
            ->with(123, 'https://example.com/avatar.jpg')
            ->willReturn($conversation);

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['avatar' => 'https://example.com/avatar.jpg']));
        $result = $this->controller->updateAvatar(123, $request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    // ==================== addParticipant ====================

    public function testAddParticipant_Success_Returns200(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(123);
        $conversation->method('hasParticipant')->willReturn(true);

        $this->conversationService->expects($this->once())
            ->method('getConversation')
            ->with(123)
            ->willReturn($conversation);
        $this->conversationService->expects($this->once())
            ->method('addParticipant')
            ->with(123, 456)
            ->willReturn(true);

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['user_id' => 456]));
        $result = $this->controller->addParticipant(123, $request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    // ==================== removeParticipant ====================

    public function testRemoveParticipant_Success_Returns200(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(123);
        $conversation->method('hasParticipant')->willReturn(true);

        $this->conversationService->expects($this->once())
            ->method('getConversation')
            ->with(123)
            ->willReturn($conversation);
        $this->conversationService->expects($this->once())
            ->method('removeParticipant')
            ->with(123, 456)
            ->willReturn(true);

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['user_id' => 456]));
        $result = $this->controller->removeParticipant(123, 456, $request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    // ==================== deleteConversation ====================

    public function testDeleteConversation_Success_Returns204(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(123);
        $conversation->method('hasParticipant')->willReturn(true);

        $this->conversationService->expects($this->once())
            ->method('getConversation')
            ->with(123)
            ->willReturn($conversation);
        $this->conversationService->expects($this->once())
            ->method('deleteConversation')
            ->with(123)
            ->willReturn(true);

        $request = new Request();
        $result = $this->controller->deleteConversation(123, $request);

        $this->assertEquals(204, $result->getStatusCode());
    }

    // ==================== searchConversations ====================

    public function testSearchConversations_Success_Returns200(): void
    {
        $conversations = [$this->createMock(Conversation::class)];
        $this->conversationService->expects($this->once())
            ->method('searchConversations')
            ->with($this->userId, 'query')
            ->willReturn($conversations);

        $request = new Request(['q' => 'query']);
        $result = $this->controller->searchConversations($this->userId, $request);

        $this->assertEquals(200, $result->getStatusCode());
    }
}
