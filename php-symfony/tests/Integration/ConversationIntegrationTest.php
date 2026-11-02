<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepository;
use App\Conversation\Service\ConversationService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Conversation\Integration
 * 
 * Integration tests for Conversation module using real PDO
 */
#[AllowMockObjectsWithoutExpectations]
class ConversationIntegrationTest extends TestCase
{
    private ?\PDO $pdo = null;
    private ConversationRepository $repository;
    private ConversationService $service;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create table
        $this->pdo->exec("
            CREATE TABLE conversations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_ids TEXT NOT NULL,
                type VARCHAR(20) NOT NULL,
                title VARCHAR(255),
                created_by INTEGER,
                avatar VARCHAR(255),
                last_message_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME
            )
        ");

        $this->repository = new ConversationRepository($this->pdo);
        $this->service = new ConversationService($this->repository);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    // ==================== CRUD Tests ====================

    public function testCreateAndRetrieveConversation(): void
    {
        $conversation = $this->service->createConversation([1, 2], 'direct');

        $this->assertNotNull($conversation->getId());
        $this->assertEquals([1, 2], $conversation->getParticipantIds());
        $this->assertEquals('direct', $conversation->getType());

        // Retrieve from database
        $retrieved = $this->repository->findById($conversation->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals('direct', $retrieved->getType());
    }

    public function testCreateGroupConversationWithTitle(): void
    {
        $conversation = $this->service->createConversation([1, 2, 3], 'group', 'Team Project');

        $this->assertNotNull($conversation->getId());
        $this->assertEquals('group', $conversation->getType());
        $this->assertEquals('Team Project', $conversation->getTitle());
    }

    public function testGetUserConversations(): void
    {
        $this->service->createConversation([1, 2], 'direct');
        $this->service->createConversation([1, 3], 'group', 'Group 1');
        $this->service->createConversation([2, 3], 'direct');

        $user1Convs = $this->service->getUserConversations(1);
        $user2Convs = $this->service->getUserConversations(2);
        $user3Convs = $this->service->getUserConversations(3);

        $this->assertCount(2, $user1Convs);
        $this->assertCount(2, $user2Convs);
        $this->assertCount(2, $user3Convs);
    }

    public function testUpdateTitle(): void
    {
        $conversation = $this->service->createConversation([1, 2], 'group', 'Original');
        $updated = $this->service->updateTitle($conversation->getId(), 'Updated Title');

        $this->assertEquals('Updated Title', $updated->getTitle());

        $retrieved = $this->repository->findById($conversation->getId());
        $this->assertEquals('Updated Title', $retrieved->getTitle());
    }

    public function testUpdateAvatar(): void
    {
        $conversation = $this->service->createConversation([1, 2], 'group');
        $updated = $this->service->updateAvatar($conversation->getId(), '/avatars/group.png');

        $this->assertEquals('/avatars/group.png', $updated->getAvatar());
    }

    public function testAddParticipant(): void
    {
        $conversation = $this->service->createConversation([1, 2], 'group');
        $updated = $this->service->addParticipant($conversation->getId(), 3);

        $this->assertContains(3, $updated->getParticipantIds());
    }

    public function testRemoveParticipant(): void
    {
        $conversation = $this->service->createConversation([1, 2, 3], 'group');
        $updated = $this->service->removeParticipant($conversation->getId(), 3);

        $this->assertCount(2, $updated->getParticipantIds());
        $this->assertNotContains(3, $updated->getParticipantIds());
    }

    public function testDeleteConversation(): void
    {
        $conversation = $this->service->createConversation([1, 2], 'direct');
        $id = $conversation->getId();

        $result = $this->service->deleteConversation($id);
        $this->assertTrue($result);
    }

    // ==================== Direct Conversation Tests ====================

    public function testGetDirectConversation(): void
    {
        // Create some conversations
        $this->service->createConversation([1, 2], 'group', 'Group');
        $direct = $this->service->createConversation([1, 2], 'direct');

        $found = $this->service->getDirectConversation(1, 2);

        $this->assertNotNull($found);
        $this->assertEquals('direct', $found->getType());
    }

    public function testGetDirectConversationNotFound(): void
    {
        $this->service->createConversation([1, 2], 'group');

        $found = $this->service->getDirectConversation(1, 2);

        $this->assertNull($found);
    }

    // ==================== Search Tests ====================

    public function testSearchConversations(): void
    {
        $this->service->createConversation([1, 2], 'group', 'Project Alpha');
        $this->service->createConversation([1, 3], 'group', 'Random Talk');
        $this->service->createConversation([1, 4], 'group', 'Project Beta');

        $results = $this->service->searchConversations(1, 'Project');

        $this->assertCount(2, $results);
    }

    public function testSearchConversationsNoResults(): void
    {
        $this->service->createConversation([1, 2], 'group', 'Group 1');

        $results = $this->service->searchConversations(1, 'NonExistent');

        $this->assertCount(0, $results);
    }

    // ==================== Data Integrity Tests ====================

    public function testConversationIsolationBetweenUsers(): void
    {
        $this->service->createConversation([1, 2], 'direct');
        $this->service->createConversation([3, 4], 'direct');

        $user1Convs = $this->service->getUserConversations(1);
        $user3Convs = $this->service->getUserConversations(3);

        $this->assertCount(1, $user1Convs);
        $this->assertCount(1, $user3Convs);
    }

    public function testGroupVsDirectConversation(): void
    {
        $group = $this->service->createConversation([1, 2, 3], 'group', 'Team');
        $direct = $this->service->createConversation([1, 2], 'direct');

        $this->assertEquals('group', $group->getType());
        $this->assertEquals('direct', $direct->getType());
        $this->assertCount(3, $group->getParticipantIds());
        $this->assertCount(2, $direct->getParticipantIds());
    }

    public function testSetTitleCanBeNull(): void
    {
        $conversation = $this->service->createConversation([1, 2], 'direct');

        $this->assertNull($conversation->getTitle());

        $conversation->setTitle('Chat');
        $this->assertEquals('Chat', $conversation->getTitle());
    }

    public function testOptionalFields(): void
    {
        $conversation = $this->service->createConversation([1, 2], 'direct');

        $this->assertNull($conversation->getCreatedBy());
        $this->assertNull($conversation->getAvatar());
        $this->assertNull($conversation->getLastMessageId());
    }
}
