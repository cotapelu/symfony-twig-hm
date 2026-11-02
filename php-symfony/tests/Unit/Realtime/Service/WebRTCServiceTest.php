<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\WebRTCService;
use App\Realtime\Service\CallQualityService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]

class WebRTCServiceTest extends TestCase
{
    private CallQualityService&MockObject $callQualityMock;
    private WebRTCService $service;

    protected function setUp(): void
    {
        $this->callQualityMock = $this->createMock(CallQualityService::class);
        $this->service = new WebRTCService($this->callQualityMock);
    }

    public function testCreateCallValid(): void
    {
        $callId = 'call_123';
        $callerId = 'user_1';
        $participants = ['user_2', 'user_3'];

        $this->callQualityMock->expects($this->once())
            ->method('startCallMonitoring')
            ->with($callId, $callerId, $participants);

        $call = $this->service->createCall($callId, $callerId, $participants, 'video');

        $this->assertSame($callId, $call['id']);
        $this->assertSame($callerId, $call['caller_id']);
        $this->assertSame($participants, $call['participants']);
        $this->assertSame('video', $call['call_type']);
        $this->assertSame('ringing', $call['status']);
        $this->assertArrayHasKey('ice_servers', $call);
        $this->assertCount(count($participants), $call['participants_status']);
        foreach ($participants as $p) {
            $this->assertSame('invited', $call['participants_status'][$p]['status']);
            $this->assertNull($call['participants_status'][$p]['joined_at']);
            $this->assertTrue($call['participants_status'][$p]['audio_enabled']);
            $this->assertTrue($call['participants_status'][$p]['video_enabled']);
            $this->assertFalse($call['participants_status'][$p]['screen_sharing']);
        }
    }

