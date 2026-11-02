<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\WebRTCSignalingService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]

class WebRTCSignalingServiceTest extends TestCase
{
    private WebRTCSignalingService $service;

    protected function setUp(): void
    {
        $this->service = new WebRTCSignalingService();
    }

    public function testCreateRoom(): void
    {
        $result = $this->service->createRoom('room1', 'user1');

        $this->assertSame('room1', $result['roomId']);
        $this->assertSame(['user1'], $result['participants']);
        // Verify internal structure
        $room = $this->getPrivateRoom('room1');
        $this->assertNotNull($room);
        $this->assertArrayHasKey('participants', $room);
        $this->assertArrayHasKey('offers', $room);
        $this->assertArrayHasKey('answers', $room);
        $this->assertArrayHasKey('candidates', $room);
        $this->assertArrayHasKey('created_at', $room);
        $this->assertSame(['user1' => ['joined_at' => $room['participants']['user1']['joined_at'], 'status' => 'connected']], $room['participants']);
    }

    public function testCreateRoomAddsParticipantToExistingRoom(): void
    {
        $this->service->createRoom('room1', 'user1');
        $this->service->createRoom('room1', 'user2');

        $result = $this->service->createRoom('room1', 'user3'); // returns room with all participants?
        // createRoom returns only roomId and participants list (all participants)
        $this->assertSame('room1', $result['roomId']);
        $this->assertSame(['user1','user2','user3'], $result['participants']);
    }

    public function testJoinRoomReturnsNullWhenRoomNotFound(): void
    {
        $this->assertNull($this->service->joinRoom('unknown', 'user1'));
    }

    public function testJoinRoomAddsParticipantAndReturnsOffers(): void
    {
        $this->service->createRoom('room1', 'user1');
        $this->service->sendOffer('room1', 'user1', ['sdp' => 'offer1']);

        $result = $this->service->joinRoom('room1', 'user2');

        $this->assertSame('room1', $result['roomId']);
        $this->assertSame(['user1','user2'], $result['participants']);
        $this->assertArrayHasKey('user1', $result['offers']);
        // offers are stored as ['offer'=>..., 'timestamp'=>...]
        $this->assertSame(['sdp' => 'offer1'], $result['offers']['user1']['offer']);
    }

    public function testSendOfferDoesNothingWhenRoomNotFound(): void
    {
        $this->service->sendOffer('unknown', 'user1', ['sdp' => 'o']);
        // Should not throw
        $this->assertTrue(true);
    }

    public function testSendOfferStoresOffer(): void
    {
        $this->service->createRoom('room1', 'user1');
        $this->service->sendOffer('room1', 'user1', ['sdp' => 'offer1']);

        $room = $this->getPrivateRoom('room1');
        $this->assertArrayHasKey('user1', $room['offers']);
        $this->assertSame(['sdp' => 'offer1'], $room['offers']['user1']['offer']);
        $this->assertArrayHasKey('timestamp', $room['offers']['user1']);
    }

    public function testSendAnswerStoresAnswer(): void
    {
        $this->service->createRoom('room1', 'user1');
        $this->service->sendAnswer('room1', 'user1', ['sdp' => 'answer1']);

        $room = $this->getPrivateRoom('room1');
        $this->assertArrayHasKey('user1', $room['answers']);
        $this->assertSame(['sdp' => 'answer1'], $room['answers']['user1']['answer']);
        $this->assertArrayHasKey('timestamp', $room['answers']['user1']);
    }

    public function testSendCandidateStoresCandidate(): void
    {
        $this->service->createRoom('room1', 'user1');
        $this->service->sendCandidate('room1', 'user1', ['cand' => 'c1']);

        $room = $this->getPrivateRoom('room1');
        $this->assertArrayHasKey('user1', $room['candidates']);
        $this->assertCount(1, $room['candidates']['user1']);
        $this->assertSame(['cand' => 'c1'], $room['candidates']['user1'][0]['candidate']);
        $this->assertArrayHasKey('timestamp', $room['candidates']['user1'][0]);

        // Send second candidate
        $this->service->sendCandidate('room1', 'user1', ['cand' => 'c2']);
        $room = $this->getPrivateRoom('room1');
        $this->assertCount(2, $room['candidates']['user1']);
    }

