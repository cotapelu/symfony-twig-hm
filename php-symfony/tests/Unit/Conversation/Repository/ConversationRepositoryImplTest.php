<?php

declare(strict_types=1);

namespace Tests\Unit\Conversation\Repository;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryImpl;
use App\Shared\Infrastructure\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;

class ConversationRepositoryImplTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CacheService $cacheService;
    private ConversationRepositoryImpl $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cacheService = $this->createMock(CacheService::class);
        $this->repository = new ConversationRepositoryImpl(
            $this->entityManager,
            $this->cacheService
        );
    }

    public function testSave_CommitsTransaction_AndReturnsConversation(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(123);
        $conversation->method('getParticipantIds')->willReturn(['1', '2']);

        $this->cacheService->expects($this->exactly(2))
            ->method('invalidateConversationList')
            ->with($this->anything());
        $this->cacheService->expects($this->once())
            ->method('invalidateConversationCaches')
            ->with('123');

        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->once())->method('persist')->with($conversation);
        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('commit');

        $result = $this->repository->save($conversation);
        $this->assertSame($conversation, $result);
    }

    public function testSave_RollsBackTransaction_OnException(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getParticipantIds')->willReturn([]);

        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->once())->method('persist')->with($conversation);
        $this->entityManager->expects($this->once())->method('flush')
            ->will($this->throwException(new \Exception('DB error')));
        $this->entityManager->expects($this->once())->method('rollback');

        $this->expectException(\Exception::class);
        $this->repository->save($conversation);
    }

    public function testFindById_ReturnsEntity_WhenFound(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(Conversation::class, 123)
            ->willReturn($conversation);

        $result = $this->repository->findById(123);
        $this->assertSame($conversation, $result);
    }

    public function testFindById_ReturnsNull_WhenNotFound(): void
    {
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(Conversation::class, 999)
            ->willReturn(null);

        $result = $this->repository->findById(999);
        $this->assertNull($result);
    }

    public function testFindByUserId_BuildsQuery_AndReturnsResults(): void
    {
        $userId = 42;
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->once())->method('innerJoin')->with('c.participants', 'p')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('c.updatedAt', 'DESC')->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$this->createMock(Conversation::class)]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $result = $this->repository->findByUserId($userId);
        $this->assertCount(1, $result);
    }

    public function testFindByParticipantId_ReturnsResults(): void
    {
        $participantId = '123';
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->once())->method('innerJoin')->with('c.participants', 'p')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);
        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findByParticipantId($participantId);
        $this->assertIsArray($result);
    }

    public function testFindPublicConversations_BuildsQuery_WithPublicFlag(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('select')->with('c')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('c.isPublic = true')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('c.isArchived = false')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('c.createdAt', 'DESC')->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findPublicConversations();
        $this->assertIsArray($result);
    }

    public function testDelete_RemovesEntity_WhenFound_AndReturnsTrue(): void
    {
        $entity = $this->createMock(Conversation::class);
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(Conversation::class, 123)
            ->willReturn($entity);
        $this->entityManager->expects($this->once())->method('remove')->with($entity);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->repository->delete(123);
        $this->assertTrue($result);
    }

    public function testDelete_ReturnsFalse_WhenNotFound(): void
    {
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(Conversation::class, 999)
            ->willReturn(null);

        $result = $this->repository->delete(999);
        $this->assertFalse($result);
    }

    public function testCountByUserId_ReturnsCorrectCount(): void
    {
        $userId = 42;
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->once())->method('innerJoin')->with('c.participants', 'p')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn('5');
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->countByUserId($userId);
        $this->assertSame(5, $result);
    }

    public function testFindConversationIdsByParticipant_ReturnsArrayOfIds(): void
    {
        $participantId = '123';
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->once())->method('innerJoin')->with('c.participants', 'p')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getScalarResult')->willReturn([
            ['id' => '1'],
            ['id' => '2'],
        ]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findConversationIdsByParticipant($participantId);
        $this->assertSame(['1', '2'], $result);
    }

    public function testFindConversationsWithLastMessage_ReturnsArray(): void
    {
        $userId = 123;
        $connection = $this->createMock(Connection::class);
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([
            [
                'id' => 1,
                'title' => 'Test',
                'type' => 'group',
                'creator_id' => 1,
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00',
                'is_active' => 1,
                'is_archived' => 0,
                'is_public' => 0,
                'description' => 'Desc',
                'avatar_url' => 'url',
                'last_message_id' => 10,
                'last_message_content' => 'Hello',
                'last_message_sender_id' => 2,
                'last_message_type' => 'text',
                'last_message_created_at' => '2024-01-01 00:00:00',
            ],
        ]);
        $statement = $this->createMock(\Doctrine\DBAL\Statement::class);
        $statement->method('executeQuery')->willReturn($result);
        $connection->method('prepare')->willReturn($statement);
        $this->entityManager->method('getConnection')->willReturn($connection);

        $resultArray = $this->repository->findConversationsWithLastMessage((string) $userId);
        $this->assertCount(1, $resultArray);
        $this->assertArrayHasKey('last_message', $resultArray[0]);
        $this->assertIsArray($resultArray[0]['last_message']);
    }

    public function testCountAll_ReturnsTotalCount(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn('42');
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->countAll();
        $this->assertSame(42, $result);
    }

    public function testFindConversationsPaginated_ReturnsPaginatedData(): void
    {
        $page = 2;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $qb = $this->createMock(QueryBuilder::class);
        $selectArgs = [];

        // Capture all select() calls (original and clone)
        $qb->method('select')->willReturnCallback(function($arg) use (&$selectArgs, $qb) {
            $selectArgs[] = $arg;
            return $qb;
        });

        // Expectations on the main QB (before cloning)
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('c.createdAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('setFirstResult')->with($offset)->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();

        // getQuery will be called twice: once on original, once on clone
        $queryMock = $this->createMock(Query::class);
        $qb->expects($this->exactly(2))->method('getQuery')->willReturn($queryMock);

        $queryMock->expects($this->once())->method('getResult')->willReturn([$this->createMock(Conversation::class)]);
        $queryMock->expects($this->once())->method('getSingleScalarResult')->willReturn('100');

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $result = $this->repository->findConversationsPaginated($page, $limit);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(1, $result['data']);
        $this->assertSame(100, $result['total']);
        $this->assertSame(['c', 'COUNT(DISTINCT c.id)'], $selectArgs);
    }

    public function testGetConversationActivity_ReturnsArrayWithDateCounts(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->once())->method('groupBy')->with('DATE(c.createdAt)')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('DATE(c.createdAt)', 'ASC')->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([
            ['date' => '2024-01-01', 'count' => 5],
        ]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getConversationActivity();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('date', $result[0]);
        $this->assertArrayHasKey('count', $result[0]);
    }

    public function testGetConversationActivity_FiltersByDateRange(): void
    {
        $start = '2024-01-01';
        $end = '2024-01-31';

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('setParameter')->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getConversationActivity($start, $end);
        $this->assertIsArray($result);
    }

    public function testIsConversationAdmin_ReturnsTrue_WhenUserIsAdmin(): void
    {
        $conversationId = 'conv123';
        $userId = 'user123';
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(\App\Conversation\Entity\ConversationParticipant::class, 'cp')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $qb->expects($this->exactly(3))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn('1');
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->isConversationAdmin($conversationId, $userId);
        $this->assertTrue($result);
    }

    public function testIsConversationAdmin_ReturnsFalse_WhenUserIsNotAdmin(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(\App\Conversation\Entity\ConversationParticipant::class, 'cp')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn('0');
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->isConversationAdmin('conv', 'user');
        $this->assertFalse($result);
    }

    public function testFindDirectConversation_ReturnsConversation_WhenDirectMatchExists(): void
    {
        $user1 = 1;
        $user2 = 2;

        $conversation = $this->createMock(Conversation::class);
        $conversation->method('hasParticipant')->willReturnMap([
            [$user1, true],
            [$user2, true]
        ]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('c.isArchived = false')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$conversation]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findDirectConversation($user1, $user2);
        $this->assertSame($conversation, $result);
    }

    public function testFindDirectConversation_ReturnsNull_WhenNoDirectMatch(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('c.isArchived = false')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findDirectConversation(1, 2);
        $this->assertNull($result);
    }

    public function testFindByParticipantIds_ReturnsMatchingConversations(): void
    {
        $participantIds = [2, 3];
        sort($participantIds);

        $conv1 = $this->createMock(Conversation::class);
        $conv1->method('getParticipantIds')->willReturn([1, 2]);

        $conv2 = $this->createMock(Conversation::class);
        $conv2->method('getParticipantIds')->willReturn([2, 3]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->with('c.isArchived = false')->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$conv1, $conv2]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findByParticipantIds($participantIds);
        $this->assertCount(1, $result);
        $this->assertSame($conv2, $result[0]);
    }

    public function testFindByParticipant_BuildsQuery_WithPagination(): void
    {
        $userId = 123;
        $page = 2;
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->once())->method('innerJoin')->with('c.participants', 'p')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('c.updatedAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('setFirstResult')->with($offset)->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findByParticipant((string)$userId, $page, $limit);
        $this->assertIsArray($result);
    }

    public function testInvalidateConversationCache_IsNoOp(): void
    {
        // No expectations set - just verify it doesn't throw
        $this->repository->invalidateConversationCache('user123');
        $this->assertTrue(true);
    }

    public function testGetParticipantIds_ReturnsEmptyArray_WhenConversationNotFound(): void
    {
        $conversationId = 'nonexistent';
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(Conversation::class, $conversationId)
            ->willReturn(null);

        $result = $this->repository->getParticipantIds($conversationId);
        $this->assertSame([], $result);
    }

    public function testGetParticipantIds_ReturnsIds_WhenConversationExists(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getParticipantIds')->willReturn(['1', '2', '3']);
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(Conversation::class, '123')
            ->willReturn($conversation);

        $result = $this->repository->getParticipantIds('123');
        $this->assertSame(['1', '2', '3'], $result);
    }

    public function testFindAllIds_ReturnsListOfIds(): void
    {
        $userId = 123;
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->once())->method('innerJoin')->with('c.participants', 'p')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getScalarResult')->willReturn([
            ['id' => '10'],
            ['id' => '20'],
        ]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findAllIds((string)$userId);
        $this->assertSame(['10', '20'], $result);
    }

    public function testSearchByTitle_BuildsLikeQuery_WithPagination(): void
    {
        $userId = 123;
        $titleQuery = 'meeting';
        $page = 1;
        $limit = 10;
        $offset = 0;

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Conversation::class, 'c')->willReturnSelf();
        $qb->expects($this->once())->method('innerJoin')->with('c.participants', 'p')->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('where')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('c.updatedAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('setFirstResult')->with($offset)->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->searchByTitle((string)$userId, $titleQuery, $page, $limit);
        $this->assertIsArray($result);
    }
}
