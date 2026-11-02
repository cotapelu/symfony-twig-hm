<?php declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Controller;

use App\Conversation\Controller\ConversationController;
use App\Conversation\Entity\Conversation;
use App\Conversation\Service\ConversationService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Conversation\Controller\ConversationController
 */
class ConversationControllerTest extends TestCase
{
    private ConversationController $controller;
    private ConversationService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ConversationService::class);
        $reflection = new \ReflectionClass(ConversationController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $reflectionProperty = $reflection->getProperty('service');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $this->service);
        $this->controller = $controller;
    }

    // ==================== Create Tests ====================

    public function testCreateConversationSuccess(): void
    {
        $conversation = new Conversation([1, 2], 'direct');
        $conversation->setId(1);
        $this->service->expects($this->once())
            ->method('createConversation')
            ->with([1, 2], 'direct', 'Chat')
            ->willReturn($conversation);

        $request = [
            'body' => [
                'participant_ids' => [1, 2],
                'type' => 'direct',
                'title' => 'Chat',
                'created_by' => 1
            ]
        ];

        $result = $this->controller->createConversation($request);

        $this->assertEquals(201, $result['status']);
        $this->assertInstanceOf(Conversation::class, $result['data']);
    }

    // ==================== Read Tests ====================

    public function testGetConversationFound(): void
    {
        $conversation = new Conversation([1, 2], 'direct');
        $conversation->setId(1);
        $this->service->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($conversation);

        $request = ['params' => ['id' => 1]];
        $result = $this->controller->getConversation($request);

        $this->assertEquals(200, $result['status']);
        $this->assertSame($conversation, $result['data']);
    }

    public function testGetConversationNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getConversation')
            ->with(999)
            ->willReturn(null);

        $request = ['params' => ['id' => 999]];
        $result = $this->controller->getConversation($request);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Conversation not found', $result['error']);
    }

    public function testGetUserConversations(): void
    {
        $conv1 = new Conversation([1, 2], 'direct');
        $conv1->setId(1);
        $conv2 = new Conversation([1, 3], 'group');
        $conv2->setId(2);
        $conv2->setTitle('Team');
        $this->service->expects($this->once())
            ->method('getUserConversations')
            ->with(1)
            ->willReturn([$conv1, $conv2]);

        $request = ['params' => ['user_id' => 1]];
        $result = $this->controller->getUserConversations($request);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(2, $result['data']);
    }

    // ==================== Update Tests ====================

    public function testUpdateTitle(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setId(1);
        $conversation->setTitle('New Title');
        $this->service->expects($this->once())
            ->method('updateTitle')
            ->with(1, 'New Title')
            ->willReturn($conversation);

        $request = [
            'params' => ['id' => 1],
            'body' => ['title' => 'New Title']
        ];

        $result = $this->controller->updateTitle($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testUpdateTitleNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('updateTitle')
            ->with(999, 'New Title')
            ->willReturn(null);

        $request = [
            'params' => ['id' => 999],
            'body' => ['title' => 'New Title']
        ];

        $result = $this->controller->updateTitle($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testUpdateAvatar(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setId(1);
        $conversation->setAvatar('/new/avatar.png');
        $this->service->expects($this->once())
            ->method('updateAvatar')
            ->with(1, '/new/avatar.png')
            ->willReturn($conversation);

        $request = [
            'params' => ['id' => 1],
            'body' => ['avatar' => '/new/avatar.png']
        ];

        $result = $this->controller->updateAvatar($request);

        $this->assertEquals(200, $result['status']);
    }

    // ==================== Participant Tests ====================

    public function testAddParticipant(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setId(1);
        $this->service->expects($this->once())
            ->method('addParticipant')
            ->with(1, 3)
            ->willReturn($conversation);

        $request = [
            'params' => ['id' => 1],
            'body' => ['user_id' => 3]
        ];

        $result = $this->controller->addParticipant($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testRemoveParticipant(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setId(1);
        $this->service->expects($this->once())
            ->method('removeParticipant')
            ->with(1, 2)
            ->willReturn($conversation);

        $request = [
            'params' => ['id' => 1, 'user_id' => 2]
        ];

        $result = $this->controller->removeParticipant($request);

        $this->assertEquals(200, $result['status']);
    }

    // ==================== Delete Tests ====================

    public function testDeleteConversationSuccess(): void
    {
        $this->service->expects($this->once())
            ->method('deleteConversation')
            ->with(1)
            ->willReturn(true);

        $request = ['params' => ['id' => 1]];
        $result = $this->controller->deleteConversation($request);

        $this->assertEquals(204, $result['status']);
    }

    public function testDeleteConversationNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('deleteConversation')
            ->with(999)
            ->willReturn(false);

        $request = ['params' => ['id' => 999]];
        $result = $this->controller->deleteConversation($request);

        $this->assertEquals(404, $result['status']);
    }

    // ==================== Search Tests ====================

    public function testSearchConversations(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setId(1);
        $conversation->setTitle('Project Alpha');
        $this->service->expects($this->once())
            ->method('searchConversations')
            ->with(1, 'Project')
            ->willReturn([$conversation]);

        $request = [
            'params' => ['user_id' => 1],
            'query' => ['q' => 'Project']
        ];

        $result = $this->controller->searchConversations($request);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }
}
