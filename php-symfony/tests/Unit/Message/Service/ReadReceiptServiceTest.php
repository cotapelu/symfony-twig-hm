<?php declare(strict_types=1);

namespace App\Tests\Unit\Message\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Message\Entity\Message;
use App\Message\Entity\MessageReadReceipt;
use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Service\ReadReceiptService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ReadReceiptServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private MessageRepositoryInterface $messageRepository;
    private ReadReceiptService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageRepository = $this->createMock(MessageRepositoryInterface::class);
        $this->service = new ReadReceiptService($this->entityManager, $this->messageRepository);
    }

    // ================= isMessageReadByUser =================

    public function testIsMessageReadByUserReturnsFalseWhenNoReceipt(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('COUNT(r.id)')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(MessageReadReceipt::class, 'r')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('r.messageId = :messageId')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('r.userId = :userId')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf(); // accept any params
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn('0');

        $result = $this->service->isMessageReadByUser('msg123', 'user123');
        $this->assertFalse($result);
    }

    public function testIsMessageReadByUserReturnsTrueWhenReceiptExists(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('COUNT(r.id)')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(MessageReadReceipt::class, 'r')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('r.messageId = :messageId')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('r.userId = :userId')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn('1');

        $result = $this->service->isMessageReadByUser('msg123', 'user123');
        $this->assertTrue($result);
    }

    // ================= getReadStatus =================

    public function testGetReadStatusWithEmptyMessageIds(): void
    {
        $result = $this->service->getReadStatus([], 'user123');
        $this->assertSame([], $result);
    }

    public function testGetReadStatusReturnsCorrectStatus(): void
    {
        $messageIds = ['msg1', 'msg2'];
        $userId = 'user123';

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('r.messageId')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(MessageReadReceipt::class, 'r')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('r.messageId IN (:messageIds)')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('r.userId = :userId')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getScalarResult')->willReturn([['messageId' => 'msg1']]);

        $result = $this->service->getReadStatus($messageIds, $userId);
        $this->assertSame(['msg1' => true, 'msg2' => false], $result);
    }

    // ================= markMessageAsRead =================

    public function testMarkMessageAsReadReturnsFalseWhenMessageNotFound(): void
    {
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn(null);
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->markMessageAsRead('msg123', 'user123');
        $this->assertFalse($result);
    }

    public function testMarkMessageAsReadReturnsFalseWhenUserNotParticipant(): void
    {
        $message = $this->createMock(Message::class);
        $message->method('getConversationId')->willReturn('conv123');
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $this->messageRepository->expects($this->once())->method('isUserParticipant')->with('conv123', 'user123')->willReturn(false);
        $this->entityManager->expects($this->never())->method('persist');

        $result = $this->service->markMessageAsRead('msg123', 'user123');
        $this->assertFalse($result);
    }

    public function testMarkMessageAsReadReturnsTrueWhenAlreadyRead(): void
    {
        $message = $this->createMock(Message::class);
        $message->method('getConversationId')->willReturn('conv123');
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $this->messageRepository->expects($this->once())->method('isUserParticipant')->with('conv123', 'user123')->willReturn(true);
        // Stub isMessageReadByUser to return true via QB
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('COUNT(r.id)')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(MessageReadReceipt::class, 'r')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('r.messageId = :messageId')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('r.userId = :userId')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn('1');

        $result = $this->service->markMessageAsRead('msg123', 'user123');
        $this->assertTrue($result);
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');
    }

    public function testMarkMessageAsReadCreatesReceiptWhenNotRead(): void
    {
        $message = $this->createMock(Message::class);
        $message->method('getConversationId')->willReturn('conv123');
        $this->messageRepository->expects($this->once())->method('findById')->with('msg123')->willReturn($message);
        $this->messageRepository->expects($this->once())->method('isUserParticipant')->with('conv123', 'user123')->willReturn(true);
        // Stub isMessageReadByUser returns false
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('COUNT(r.id)')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(MessageReadReceipt::class, 'r')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('r.messageId = :messageId')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('r.userId = :userId')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn('0');

        $this->entityManager->expects($this->once())->method('persist')
            ->with($this->callback(function($receipt) {
                return $receipt instanceof MessageReadReceipt
                    && $receipt->getMessageId() === 'msg123'
                    && $receipt->getUserId() === 'user123';
            }));
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->markMessageAsRead('msg123', 'user123');
        $this->assertTrue($result);
    }

    // ================= markMessagesAsRead =================

    public function testMarkMessagesAsReadAggregatesResults(): void
    {
        $messageIds = ['msg1', 'msg2'];
        $userId = 'user123';

        // Partial mock overriding markMessageAsRead
        $partial = $this->getMockBuilder(ReadReceiptService::class)
            ->setConstructorArgs([$this->entityManager, $this->messageRepository])
            ->onlyMethods(['markMessageAsRead'])
            ->getMock();

        $partial->expects($this->exactly(2))
            ->method('markMessageAsRead')
            ->willReturnOnConsecutiveCalls(true, false);

        $result = $partial->markMessagesAsRead($messageIds, $userId);
        $this->assertSame(['msg1' => true, 'msg2' => false], $result);
    }

    // ================= getMessageReadCount =================

    public function testGetMessageReadCountReturnsCount(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('COUNT(r.id)')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(MessageReadReceipt::class, 'r')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('r.messageId = :messageId')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn('5');

        $result = $this->service->getMessageReadCount('msg123');
        $this->assertSame(5, $result);
    }

    // ================= getMessageReadReceipts =================

    public function testGetMessageReadReceiptsReturnsArray(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('r')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(MessageReadReceipt::class, 'r')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('r.messageId = :messageId')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('r.readAt', 'ASC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $receipt1 = new MessageReadReceipt('msg123', 'user1');
        $receipt2 = new MessageReadReceipt('msg123', 'user2');
        $query->expects($this->once())->method('getResult')->willReturn([$receipt1, $receipt2]);

        $result = $this->service->getMessageReadReceipts('msg123');
        $this->assertCount(2, $result);
        $this->assertSame($receipt1, $result[0]);
        $this->assertSame($receipt2, $result[1]);
    }
}
