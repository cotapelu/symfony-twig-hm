<?php declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Service;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryInterface;
use App\Conversation\Service\ConversationService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Conversation\Service\ConversationService
 */
class ConversationServiceTest extends TestCase
{
    private ConversationService $service;
    private ConversationRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ConversationRepositoryInterface::class);
        $this->service = new ConversationService($this->repository);
    }

    // ==================== Create Tests ====================

    public function testCreateConversationSuccess(): void
    {
        $conversation = new Conversation([1, 2], 'direct');
        $conversation->setId(1);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Conversation $c) {
                $c->setId(1);
                return $c;
            });

        $result = $this->service->createConversation([1, 2], 'direct');

        $this->assertInstanceOf(Conversation::class, $result);
        $this->assertEquals([1, 2], $result->getParticipantIds());
        $this->assertEquals('direct', $result->getType());
    }

    public function testCreateGroupConversationWithTitle(): void
    {
        $conversation = new Conversation([1, 2, 3], 'group');
        $conversation->setId(1);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Conversation $c) {
                $c->setId(1);
                return $c;
            });

        $result = $this->service->createConversation([1, 2, 3], 'group', 'Team Chat');

        $this->assertEquals('group', $result->getType());
        $this->assertEquals('Team Chat', $result->getTitle());
    }

    // ==================== Read Tests ====================

    public function testGetConversationFound(): void
    {
        $conversation = new Conversation([1, 2], 'direct');
        $conversation->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($conversation);

        $result = $this->service->getConversation(1);

        $this->assertSame($conversation, $result);
    }

    public function testGetConversationNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getConversation(999);

        $this->assertNull($result);
    }

    public function testGetUserConversations(): void
    {
        $conv1 = new Conversation([1, 2], 'direct');
        $conv1->setId(1);
        $conv2 = new Conversation([1, 3], 'group');
        $conv2->setId(2);
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with(1)
            ->willReturn([$conv1, $conv2]);

        $result = $this->service->getUserConversations(1);

        $this->assertCount(2, $result);
    }

    // ==================== Update Tests ====================

    public function testAddParticipant(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($conversation);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($conversation);

        $result = $this->service->addParticipant(1, 3);

        $this->assertInstanceOf(Conversation::class, $result);
        $this->assertContains(3, $result->getParticipantIds());
    }

    public function testAddParticipantConversationNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->addParticipant(999, 3);

        $this->assertNull($result);
    }

    public function testRemoveParticipant(): void
    {
        $conversation = new Conversation([1, 2, 3], 'group');
        $conversation->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($conversation);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($conversation);

        $result = $this->service->removeParticipant(1, 3);

        $this->assertInstanceOf(Conversation::class, $result);
        $this->assertNotContains(3, $result->getParticipantIds());
    }

    public function testUpdateTitle(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($conversation);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($conversation);

        $result = $this->service->updateTitle(1, 'New Title');

        $this->assertEquals('New Title', $result->getTitle());
    }

    public function testUpdateAvatar(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($conversation);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($conversation);

        $result = $this->service->updateAvatar(1, '/new/avatar.png');

        $this->assertEquals('/new/avatar.png', $result->getAvatar());
    }

    // ==================== Delete Tests ====================

    public function testDeleteConversationSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $result = $this->service->deleteConversation(1);

        $this->assertTrue($result);
    }

    public function testDeleteConversationNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(999)
            ->willReturn(false);

        $result = $this->service->deleteConversation(999);

        $this->assertFalse($result);
    }

    // ==================== Search Tests ====================

    public function testSearchConversations(): void
    {
        $conv1 = new Conversation([1, 2], 'group');
        $conv1->setId(1);
        $conv1->setTitle('Project Alpha');
        $conv2 = new Conversation([1, 3], 'group');
        $conv2->setId(2);
        $conv2->setTitle('Random Talk');
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with(1)
            ->willReturn([$conv1, $conv2]);

        $result = $this->service->searchConversations(1, 'Project');

        $this->assertCount(1, $result);
        $this->assertEquals('Project Alpha', array_values($result)[0]->getTitle());
    }

    public function testGetDirectConversation(): void
    {
        $conv = new Conversation([1, 2], 'direct');
        $conv->setId(1);
        $this->repository->expects($this->once())
            ->method('findDirectConversation')
            ->with(1, 2)
            ->willReturn($conv);

        $result = $this->service->getDirectConversation(1, 2);

        $this->assertSame($conv, $result);
    }

    public function testGetDirectConversationNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findDirectConversation')
            ->with(1, 2)
            ->willReturn(null);

        $result = $this->service->getDirectConversation(1, 2);

        $this->assertNull($result);
    }
}