    public function testCreateCallInvalidCallType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid call type');
        $this->service->createCall('c', 'u', ['v'], 'invalid');
    }

    public function testCreateCallEmptyParticipants(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one participant');
        $this->service->createCall('c', 'u', [], 'voice');
    }

    public function testCreateCallNonStringParticipant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Participant IDs must be non-empty strings');
        $this->service->createCall('c', 'u', [123], 'voice');
    }

    public function testCreateCallEmptyStringParticipant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Participant IDs must be non-empty strings');
        $this->service->createCall('c', 'u', [''], 'voice');
    }

    public function testJoinCallValid(): void
    {
        $callId = 'call_1';
        $this->service->createCall($callId, 'user_1', ['user_2', 'user_3'], 'voice');

        // First join: status remains ringing (only 1 joined)
        $this->service->joinCall($callId, 'user_2');
        $call = $this->service->getCall($callId);
        $this->assertSame('ringing', $call['status']);
        $this->assertSame('joined', $call['participants_status']['user_2']['status']);

        // Second join: activates call
        $this->service->joinCall($callId, 'user_3');
        $call = $this->service->getCall($callId);
        $this->assertSame('active', $call['status']);
        $this->assertSame('joined', $call['participants_status']['user_3']['status']);
    }

    public function testJoinCallNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Call not found or has ended');
        $this->service->joinCall('unknown', 'user_1');
    }

    public function testJoinCallParticipantNotInvited(): void
    {
        $callId = 'call_1';
        $this->service->createCall($callId, 'user_1', ['user_2']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Participant not invited to this call');
        $this->service->joinCall($callId, 'user_3');
    }

    public function testJoinCallAlreadyJoined(): void
    {
        $callId = 'call_1';
        $this->service->createCall($callId, 'user_1', ['user_2']);
        $this->service->joinCall($callId, 'user_2');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Participant already joined this call');
        $this->service->joinCall($callId, 'user_2');
    }

    public function testLeaveCallEndsWhenInsufficientParticipants(): void
    {
        $callId = 'call_1';
        $this->service->createCall($callId, 'user_1', ['user_2']);
        $this->service->joinCall($callId, 'user_2');

        // Expect stopCallMonitoring to be called exactly once when call ends
        $this->callQualityMock->expects($this->once())
            ->method('stopCallMonitoring')
            ->with($callId);

        $result = $this->service->leaveCall($callId, 'user_2');

        $this->assertSame('left', $result['participants_status']['user_2']['status']);
        $this->assertArrayHasKey('left_at', $result['participants_status']['user_2']);
        $this->assertSame('ended', $result['status']);
        $this->assertArrayHasKey('end_time', $result);
    }

    public function testLeaveCallNotActive(): void
    {
        $result = $this->service->leaveCall('unknown', 'user_1');
        $this->assertSame(['status' => 'not_found'], $result);
    }

    public function testStoreOffer(): void
    {
        $callId = 'call_1';
        $from = 'user_1';
        $to = 'user_2';
        $offer = ['type' => 'offer', 'sdp' => '...'];

        $this->service->storeOffer($callId, $from, $to, $offer);

        $retrieved = $this->service->getOffer($callId, $from, $to);
        $this->assertNotNull($retrieved);
        $this->assertSame($offer, $retrieved['offer']);
        $this->assertSame($from, $retrieved['from']);
        $this->assertSame($to, $retrieved['to']);
        $this->assertArrayHasKey('timestamp', $retrieved);
    }

    public function testGetOfferNotFound(): void
    {
        $this->assertNull($this->service->getOffer('c', 'a', 'b'));
    }

    public function testStoreAnswerStoresWhenPendingOfferExists(): void
    {
        $callId = 'call_1';
        // Create a pending offer that will match the answer direction
        $this->service->storeOffer($callId, 'u2', 'u1', ['sdp' => 'offer']);

        $answer = ['type' => 'answer', 'sdp' => 'answer_sdp'];
        $this->service->storeAnswer($callId, 'u2', 'u1', $answer);

        $offerRecord = $this->service->getOffer($callId, 'u2', 'u1');
        $this->assertArrayHasKey('answer', $offerRecord);
        $this->assertSame($answer, $offerRecord['answer']);
        $this->assertArrayHasKey('answer_timestamp', $offerRecord);
    }

    public function testStoreAnswerDoesNothingWhenNoPendingOffer(): void
    {
        $this->service->storeAnswer('c', 'u2', 'u1', ['sdp' => 'a']);
        $this->assertNull($this->service->getOffer('c', 'u2', 'u1'));
    }

    public function testStoreIceCandidate(): void
    {
        $callId = 'call_1';
        $this->service->storeOffer($callId, 'u1', 'u2', ['sdp' => 'offer']);

        $candidate = ['candidate' => '...', 'sdpMid' => '0'];
        $this->service->storeIceCandidate($callId, 'u1', 'u2', $candidate);

        $offer = $this->service->getOffer($callId, 'u1', 'u2');
        $this->assertCount(1, $offer['ice_candidates']);
        $this->assertSame($candidate, $offer['ice_candidates'][0]);

        $this->service->storeIceCandidate($callId, 'u1', 'u2', ['cand' => '2']);
        $offer = $this->service->getOffer($callId, 'u1', 'u2');
        $this->assertCount(2, $offer['ice_candidates']);
    }

    public function testGetIceCandidatesEmptyWhenNoOffer(): void
    {
        $this->assertSame([], $this->service->getIceCandidates('c', 'u1', 'u2'));
    }

    public function testUpdateParticipantMedia(): void
    {
        $callId = 'call_1';
        $this->service->createCall($callId, 'user_1', ['user_2']);
        $this->service->joinCall($callId, 'user_2');

        $mediaStatus = ['audio_enabled' => false, 'video_enabled' => true];
        $this->service->updateParticipantMedia($callId, 'user_2', $mediaStatus);

        $call = $this->service->getCall($callId);
        $this->assertFalse($call['participants_status']['user_2']['audio_enabled']);
        $this->assertTrue($call['participants_status']['user_2']['video_enabled']);
    }

    public function testUpdateParticipantMediaNoOpWhenCallNotExists(): void
    {
        $this->service->updateParticipantMedia('nope', 'u', ['audio_enabled' => false]);
        $this->assertTrue(true);
    }

    public function testGetCall(): void
    {
        $callId = 'call_1';
        $call = $this->service->createCall($callId, 'user_1', ['user_2']);
        $this->assertSame($call, $this->service->getCall($callId));
        $this->assertNull($this->service->getCall('unknown'));
    }

    public function testGetActiveCallsFiltersInactive(): void
    {
        // c1: make active
        $this->service->createCall('c1', 'u1', ['u2', 'u3']);
        $this->service->joinCall('c1', 'u2');
        $this->service->joinCall('c1', 'u3'); // now active

        // c2: will be ended
        $this->service->createCall('c2', 'u1', ['u2']);
        $this->service->joinCall('c2', 'u2'); // ringing
        $this->service->leaveCall('c2', 'u2'); // ends

        $active = $this->service->getActiveCalls();
        $this->assertArrayHasKey('c1', $active);
        $this->assertArrayNotHasKey('c2', $active);
    }

    public function testGetActiveCallsOnlyActiveCalls(): void
    {
        $this->service->createCall('c1', 'u', ['p1', 'p2']);
        $this->service->joinCall('c1', 'p1');
        $this->service->joinCall('c1', 'p2'); // active

        $this->service->createCall('c2', 'u', ['p']);
        $this->service->joinCall('c2', 'p'); // ringing

        $active = $this->service->getActiveCalls();
        $this->assertArrayHasKey('c1', $active);
        $this->assertArrayNotHasKey('c2', $active);
    }

    public function testCleanupOldOffers(): void
    {
        $oldOfferTime = time() - 400; // older than 5 minutes
        $recentOfferTime = time() - 60;

        $ref = new \ReflectionProperty($this->service, 'pendingOffers');
        $ref->setAccessible(true);
        $offers = [
            'old_key' => ['offer' => ['sdp' => 'old'], 'timestamp' => $oldOfferTime],
            'new_key' => ['offer' => ['sdp' => 'new'], 'timestamp' => $recentOfferTime],
        ];
        $ref->setValue($this->service, $offers);

        $this->service->cleanupOldOffers();

        $updatedOffers = $ref->getValue($this->service);
        $this->assertArrayNotHasKey('old_key', $updatedOffers);
        $this->assertArrayHasKey('new_key', $updatedOffers);
    }

    public function testGetIceServersIncludesStunAndOptionalTurn(): void
    {
        // Ensure env vars are not set
        unset($_ENV['TURN_SERVER_URL'], $_ENV['TURN_USERNAME'], $_ENV['TURN_CREDENTIAL']);
        putenv('TURN_SERVER_URL');
        putenv('TURN_USERNAME');
        putenv('TURN_CREDENTIAL');

        $ref = new \ReflectionMethod($this->service, 'getIceServers');
        $ref->setAccessible(true);
        $servers = $ref->invoke($this->service);

        $this->assertCount(1, $servers);
        $this->assertSame('stun:stun.l.google.com:19302', $servers[0]['urls']);

        // Set TURN env vars
        $_ENV['TURN_SERVER_URL'] = 'turn:example.com';
        $_ENV['TURN_USERNAME'] = 'user';
        $_ENV['TURN_CREDENTIAL'] = 'pass';
        putenv('TURN_SERVER_URL=turn:example.com');
        putenv('TURN_USERNAME=user');
        putenv('TURN_CREDENTIAL=pass');

        $servers = $ref->invoke($this->service);
        $this->assertCount(2, $servers);
        $this->assertSame('turn:example.com', $servers[1]['urls']);
        $this->assertSame('user', $servers[1]['username']);
        $this->assertSame('pass', $servers[1]['credential']);

        // Cleanup
        unset($_ENV['TURN_SERVER_URL'], $_ENV['TURN_USERNAME'], $_ENV['TURN_CREDENTIAL']);
        putenv('TURN_SERVER_URL');
        putenv('TURN_USERNAME');
        putenv('TURN_CREDENTIAL');
    }

    public function testGetCallStatistics(): void
    {
        $callId = 'call_1';
        $this->service->createCall($callId, 'user_1', ['user_2', 'user_3']);
        $this->service->joinCall($callId, 'user_2');
        $this->service->joinCall($callId, 'user_3'); // now active (2 joined)

        $stats = $this->service->getCallStatistics($callId);

        $this->assertSame($callId, $stats['call_id']);
        $this->assertGreaterThanOrEqual(0, $stats['duration']);
        $this->assertSame(2, $stats['total_participants']); // invitees only
        $this->assertSame(2, $stats['active_participants']);
        $this->assertSame('active', $stats['status']);
    }

    public function testGetCallStatisticsNotFound(): void
    {
        $stats = $this->service->getCallStatistics('unknown');
        $this->assertSame(['error' => 'Call not found'], $stats);
    }

    public function testLeaveCallKeepsActiveWhenEnoughJoined(): void
    {
        $callId = 'call_1';
        $this->service->createCall($callId, 'user_1', ['user_2', 'user_3', 'user_4']);
        $this->service->joinCall($callId, 'user_2');
        $this->service->joinCall($callId, 'user_3');
        $this->service->joinCall($callId, 'user_4'); // now 3 joined, active

        // Leave one participant; still 2 joined, should stay active, no stopCallMonitoring
        $this->callQualityMock->expects($this->never())
            ->method('stopCallMonitoring');

        $result = $this->service->leaveCall($callId, 'user_2');

        $this->assertSame('active', $result['status']);
    }

    public function testCreateCallVoiceAudioOnly(): void
    {
        $call = $this->service->createCall('c', 'u', ['p'], 'voice');
        $this->assertFalse($call['participants_status']['p']['video_enabled']);
        $this->assertFalse($call['participants_status']['p']['screen_sharing']);
    }

    public function testCreateCallWithSingleParticipant(): void
    {
        $call = $this->service->createCall('c', 'u', ['p']);
        $this->assertArrayHasKey('p', $call['participants_status']);
    }

    public function testConstructorInjectsCallQualityService(): void
    {
        $ref = new \ReflectionProperty(WebRTCService::class, 'callQualityService');
        $ref->setAccessible(true);
        $service = new WebRTCService($this->callQualityMock);
        $this->assertSame($this->callQualityMock, $ref->getValue($service));
    }
}
