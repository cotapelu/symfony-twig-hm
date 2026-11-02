<?php declare(strict_types=1);

namespace Tests\Unit\Conversation\Repository;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConversationRepositoryTest extends TestCase
{
    private PDO&MockObject $pdoMock;
    private PDOStatement&MockObject $stmtMock;
    private ConversationRepository $repository;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->repository = new ConversationRepository($this->pdoMock);
    }

    private function willPrepareExecuteFetchRow(?array $row = null): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with($this->anything())->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($row);
    }

    private function willPrepareExecuteFetchAll(array $rows): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with($this->anything())->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($rows);
    }

    private function willExecuteAndReturnInt(int $value): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with($this->anything())->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetchColumn')->willReturn($value);
    }

    private function willLastInsertId(int $id): void
    {
        $this->pdoMock->expects($this->once())->method('lastInsertId')->willReturn((string)$id);
    }

    private function createConversationEntity(array $row): Conversation
    {
        $participants = is_string($row['user_ids']) ? json_decode($row['user_ids'], true) : $row['user_ids'];
        $conv = new Conversation($participants, $row['type']);
        $conv->setId((int)$row['id']);
        $conv->setTitle($row['title']);
        $conv->setCreatedBy((int)$row['created_by']);
        $conv->setAvatar($row['avatar']);
        $conv->setLastMessageId($row['last_message_id'] ? (int)$row['last_message_id'] : null);
        return $conv;
    }

    // ==================== findById ====================

    public function testFindById_ReturnsNull_WhenNotFound(): void
    {
        $this->willPrepareExecuteFetchRow(null);

        $result = $this->repository->findById(999);
        $this->assertNull($result);
    }

    public function testFindById_ReturnsConversation_WhenFound(): void
    {
        $row = [
            'id' => 1,
            'user_ids' => json_encode([1,2]),
            'type' => 'direct',
            'title' => 'Chat',
            'created_by' => 1,
            'avatar' => 'avatar.jpg',
            'last_message_id' => 10,
        ];
        $this->willPrepareExecuteFetchRow($row);

        $result = $this->repository->findById(1);
        $this->assertInstanceOf(Conversation::class, $result);
        $this->assertSame(1, $result->getId());
        $this->assertSame('Chat', $result->getTitle());
    }

    // ==================== findByUserId ====================

    public function testFindByUserId_ReturnsEmptyArray_WhenNone(): void
    {
        $this->willPrepareExecuteFetchAll([]);

        $result = $this->repository->findByUserId(123);
        $this->assertEmpty($result);
    }

    public function testFindByUserId_ReturnsConversations_WhenFound(): void
    {
        $rows = [
            [
                'id' => 1,
                'user_ids' => json_encode([123,456]),
                'type' => 'direct',
                'title' => 'Chat A',
                'created_by' => 123,
                'avatar' => null,
                'last_message_id' => 5,
            ],
            [
                'id' => 2,
                'user_ids' => json_encode([123]),
                'type' => 'group',
                'title' => 'Group G',
                'created_by' => 456,
                'avatar' => 'g.jpg',
                'last_message_id' => null,
            ],
        ];
        $this->willPrepareExecuteFetchAll($rows);

        $result = $this->repository->findByUserId(123);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Conversation::class, $result[0]);
    }

    // ==================== findDirectConversation ====================

    public function testFindDirectConversation_ReturnsNull_WhenNone(): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->stringContains('type = \'direct\''))->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with([])->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]);

        $result = $this->repository->findDirectConversation(1, 2);
        $this->assertNull($result);
    }

    public function testFindDirectConversation_ReturnsConversation_WhenFound(): void
    {
        $rows = [
            [
                'id' => 5,
                'user_ids' => json_encode([1,2]),
                'type' => 'direct',
                'title' => '',
                'created_by' => 1,
                'avatar' => null,
                'last_message_id' => null,
            ],
            // other rows not matching participants will be skipped
        ];
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->stringContains('type = \'direct\''))->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with([])->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($rows);

        $result = $this->repository->findDirectConversation(1,2);
        $this->assertInstanceOf(Conversation::class, $result);
        $this->assertSame(5, $result->getId());
    }

    // ==================== save (insert) ====================

    public function testSave_Insert_NewEntity(): void
    {
        $participants = [1,2];
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(null);
        $conversation->method('getParticipantIds')->willReturn($participants);
        $conversation->method('getType')->willReturn('direct');
        $conversation->method('getTitle')->willReturn('Chat');
        $conversation->method('getCreatedBy')->willReturn(1);
        $conversation->method('getAvatar')->willReturn(null);
        $conversation->method('getLastMessageId')->willReturn(null);
        $conversation->expects($this->once())->method('setId')->with(123);

        // Mock prepare for INSERT
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->stringContains('INSERT'))->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with(
            $this->callback(function($args) use ($participants) {
                $this->assertSame(json_encode($participants), $args[0]);
                return true;
            })
        )->willReturn(true);
        $this->willLastInsertId(123);

        $result = $this->repository->save($conversation);
        $this->assertSame($conversation, $result);
    }

    // ==================== save (update) ====================

    public function testSave_Update_ExistingEntity(): void
    {
        $participants = [1,2];
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(5);
        $conversation->method('getParticipantIds')->willReturn($participants);
        $conversation->method('getType')->willReturn('group');
        $conversation->method('getTitle')->willReturn('Group');
        $conversation->method('getCreatedBy')->willReturn(1);
        $conversation->method('getAvatar')->willReturn('avatar.jpg');
        $conversation->method('getLastMessageId')->willReturn(10);

        $this->pdoMock->expects($this->once())->method('prepare')->with($this->stringContains('UPDATE'))->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with(
            $this->callback(function($args) use ($participants) {
                // parameters: user_ids, type, title, created_by, avatar, last_message_id, id
                $this->assertSame(json_encode($participants), $args[0]);
                $this->assertSame('group', $args[1]);
                $this->assertSame('Group', $args[2]);
                $this->assertSame(1, $args[3]);
                $this->assertSame('avatar.jpg', $args[4]);
                $this->assertSame(10, $args[5]);
                $this->assertSame(5, $args[6]);
                return true;
            })
        )->willReturn(true);

        $result = $this->repository->save($conversation);
        $this->assertSame($conversation, $result);
    }

    // ==================== delete ====================

    public function testDelete_SetsDeletedAt(): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with(
            $this->stringContains('UPDATE conversations SET deleted_at')
        )->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with([123])->willReturn(true);

        $result = $this->repository->delete(123);
        $this->assertTrue($result);
    }

    // ==================== findAllIds ====================

    public function testFindAllIds_ReturnsIds(): void
    {
        $rows = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];
        $this->pdoMock->expects($this->once())->method('prepare')->with(
            $this->stringContains('SELECT id FROM conversations WHERE user_ids LIKE ? AND deleted_at IS NULL')
        )->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with(["%123%"])->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($rows);

        $result = $this->repository->findAllIds('123');
        $this->assertSame([1,2,3], $result);
    }

    // ==================== countByUserId ====================

    public function testCountByUserId_ReturnsCount(): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with(
            $this->stringContains('SELECT COUNT(*) FROM conversations WHERE user_ids LIKE ? AND deleted_at IS NULL')
        )->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with(["%456%"])->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetchColumn')->willReturn('5');

        $result = $this->repository->countByUserId(456);
        $this->assertSame(5, $result);
    }

    // ==================== findPublicConversations ====================

    public function testFindPublicConversations_ReturnsArray(): void
    {
        $rows = [
            [
                'id' => 10,
                'user_ids' => json_encode([1,2,3]),
                'type' => 'public',
                'title' => 'Public Chat',
                'created_by' => 1,
                'avatar' => null,
                'last_message_id' => null,
            ],
        ];
        $this->willPrepareExecuteFetchAll($rows);

        $result = $this->repository->findPublicConversations();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Conversation::class, $result[0]);
    }

    // ==================== countAll ====================

    public function testCountAll_ReturnsInt(): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with(
            $this->stringContains('SELECT COUNT(*) FROM conversations WHERE deleted_at IS NULL')
        )->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with([])->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetchColumn')->willReturn('42');

        $result = $this->repository->countAll();
        $this->assertSame(42, $result);
    }

    // ==================== isConversationAdmin ====================

    public function testIsConversationAdmin_ReturnsTrue_WhenCreatorMatches(): void
    {
        $row = ['created_by' => 100];
        $this->pdoMock->expects($this->once())->method('prepare')->with(
            $this->stringContains('SELECT created_by FROM conversations WHERE id = ? AND deleted_at IS NULL')
        )->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with([50])->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($row);

        $result = $this->repository->isConversationAdmin('50', '100');
        $this->assertTrue($result);
    }

    public function testIsConversationAdmin_ReturnsFalse_WhenCreatorMismatch(): void
    {
        $row = ['created_by' => 100];
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with([50])->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($row);

        $result = $this->repository->isConversationAdmin('50', '200');
        $this->assertFalse($result);
    }

    public function testIsConversationAdmin_ReturnsFalse_WhenNotFound(): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with([50])->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false);

        $result = $this->repository->isConversationAdmin('50', '100');
        $this->assertFalse($result);
    }

    // ==================== searchByTitle ====================

    public function testSearchByTitle_ReturnsEmptyArray_WhenNoMatch(): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with(
            $this->stringContains('SELECT * FROM conversations')
        )->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with(
            $this->callback(function($args) {
                $this->assertSame('%999%', $args[0]);
                $this->assertSame('%hello%', $args[1]);
                return true;
            })
        )->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]);

        $result = $this->repository->searchByTitle('999', 'hello');
        $this->assertEmpty($result);
    }

    public function testSearchByTitle_ReturnsMatches(): void
    {
        $rows = [
            [
                'id' => 7,
                'user_ids' => json_encode([999]),
                'type' => 'group',
                'title' => 'Hello World',
                'created_by' => 999,
                'avatar' => null,
                'last_message_id' => 3,
            ],
        ];
        $this->pdoMock->expects($this->once())->method('prepare')->with(
            $this->stringContains('SELECT * FROM conversations')
        )->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with(
            $this->callback(function($args) {
                $this->assertSame('%999%', $args[0]);
                $this->assertSame('%hello%', $args[1]);
                return true;
            })
        )->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($rows);

        $result = $this->repository->searchByTitle('999', 'hello');
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Conversation::class, $result[0]);
    }
}