    public function testGetRoomDataReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->service->getRoomData('unknown'));
    }

    public function testGetRoomDataReturnsFullRoom(): void
    {
        $this->service->createRoom('room1', 'user1');
        $this->service->sendOffer('room1', 'user1', ['sdp' => 'o']);
        $room = $this->service->getRoomData('room1');
        $this->assertArrayHasKey('participants', $room);
        $this->assertArrayHasKey('offers', $room);
        $this->assertArrayHasKey('answers', $room);
        $this->assertArrayHasKey('candidates', $room);
        $this->assertArrayHasKey('created_at', $room);
    }

    public function testLeaveRoomRemovesParticipant(): void
    {
        $this->service->createRoom('room1', 'user1');
        $this->service->createRoom('room1', 'user2');
        $this->service->leaveRoom('room1', 'user1');

        $room = $this->getPrivateRoom('room1');
        $this->assertArrayNotHasKey('user1', $room['participants']);
        $this->assertArrayHasKey('user2', $room['participants']);
    }

    public function testLeaveRoomCleansEmptyRoom(): void
    {
        $this->service->createRoom('room1', 'user1');
        $this->service->leaveRoom('room1', 'user1');

        $this->assertNull($this->getPrivateRoom('room1'));
    }

    public function testLeaveRoomNonExistentParticipantNoOp(): void
    {
        $this->service->createRoom('room1', 'user1');
        $this->service->leaveRoom('room1', 'user2'); // user2 not in room

        $room = $this->getPrivateRoom('room1');
        $this->assertArrayHasKey('user1', $room['participants']);
    }

    public function testMultipleOffersFromDifferentUsers(): void
    {
        $this->service->createRoom('room1', 'user1');
        $this->service->createRoom('room1', 'user2');
        $this->service->sendOffer('room1', 'user1', ['sdp' => 'o1']);
        $this->service->sendOffer('room1', 'user2', ['sdp' => 'o2']);

        $room = $this->getPrivateRoom('room1');
        $this->assertArrayHasKey('user1', $room['offers']);
        $this->assertArrayHasKey('user2', $room['offers']);
        $this->assertSame(['sdp' => 'o1'], $room['offers']['user1']['offer']);
        $this->assertSame(['sdp' => 'o2'], $room['offers']['user2']['offer']);
    }

    public function testMultipleAnswersAndCandidates(): void
    {
        $this->service->createRoom('room1', 'u1');
        $this->service->createRoom('room1', 'u2');
        $this->service->sendAnswer('room1', 'u1', ['sdp' => 'a1']);
        $this->service->sendAnswer('room1', 'u2', ['sdp' => 'a2']);
        $this->service->sendCandidate('room1', 'u1', ['c' => 'c1']);
        $this->service->sendCandidate('room1', 'u1', ['c' => 'c2']);

        $room = $this->getPrivateRoom('room1');
        $this->assertArrayHasKey('u1', $room['answers']);
        $this->assertArrayHasKey('u2', $room['answers']);
        $this->assertCount(2, $room['candidates']['u1']);
    }

    public function testRoomDataIsolationBetweenRooms(): void
    {
        $this->service->createRoom('room1', 'u1');
        $this->service->createRoom('room2', 'u2');
        $this->service->sendOffer('room1', 'u1', ['sdp' => 'o1']);
        $this->service->sendOffer('room2', 'u2', ['sdp' => 'o2']);

        $room1 = $this->service->getRoomData('room1');
        $room2 = $this->service->getRoomData('room2');
        $this->assertArrayHasKey('u1', $room1['offers']);
        $this->assertArrayNotHasKey('u2', $room1['offers']);
        $this->assertArrayHasKey('u2', $room2['offers']);
        $this->assertArrayNotHasKey('u1', $room2['offers']);
    }

    public function testCreateRoomWithSameIdPreservesExistingParticipants(): void
    {
        $this->service->createRoom('room1', 'u1');
        $this->service->createRoom('room1', 'u2');
        // Calling createRoom again for same room with existing user should not duplicate
        $result = $this->service->createRoom('room1', 'u2');

        $this->assertSame(['u1','u2'], $result['participants']);
        $room = $this->getPrivateRoom('room1');
        $this->assertCount(2, $room['participants']);
    }

    public function testJoinRoomParticipantAlreadyJoined(): void
    {
        $this->service->createRoom('room1', 'u1');
        $first = $this->service->joinRoom('room1', 'u2');
        $second = $this->service->joinRoom('room1', 'u2'); // join again

        $this->assertSame(['u1','u2'], $first['participants']);
        $this->assertSame(['u1','u2'], $second['participants']);
        $room = $this->getPrivateRoom('room1');
        $this->assertCount(2, $room['participants']);
    }

    public function testOfferAnswerCandidateSequence(): void
    {
        $this->service->createRoom('room1', 'u1');
        $this->service->createRoom('room1', 'u2');
        $this->service->sendOffer('room1', 'u1', ['sdp' => 'offer1']);
        $this->service->sendAnswer('room1', 'u2', ['sdp' => 'answer1']);
        $this->service->sendCandidate('room1', 'u2', ['c' => 'c1']);

        $room = $this->getPrivateRoom('room1');
        $this->assertSame(['sdp' => 'offer1'], $room['offers']['u1']['offer']);
        $this->assertSame(['sdp' => 'answer1'], $room['answers']['u2']['answer']);
        $this->assertCount(1, $room['candidates']['u2']);
    }

    public function testTimestampsAreSet(): void
    {
        $before = time();
        $this->service->createRoom('room1', 'u1');
        $this->service->sendOffer('room1', 'u1', ['sdp' => 'o']);
        $after = time();

        $room = $this->getPrivateRoom('room1');
        $this->assertGreaterThanOrEqual($before, $room['created_at']);
        $this->assertLessThanOrEqual($after, $room['created_at']);
        $this->assertArrayHasKey('joined_at', $room['participants']['u1']);
        $this->assertArrayHasKey('timestamp', $room['offers']['u1']);
    }

    private function getPrivateRoom(string $roomId): ?array
    {
        $ref = new \ReflectionProperty(WebRTCSignalingService::class, 'rooms');
        $ref->setAccessible(true);
        $rooms = $ref->getValue($this->service);
        return $rooms[$roomId] ?? null;
    }
}
