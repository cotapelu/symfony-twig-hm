<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation;

use App\Conversation\Service\ConversationService;
use App\Conversation\Entity\Conversation;
use PHPUnit\Framework\TestCase;

class ConversationServiceTest extends TestCase
{
    private ConversationService $service;

    protected function setUp(): void
    {
        $this->service = new ConversationService();
    }

    public function testCreateConversationReturnsConversation(): void
    {
        $conv = $this->service->createConversation([1, 2], 'direct');
        $this->assertInstanceOf(Conversation::class, $conv);
        $this->assertSame([1, 2], $conv->getParticipantIds());
        $this->assertSame('direct', $conv->getType());
    }

    public function testCreateConversationWithTitle(): void
    {
        $conv = $this->service->createConversation([1], 'group', 'Team Chat');
        $this->assertSame('Team Chat', $conv->getTitle());
    }

    public function testGetConversationReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->service->getConversation(999));
    }

    public function testGetConversationReturnsAfterCreate(): void
    {
        $conv = $this->service->createConversation([1], 'direct');
        $found = $this->service->getConversation($conv->getId());
        $this->assertSame($conv, $found);
    }

    public function testGetUserConversationsFiltersByParticipant(): void
    {
        $c1 = $this->service->createConversation([1, 2], 'group');
        $c2 = $this->service->createConversation([2, 3], 'group');
        $c3 = $this->service->createConversation([1, 3], 'direct');

        $user1 = $this->service->getUserConversations(1);
        $this->assertCount(2, $user1);
        $ids = array_map(fn($c) => $c->getId(), $user1);
        $this->assertContains($c1->getId(), $ids);
        $this->assertContains($c3->getId(), $ids);
    }

    public function testUpdateTitleChangesTitle(): void
    {
        $conv = $this->service->createConversation([1], 'group', 'Old');
        $updated = $this->service->updateTitle($conv->getId(), 'New Title');
        $this->assertSame('New Title', $updated->getTitle());
    }

    public function testUpdateTitleReturnsNullIfNotFound(): void
    {
        $this->assertNull($this->service->updateTitle(999, 'X'));
    }

    public function testUpdateAvatarChangesAvatar(): void
    {
        $conv = $this->service->createConversation([1], 'group');
        $updated = $this->service->updateAvatar($conv->getId(), 'avatar.png');
        $this->assertSame('avatar.png', $updated->getAvatar());
    }

    public function testUpdateAvatarReturnsNullIfNotFound(): void
    {
        $this->assertNull($this->service->updateAvatar(999, 'x.png'));
    }

    public function testDeleteConversationRemovesIt(): void
    {
        $conv = $this->service->createConversation([1], 'direct');
        $id = $conv->getId();
        $this->assertTrue($this->service->deleteConversation($id));
        $this->assertNull($this->service->getConversation($id));
    }

    public function testDeleteConversationReturnsFalseIfNotFound(): void
    {
        $this->assertFalse($this->service->deleteConversation(999));
    }
}
