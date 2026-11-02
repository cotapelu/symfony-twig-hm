<?php declare(strict_types=1);

namespace App\Tests\Unit\Message\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Message\Entity\Message;
use App\Message\DomainEvent\MessageDeletedEvent;
use App\Message\Repository\MessageRepositoryImpl;
use App\Message\Service\DeleteMessageService;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeleteMessageServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private MessageRepositoryImpl $messageRepository;
    private DomainEventPublisherInterface $eventPublisher;
    private DeleteMessageService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageRepository = $this->getMockBuilder(MessageRepositoryImpl::class)->disableOriginalConstructor()->getMock();
        $this->eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $this->service = new DeleteMessageService(
            $this->entityManager,
            $this->messageRepository,
            $this->eventPublisher
        );
    }

    private function createMessageMock(
        ?string $id = 'msg123',
        ?string $senderId = 'user123',
        ?string $conversationId = 'conv123',
        bool $isDeleted = false,
        ?DateTime $deletedAt = null,
        ?string $deletedBy = null
    ): Message {
        $msg = $this->createMock(Message::class);
        $msg->method('getId')->willReturn($id);
        $msg->method('getSenderId')->willReturn($senderId);
        $msg->method('getConversationId')->willReturn($conversationId);
        $msg->method('isDeleted')->willReturn($isDeleted);
        $msg->method('getDeletedAt')->willReturn($deletedAt);
        $msg->method('getDeletedBy')->willReturn($deletedBy);
        // Allow setters to be called without error
        $msg->expects($this->any())->method('setIsDeleted')->with($this->isBool());
        $msg->expects($this->any())->method('setDeletedAt')->with($this->logicalOr($this->isNull(), $this->isInstanceOf(DateTime::class)));
        $msg->expects($this->any())->method('setDeletedBy')->with($this->logicalOr($this->isString(), $this->isNull()));
        return $msg;
    }

    // ================= deleteMessage =================

    public function testDeleteMessageReturnsNullWhenNotFound(): void
    {
        $this->messageRepository->expects($this->once())->method('findById')->with('nonexistent')->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');
        $this->eventPublisher->expects($this->never())->method('publish');

        $result = $this->service->deleteMessage('nonexistent', 'user123');
        $this->assertNull($result);
    }

    public function testDeleteMessageThrowsWhenAlreadyDeleted(): void
    {
        $message = $this->createMessageMock('msg123', 'user123', 'conv123', true);
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $this->entityManager->expects($this->never())->method('flush');
        $this->eventPublisher->expects($this->never())->method('publish');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Message already deleted');
        $this->service->deleteMessage('msg123', 'user123');
    }

    public function testDeleteMessageThrowsWhenNotOwnerAndNotAdmin(): void
    {
        $message = $this->createMessageMock('msg123', 'owner456', 'conv123', false);
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $this->messageRepository->expects($this->once())->method('isConversationAdmin')->with('conv123', 'user123')->willReturn(false);
        $this->entityManager->expects($this->never())->method('flush');
        $this->eventPublisher->expects($this->never())->method('publish');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You can only delete your own messages');
        $this->service->deleteMessage('msg123', 'user123');
    }

    public function testDeleteMessageSucceedsForOwner(): void
    {
        $userId = 'user123';
        $messageId = 'msg123';
        $conversationId = 'conv123';
        $message = $this->createMessageMock($messageId, $userId, $conversationId, false);
        $this->messageRepository->expects($this->once())->method('findById')->with($messageId)->willReturn($message);
        // No admin check because owner
        $this->messageRepository->expects($this->never())->method('isConversationAdmin');

        // Expect setters and flush
        $message->expects($this->once())->method('setIsDeleted')->with(true);
        $message->expects($this->once())->method('setDeletedAt')->with($this->isInstanceOf(DateTime::class));
        $message->expects($this->once())->method('setDeletedBy')->with($userId);
        $this->entityManager->expects($this->once())->method('flush');

        // Expect event publish
        $this->eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(MessageDeletedEvent::class));

        $result = $this->service->deleteMessage($messageId, $userId);
        $this->assertSame($message, $result);
    }

    public function testDeleteMessageSucceedsForAdmin(): void
    {
        $userId = 'adminUser';
        $messageId = 'msg123';
        $conversationId = 'conv123';
        $message = $this->createMessageMock($messageId, 'ownerOther', $conversationId, false);
        $this->messageRepository->expects($this->once())->method('findById')->with($messageId)->willReturn($message);
        $this->messageRepository->expects($this->once())->method('isConversationAdmin')->with($conversationId, $userId)->willReturn(true);

        $message->expects($this->once())->method('setIsDeleted')->with(true);
        $message->expects($this->once())->method('setDeletedAt')->with($this->isInstanceOf(DateTime::class));
        $message->expects($this->once())->method('setDeletedBy')->with($userId);
        $this->entityManager->expects($this->once())->method('flush');

        $this->eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(MessageDeletedEvent::class));

        $result = $this->service->deleteMessage($messageId, $userId);
        $this->assertSame($message, $result);
    }

    // ================= undeleteMessage =================

    public function testUndeleteMessageReturnsNullWhenNotFound(): void
    {
        $this->messageRepository->expects($this->once())->method('findById')->with('nonexistent')->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->undeleteMessage('nonexistent', 'user123');
        $this->assertNull($result);
    }

    public function testUndeleteMessageThrowsWhenNotOwner(): void
    {
        $message = $this->createMessageMock('msg123', 'owner456', 'conv123', true, new DateTime(), 'deleter');
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You can only undelete your own messages');
        $this->service->undeleteMessage('msg123', 'user123');
    }

    public function testUndeleteMessageReturnsMessageIfNotDeleted(): void
    {
        $message = $this->createMessageMock('msg123', 'user123', 'conv123', false);
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        // Expect no state changes, no flush
        $message->expects($this->never())->method('setIsDeleted');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->undeleteMessage('msg123', 'user123');
        $this->assertSame($message, $result);
    }

    public function testUndeleteMessageThrowsWhenTimeoutExceeded(): void
    {
        $deletedAt = new DateTime('@' . (time() - 31 * 60)); // 31 minutes ago
        $message = $this->createMessageMock('msg123', 'user123', 'conv123', true, $deletedAt, 'user123');
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Can only undelete messages within 30 minutes of deletion');
        $this->service->undeleteMessage('msg123', 'user123');
    }

    public function testUndeleteMessageSucceedsWithinTime(): void
    {
        $deletedAt = new DateTime('@' . (time() - 10 * 60)); // 10 minutes ago
        $message = $this->createMessageMock('msg123', 'user123', 'conv123', true, $deletedAt, 'user123');
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);

        $message->expects($this->once())->method('setIsDeleted')->with(false);
        $message->expects($this->once())->method('setDeletedAt')->with(null);
        $message->expects($this->once())->method('setDeletedBy')->with(null);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->undeleteMessage('msg123', 'user123');
        $this->assertSame($message, $result);
    }

    // ================= canDeleteMessage =================

    public function testCanDeleteMessageReturnsFalseWhenNotFound(): void
    {
        $this->messageRepository->expects($this->once())->method('findById')->with('nonexistent')->willReturn(null);
        $result = $this->service->canDeleteMessage('nonexistent', 'user123');
        $this->assertFalse($result);
    }

    public function testCanDeleteMessageReturnsFalseWhenAlreadyDeleted(): void
    {
        $message = $this->createMessageMock('msg123', 'user123', 'conv123', true);
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $result = $this->service->canDeleteMessage('msg123', 'user123');
        $this->assertFalse($result);
    }

    public function testCanDeleteMessageReturnsTrueWhenOwner(): void
    {
        $message = $this->createMessageMock('msg123', 'user123', 'conv123', false);
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $this->messageRepository->expects($this->never())->method('isConversationAdmin');
        $result = $this->service->canDeleteMessage('msg123', 'user123');
        $this->assertTrue($result);
    }

    public function testCanDeleteMessageReturnsTrueWhenAdmin(): void
    {
        $message = $this->createMessageMock('msg123', 'otherUser', 'conv123', false);
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $this->messageRepository->expects($this->once())->method('isConversationAdmin')->with('conv123', 'adminUser')->willReturn(true);
        $result = $this->service->canDeleteMessage('msg123', 'adminUser');
        $this->assertTrue($result);
    }

    public function testCanDeleteMessageReturnsFalseWhenNeitherOwnerNorAdmin(): void
    {
        $message = $this->createMessageMock('msg123', 'otherUser', 'conv123', false);
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $this->messageRepository->expects($this->once())->method('isConversationAdmin')->with('conv123', 'user123')->willReturn(false);
        $result = $this->service->canDeleteMessage('msg123', 'user123');
        $this->assertFalse($result);
    }
}
