<?php

declare(strict_types=1);

namespace App\Tests\Conversation\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Conversation\Service\ConversationService;
use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryInterface;

#[AllowMockObjectsWithoutExpectations]
class ConversationServiceTest extends TestCase
{
    private ConversationRepositoryInterface $repo;
    private ConversationService $service;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ConversationRepositoryInterface::class);
        // By default, repository->save returns the passed entity
        $this->repo->method('save')
            ->willReturnCallback(fn($entity) => $entity);
        $this->service = new ConversationService($this->repo);
    }

    public function testCreateConversation(): void
    {
        $participants = [1, 2, 3];

        $this->repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Conversation $c) use ($participants) {
                return $c->getParticipantIds() === $participants;
            }))
            ->willReturnCallback(function (Conversation $c) {
                return $c; // Return the same conversation
            });

        $conversation = $this->service->createConversation($participants, 'group', 'Test Group');

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertSame($participants, $conversation->getParticipantIds());
        $this->assertSame('group', $conversation->getType());
        $this->assertSame('Test Group', $conversation->getTitle());
    }

    public function testGetConversation(): void
    {
        $id = 42;
        $conversation = new Conversation([1], 'direct');
        $conversation->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($conversation);

        $result = $this->service->getConversation($id);
        $this->assertSame($conversation, $result);
    }

    public function testGetConversationNotFound(): void
    {
        $this->repo->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getConversation(999);
        $this->assertNull($result);
    }

    public function testGetUserConversations(): void
    {
        $userId = 5;
        $conv1 = new Conversation([$userId], 'direct');
        $conv2 = new Conversation([$userId, 6], 'group');
        $conversations = [$conv1, $conv2];

        $this->repo->expects($this->once())
            ->method('findByUserId')
            ->with((string)$userId)
            ->willReturn($conversations);

        $result = $this->service->getUserConversations($userId);
        $this->assertSame($conversations, $result);
    }

    public function testAddParticipant(): void
    {
        $id = 10;
        $newUser = 7;
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($conversation);
        $this->repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Conversation $c) use ($newUser) {
                return in_array($newUser, $c->getParticipantIds(), true);
            }))
            ->willReturn($conversation);

        $result = $this->service->addParticipant($id, $newUser);
        $this->assertSame($conversation, $result);
        $this->assertContains($newUser, $conversation->getParticipantIds());
    }

    public function testRemoveParticipant(): void
    {
        $id = 11;
        $userIdToRemove = 2;
        $conversation = new Conversation([1, 2, 3], 'group');
        $conversation->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($conversation);
        $this->repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Conversation $c) use ($userIdToRemove) {
                return !in_array($userIdToRemove, $c->getParticipantIds(), true);
            }))
            ->willReturn($conversation);

        $result = $this->service->removeParticipant($id, $userIdToRemove);
        $this->assertSame($conversation, $result);
        $this->assertNotContains($userIdToRemove, $conversation->getParticipantIds());
    }

    public function testDeleteConversation(): void
    {
        $id = 12;
        $this->repo->expects($this->once())
            ->method('delete')
            ->with($id)
            ->willReturn(true);

        $result = $this->service->deleteConversation($id);
        $this->assertTrue($result);
    }

    public function testHasParticipant(): void
    {
        $conversation = new Conversation([1, 2, 3], 'group');
        $this->assertTrue($conversation->hasParticipant(2));
        $this->assertFalse($conversation->hasParticipant(4));
    }
}
