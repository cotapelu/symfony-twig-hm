<?php

declare(strict_types=1);

namespace Tests\Unit\Message\Repository;

use App\Message\Repository\MessageRepositoryImpl;
use App\Message\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use App\Shared\Infrastructure\CacheService;
use App\Conversation\Repository\ConversationRepositoryInterface;
use PHPUnit\Framework\TestCase;

class MessageRepositoryImplTest extends TestCase
{
    private EntityManagerInterface $entityManagerMock;
    private CacheService $cacheServiceMock;
    private ConversationRepositoryInterface $conversationRepoMock;
    private MessageRepositoryImpl $repository;

    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->cacheServiceMock = $this->createMock(CacheService::class);
        $this->conversationRepoMock = $this->createMock(ConversationRepositoryInterface::class);

        $this->repository = new MessageRepositoryImpl(
            $this->entityManagerMock,
            $this->createMock(\Symfony\Contracts\Cache\CacheInterface::class),
            $this->conversationRepoMock,
            $this->cacheServiceMock
        );
    }

    public function testFindById_ReturnsNull_WhenNotFound(): void
    {
        $this->entityManagerMock->expects($this->once())
            ->method('find')
            ->with(Message::class, 'nonexistent')
            ->willReturn(null);

        $result = $this->repository->findById('nonexistent');
        $this->assertNull($result);
    }

    public function testFindById_ReturnsMessage_WhenFound(): void
    {
        $message = $this->createMock(Message::class);
        $this->entityManagerMock->expects($this->once())
            ->method('find')
            ->with(Message::class, 'msg-123')
            ->willReturn($message);

        $result = $this->repository->findById('msg-123');
        $this->assertSame($message, $result);
    }

    public function testCountAll_ReturnsInteger(): void
    {
        $this->entityManagerMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class));

        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(m.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('from')
            ->with(Message::class, 'm')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query = $this->createMock(\Doctrine\ORM\Query::class));
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('42');

        $result = $this->repository->countAll();
        $this->assertSame(42, $result);
    }

    public function testDelete_CallsEntityManagerRemoveAndFlush(): void
    {
        $id = 'msg-123';
        $message = $this->createMock(Message::class);
        $this->entityManagerMock->expects($this->once())
            ->method('find')
            ->with(Message::class, $id)
            ->willReturn($message);
        $this->entityManagerMock->expects($this->once())
            ->method('remove')
            ->with($message);
        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $this->repository->delete($id);
    }

    public function testSave_PersistsAndFlushesAndInvalidatesCaches(): void
    {
        $message = $this->createMock(Message::class);
        $message->method('getSenderId')->willReturn('user-123');

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($message);
        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $this->cacheServiceMock->expects($this->once())
            ->method('invalidateUserCaches')
            ->with('user-123');

        $this->repository->save($message);
    }

    public function testCountByConversationId_ReturnsInt(): void
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('select')->with('COUNT(m.id)')->willReturnSelf();
        $qb->method('from')->with(Message::class, 'm')->willReturnSelf();
        $qb->method('where')->with('m.conversationId = :conversationId')->willReturnSelf();
        $qb->method('setParameter')->with('conversationId', 'conv-123')->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getSingleScalarResult')->willReturn('42');
        $qb->method('getQuery')->willReturn($query);
        $this->entityManagerMock->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->countByConversationId('conv-123');
        $this->assertSame(42, $result);
    }

    public function testFindByConversationId_ReturnsMessages(): void
    {
        $conversationId = 'conv-123';
        $messages = [$this->createMock(Message::class), $this->createMock(Message::class)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('m')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('from')
            ->with(Message::class, 'm')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('m.conversationId = :conversationId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('conversationId', $conversationId)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('m.createdAt', 'ASC')
            ->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($messages);

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->entityManagerMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $result = $this->repository->findByConversationId($conversationId);
        $this->assertSame($messages, $result);
    }

    public function testFindByConversationIdWithPagination_AppliesLimitOffsetAndOrdersDesc(): void
    {
        $conversationId = 'conv-123';
        $limit = 20;
        $offset = 40;
        $messages = [$this->createMock(Message::class)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())->method('select')->with('m')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Message::class, 'm')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('m.conversationId = :conversationId')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('conversationId', $conversationId)->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('m.createdAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();
        $qb->expects($this->once())->method('setFirstResult')->with($offset)->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())->method('getResult')->willReturn($messages);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->entityManagerMock->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findByConversationIdWithPagination($conversationId, $limit, $offset);
        $this->assertSame($messages, $result);
    }

    public function testFindRecentByUserId_ReturnsMessagesAndCaches(): void
    {
        $userId = 'user-123';
        $limit = 10;
        $messages = [$this->createMock(Message::class)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('select')->with('m')->willReturnSelf();
        $qb->method('from')->with(Message::class, 'm')->willReturnSelf();
        $qb->method('innerJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('orderBy')->with('m.createdAt', 'DESC')->willReturnSelf();
        $qb->method('setMaxResults')->with($limit)->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn($messages);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManagerMock->method('createQueryBuilder')->willReturn($qb);

        $cacheMock = $this->createMock(\Symfony\Contracts\Cache\CacheInterface::class);
        $cacheMock->expects($this->once())
            ->method('get')
            ->with("messages:recent:{$userId}:{$limit}", $this->isCallable())
            ->willReturn($messages);

        $this->repository = new MessageRepositoryImpl(
            $this->entityManagerMock,
            $cacheMock,
            $this->conversationRepoMock,
            $this->cacheServiceMock
        );

        $result = $this->repository->findRecentByUserId($userId, $limit);
        $this->assertSame($messages, $result);
    }

    public function testFindRecent_ReturnsMessagesOrderedByCreatedAtDesc(): void
    {
        $limit = 5;
        $messages = [$this->createMock(Message::class), $this->createMock(Message::class)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())->method('select')->with('m')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Message::class, 'm')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('m.createdAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())->method('getResult')->willReturn($messages);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->entityManagerMock->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findRecent($limit);
        $this->assertSame($messages, $result);
    }

    public function testCountByUserId_ReturnsIntAndCaches(): void
    {
        $userId = 'user-123';
        $count = 42;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('select')->with('COUNT(m.id)')->willReturnSelf();
        $qb->method('from')->with(Message::class, 'm')->willReturnSelf();
        $qb->method('innerJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getSingleScalarResult')->willReturn((string)$count);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManagerMock->method('createQueryBuilder')->willReturn($qb);

        $cacheMock = $this->createMock(\Symfony\Contracts\Cache\CacheInterface::class);
        $cacheMock->expects($this->once())
            ->method('get')
            ->with("messages:count:{$userId}", $this->isCallable())
            ->willReturn($count);

        $this->repository = new MessageRepositoryImpl(
            $this->entityManagerMock,
            $cacheMock,
            $this->conversationRepoMock,
            $this->cacheServiceMock
        );

        $result = $this->repository->countByUserId($userId);
        $this->assertSame($count, $result);
    }

    public function testFindBySenderId_ReturnsMessagesOrderedByCreatedAtDesc(): void
    {
        $senderId = 'user-456';
        $messages = [$this->createMock(Message::class)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())->method('select')->with('m')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Message::class, 'm')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('m.senderId = :senderId')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('senderId', $senderId)->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('m.createdAt', 'DESC')->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())->method('getResult')->willReturn($messages);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->entityManagerMock->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findBySenderId($senderId);
        $this->assertSame($messages, $result);
    }

    public function testIsUserParticipant_ReturnsTrueAndCaches(): void
    {
        $conversationId = 'conv-123';
        $userId = 'user-789';
        $conversationIds = ['conv-123', 'conv-456'];

        $this->conversationRepoMock->expects($this->once())
            ->method('findConversationIdsByParticipant')
            ->with($userId)
            ->willReturn($conversationIds);

        $cacheMock = $this->createMock(\Symfony\Contracts\Cache\CacheInterface::class);
        $cacheMock->expects($this->once())
            ->method('get')
            ->with("user:{$userId}:conversation:{$conversationId}:participant", $this->isCallable())
            ->willReturn(true);

        $this->repository = new MessageRepositoryImpl(
            $this->entityManagerMock,
            $cacheMock,
            $this->conversationRepoMock,
            $this->cacheServiceMock
        );

        $result = $this->repository->isUserParticipant($conversationId, $userId);
        $this->assertTrue($result);
    }

    public function testFindByThreadIdReturnsEmpty(): void
    {
        $result = $this->repository->findByThreadId('thread-123');
        $this->assertSame([], $result);
    }

    public function testFindLatestMessagesByConversationIdsReturnsMappedArray(): void
    {
        $conversationIds = ['conv-1', 'conv-2'];
        $message1 = $this->createMock(Message::class);
        $message1->method('getConversationId')->willReturn('conv-1');
        $message2 = $this->createMock(Message::class);
        $message2->method('getConversationId')->willReturn('conv-2');
        $messages = [$message1, $message2];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())->method('select')->with('m')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Message::class, 'm')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('m.conversationId IN (:conversationIds)')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with($this->stringContains('MAX(m2.id)'))->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('conversationIds', $conversationIds)->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('m.createdAt', 'DESC')->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())->method('getResult')->willReturn($messages);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->entityManagerMock->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findLatestMessagesByConversationIds($conversationIds);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('conv-1', $result);
        $this->assertArrayHasKey('conv-2', $result);
        $this->assertSame($message1, $result['conv-1']);
        $this->assertSame($message2, $result['conv-2']);
    }

    public function testCountUnreadMessagesInConversationReturnsCount(): void
    {
        $conversationId = 'conv-123';
        $userId = 'user-456';
        $expectedCount = 5;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('select')->with('COUNT(m.id)')->willReturnSelf();
        $qb->method('from')->with(Message::class, 'm')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getSingleScalarResult')->willReturn((string)$expectedCount);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManagerMock->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->countUnreadMessagesInConversation($conversationId, $userId);
        $this->assertSame($expectedCount, $result);
    }

    public function testSearchMessagesReturnsResults(): void
    {
        $query = 'test';
        $messages = [$this->createMock(Message::class)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())->method('select')->with('m')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Message::class, 'm')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('m.createdAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with(50)->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with($this->stringContains('TO_TSVECTOR'))->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('query', $this->isType('string'))->willReturnSelf();

        $queryObj = $this->createMock(\Doctrine\ORM\Query::class);
        $queryObj->expects($this->once())->method('getResult')->willReturn($messages);
        $qb->expects($this->once())->method('getQuery')->willReturn($queryObj);

        $this->entityManagerMock->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->searchMessages($query);
        $this->assertSame($messages, $result);
    }

    public function testIsConversationAdminReturnsTrue(): void
    {
        $conversationId = 'conv-123';
        $userId = 'user-789';
        $this->conversationRepoMock->expects($this->once())
            ->method('isConversationAdmin')
            ->with($conversationId, $userId)
            ->willReturn(true);

        $result = $this->repository->isConversationAdmin($conversationId, $userId);
        $this->assertTrue($result);
    }
}
