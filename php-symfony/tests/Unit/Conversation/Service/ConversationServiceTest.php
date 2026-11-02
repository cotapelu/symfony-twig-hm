<?php declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryInterface;
use App\Conversation\Service\ConversationService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ConversationServiceTest extends TestCase
{
    private ConversationRepositoryInterface $repoMock;
    private ConversationService $service;

    protected function setUp(): void
    {
        $this->repoMock = $this->createMock(ConversationRepositoryInterface::class);
        $this->service = new ConversationService($this->repoMock);
    }

    private function createConversation(?int $id = null, array $participants = [], string $type = 'group', ?string $title = null): Conversation
    {
        $c = new Conversation($participants, $type);
        if ($title !== null) {
            $c->setTitle($title);
        }
        if ($id !== null) {
            $c->setId($id);
        }
        return $c;
    }

    // ==================== createConversation ====================

    public function testCreateConversationSuccess(): void
    {
        $this->repoMock->method('save')->willReturnCallback(function (Conversation $c) {
            $c->setId(123);
            return $c;
        });

        $result = $this->service->createConversation([1, 2], 'group', 'Team Chat');

        $this->assertInstanceOf(Conversation::class, $result);
        $this->assertSame([1, 2], $result->getParticipantIds());
        $this->assertSame('group', $result->getType());
        $this->assertSame('Team Chat', $result->getTitle());
        $this->assertSame(123, $result->getId());
    }

    public function testCreateConversationWithoutTitle(): void
    {
        $this->repoMock->method('save')->willReturnArgument(0);
        $result = $this->service->createConversation([1], 'direct');
        $this->assertNull($result->getTitle());
    }

    // ==================== getConversation ====================

    public function testGetConversation(): void
    {
        $conversation = $this->createConversation(id: 1);
        $this->repoMock->method('findById')->with(1)->willReturn($conversation);
        $result = $this->service->getConversation(1);
        $this->assertSame($conversation, $result);
    }

    public function testGetConversationReturnsNullIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(99)->willReturn(null);
        $result = $this->service->getConversation(99);
        $this->assertNull($result);
    }

    // ==================== getUserConversations ====================

    public function testGetUserConversations(): void
    {
        $convs = [$this->createConversation(id: 1), $this->createConversation(id: 2)];
        $this->repoMock->method('findByUserId')->with(5)->willReturn($convs);
        $result = $this->service->getUserConversations(5);
        $this->assertSame($convs, $result);
    }

    // ==================== addParticipant ====================

    public function testAddParticipantSuccess(): void
    {
        $conversation = $this->createConversation(id: 1, participants: [1, 2]);
        $this->repoMock->method('findById')->with(1)->willReturn($conversation);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->addParticipant(1, 3);

        $this->assertSame($conversation, $result);
        $this->assertSame([1, 2, 3], $result->getParticipantIds());
    }

    public function testAddParticipantReturnsNullIfConversationNotFound(): void
    {
        $this->repoMock->method('findById')->with(1)->willReturn(null);
        $result = $this->service->addParticipant(1, 3);
        $this->assertNull($result);
    }

    public function testAddParticipantDoesNotDuplicate(): void
    {
        $conversation = $this->createConversation(id: 1, participants: [1, 2]);
        $this->repoMock->method('findById')->with(1)->willReturn($conversation);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->addParticipant(1, 2);

        $this->assertSame($conversation, $result);
        $this->assertSame([1, 2], $result->getParticipantIds());
    }

    // ==================== removeParticipant ====================

    public function testRemoveParticipantSuccess(): void
    {
        $conversation = $this->createConversation(id: 1, participants: [1, 2, 3]);
        $this->repoMock->method('findById')->with(1)->willReturn($conversation);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->removeParticipant(1, 2);

        $this->assertSame($conversation, $result);
        $this->assertSame([1, 3], array_values($result->getParticipantIds()));
    }

    public function testRemoveParticipantReturnsNullIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(1)->willReturn(null);
        $result = $this->service->removeParticipant(1, 2);
        $this->assertNull($result);
    }

    public function testRemoveParticipantNonExisting(): void
    {
        $conversation = $this->createConversation(id: 1, participants: [1, 2]);
        $this->repoMock->method('findById')->with(1)->willReturn($conversation);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->removeParticipant(1, 3);

        $this->assertSame($conversation, $result);
        $this->assertSame([1, 2], $result->getParticipantIds());
    }

    // ==================== updateTitle ====================

    public function testUpdateTitle(): void
    {
        $conversation = $this->createConversation(id: 1, title: 'Old');
        $this->repoMock->method('findById')->with(1)->willReturn($conversation);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->updateTitle(1, 'New Title');

        $this->assertSame($conversation, $result);
        $this->assertSame('New Title', $result->getTitle());
    }

    public function testUpdateTitleReturnsNullIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(1)->willReturn(null);
        $result = $this->service->updateTitle(1, 'New');
        $this->assertNull($result);
    }

    // ==================== updateAvatar ====================

    public function testUpdateAvatar(): void
    {
        $conversation = $this->createConversation(id: 1);
        $this->repoMock->method('findById')->with(1)->willReturn($conversation);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->updateAvatar(1, 'avatar_url');

        $this->assertSame($conversation, $result);
        $this->assertSame('avatar_url', $result->getAvatar());
    }

    public function testUpdateAvatarReturnsNullIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(1)->willReturn(null);
        $result = $this->service->updateAvatar(1, 'url');
        $this->assertNull($result);
    }

    // ==================== deleteConversation ====================

    public function testDeleteConversation(): void
    {
        $this->repoMock->method('delete')->with(5)->willReturn(true);
        $result = $this->service->deleteConversation(5);
        $this->assertTrue($result);
    }

    public function testDeleteConversationReturnsFalseIfNotFound(): void
    {
        $this->repoMock->method('delete')->with(5)->willReturn(false);
        $result = $this->service->deleteConversation(5);
        $this->assertFalse($result);
    }
}
