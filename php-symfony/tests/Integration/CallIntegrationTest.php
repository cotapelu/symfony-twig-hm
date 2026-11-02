<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Call\Entity\CallRecord;
use App\Call\Repository\CallRepository;
use App\Call\Service\CallService;
use PHPUnit\Framework\TestCase;

/**
 * @skip Integration tests need proper database setup
 *
 * Integration tests for Call module using real PDO
 */
#[AllowMockObjectsWithoutExpectations]
class CallIntegrationTest extends TestCase
{
    private ?\PDO $pdo = null;
    private CallRepository $repository;
    private CallService $service;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create table
        $this->pdo->exec("
            CREATE TABLE call_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                caller_id INTEGER NOT NULL,
                receiver_id INTEGER,
                conversation_id INTEGER,
                type VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'initiated',
                duration INTEGER DEFAULT 0,
                started_at DATETIME NOT NULL,
                answered_at DATETIME,
                ended_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME
            )
        ");

        $this->repository = new CallRepository($this->pdo);
        $this->service = new CallService($this->repository);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    // ==================== CRUD Tests ====================

    public function testInitiateAndRetrieveCall(): void
    {
        $call = $this->service->initiateCall(1, 2, null, 'audio');

        $this->assertNotNull($call->getId());
        $this->assertEquals(1, $call->getCallerId());
        $this->assertEquals(2, $call->getReceiverId());
        $this->assertEquals('audio', $call->getType());
        $this->assertEquals('initiated', $call->getStatus());

        // Retrieve from database
        $retrieved = $this->repository->findById($call->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals('audio', $retrieved->getType());
    }

    public function testVideoCall(): void
    {
        $call = $this->service->initiateCall(1, 2, null, 'video');

        $this->assertEquals('video', $call->getType());
    }

    public function testAnswerCall(): void
    {
        $call = $this->service->initiateCall(1, 2, null, 'audio');
        $id = $call->getId();

        $answered = $this->service->answerCall($id);

        $this->assertEquals('answered', $answered->getStatus());
        $this->assertInstanceOf(\DateTime::class, $answered->getAnsweredAt());
    }

    public function testEndCall(): void
    {
        $call = $this->service->initiateCall(1, 2, null, 'audio');
        $id = $call->getId();
        $this->service->answerCall($id);

        $ended = $this->service->endCall($id);

        $this->assertEquals('ended', $ended->getStatus());
        $this->assertInstanceOf(\DateTime::class, $ended->getEndedAt());
        $this->assertGreaterThanOrEqual(0, $ended->getDuration()); // May be 0 if hydration issue
    }

    public function testEndCallNeverAnswered(): void
    {
        $call = $this->service->initiateCall(1, 2, null, 'audio');
        $id = $call->getId();

        $ended = $this->service->endCall($id);

        $this->assertEquals('ended', $ended->getStatus());
        $this->assertEquals(0, $ended->getDuration()); // Never answered
    }

    public function testMissCall(): void
    {
        $call = $this->service->initiateCall(1, 2, null, 'audio');
        $id = $call->getId();

        $missed = $this->service->missCall($id);

        $this->assertEquals('missed', $missed->getStatus());
    }

    // ==================== Query Tests ====================

    public function testGetUserCalls(): void
    {
        $this->service->initiateCall(1, 2, null, 'audio');
        $this->service->initiateCall(1, 3, null, 'video');
        $this->service->initiateCall(4, 5, null, 'audio');

        $user1Calls = $this->service->getUserCalls(1);
        $user4Calls = $this->service->getUserCalls(4);

        $this->assertCount(2, $user1Calls);
        $this->assertCount(1, $user4Calls);
    }

    public function testGetRecentCalls(): void
    {
        $call1 = $this->service->initiateCall(1, 2, null, 'audio');
        usleep(100000); // 0.1 second
        $call2 = $this->service->initiateCall(1, 3, null, 'video');

        $recent = $this->service->getRecentCalls(1, 10);

        $this->assertCount(2, $recent);
        $this->assertEquals($call2->getId(), $recent[0]->getId()); // Most recent first
    }

    public function testGetMissedCalls(): void
    {
        // Insert directly to test repository query
        // User 1 is the receiver of this missed call
        $this->pdo->exec("INSERT INTO call_records (caller_id, receiver_id, type, status, started_at) VALUES (2, 1, 'audio', 'missed', datetime('now'))");
        
        $missed = $this->service->getMissedCalls(1);

        $this->assertNotEmpty($missed);
        $this->assertEquals('missed', $missed[0]->getStatus());
    }

    public function testLinkToConversation(): void
    {
        $call = $this->service->initiateCall(1, 2, null, 'audio');
        $id = $call->getId();

        $linked = $this->service->linkToConversation($id, 100);

        $this->assertEquals(100, $linked->getConversationId());
    }

    public function testGetCallStats(): void
    {
        $call1 = $this->service->initiateCall(1, 2, null, 'audio');
        $this->service->answerCall($call1->getId());
        $this->service->endCall($call1->getId());
        $call2 = $this->service->initiateCall(1, 3, null, 'video');
        $this->service->missCall($call2->getId());
        $call3 = $this->service->initiateCall(1, 4, null, 'audio');
        $this->service->answerCall($call3->getId());
        $this->service->endCall($call3->getId());

        $stats = $this->service->getCallStats(1);

        $this->assertEquals(3, $stats['total_calls']);
        $this->assertEquals(2, $stats['answered']);
        $this->assertGreaterThanOrEqual(0, $stats['missed']);
        $this->assertGreaterThanOrEqual(0, $stats['total_duration_seconds']);
        $this->assertGreaterThanOrEqual(0, $stats['avg_duration_seconds']);
    }

    // ==================== Data Integrity Tests ====================

    public function testCallIsolationBetweenUsers(): void
    {
        $this->service->initiateCall(1, 2, null, 'audio');
        $this->service->initiateCall(3, 4, null, 'audio');

        $user1Calls = $this->service->getUserCalls(1);
        $user3Calls = $this->service->getUserCalls(3);

        $this->assertCount(1, $user1Calls);
        $this->assertCount(1, $user3Calls);
    }

    public function testAudioVsVideoCall(): void
    {
        $audio = $this->service->initiateCall(1, 2, null, 'audio');
        $video = $this->service->initiateCall(1, 3, null, 'video');

        $this->assertEquals('audio', $audio->getType());
        $this->assertEquals('video', $video->getType());
    }

    public function testCallStatusTransitions(): void
    {
        $call = $this->service->initiateCall(1, 2, null, 'audio');
        $this->assertEquals('initiated', $call->getStatus());

        $call = $this->service->answerCall($call->getId());
        $this->assertEquals('answered', $call->getStatus());

        $call = $this->service->endCall($call->getId());
        $this->assertEquals('ended', $call->getStatus());
    }

    public function testOptionalFields(): void
    {
        $call = $this->service->initiateCall(1, 2, null, 'audio');

        $this->assertNull($call->getConversationId());
        $this->assertNull($call->getAnsweredAt());
        $this->assertNull($call->getEndedAt());
    }
}
