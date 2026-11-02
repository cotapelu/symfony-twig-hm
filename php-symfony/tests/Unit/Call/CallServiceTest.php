<?php declare(strict_types=1);

namespace App\Tests\Unit\Call;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Call\Entity\CallRecord;
use App\Call\Repository\CallRepositoryInterface;
use App\Call\Service\CallService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CallServiceTest extends TestCase
{
    private CallRepositoryInterface $repository;
    private CallService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CallRepositoryInterface::class);
        $this->service = new CallService($this->repository);
    }

    public function testInitiateCallCreatesRecord(): void
    {
        $callerId = 1;
        $receiverId = 2;
        $type = 'audio';

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (CallRecord $call) use ($callerId, $receiverId, $type) {
                $this->assertEquals($callerId, $call->getCallerId());
                $this->assertEquals($receiverId, $call->getReceiverId());
                $this->assertEquals($type, $call->getType());
                $this->assertEquals('initiated', $call->getStatus());
                return $call;
            });

        $call = $this->service->initiateCall($callerId, $receiverId, null, $type);

        $this->assertInstanceOf(CallRecord::class, $call);
        $this->assertEquals($callerId, $call->getCallerId());
        $this->assertEquals($receiverId, $call->getReceiverId());
        $this->assertEquals($type, $call->getType());
    }

    public function testInitiateCallWithGroupSetsNullReceiver(): void
    {
        $callerId = 1;
        $groupId = 100;

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (CallRecord $call) use ($callerId) {
                $this->assertEquals($callerId, $call->getCallerId());
                $this->assertNull($call->getReceiverId());
                return $call;
            });

        $call = $this->service->initiateCall($callerId, null, $groupId, 'video');

        $this->assertInstanceOf(CallRecord::class, $call);
        $this->assertNull($call->getReceiverId());
    }

    public function testInitiateCallThrowsIfNoReceiverOrGroup(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either receiver or group must be provided');
        $this->service->initiateCall(1, null, null, 'audio');
    }

    public function testAnswerCallChangesStatus(): void
    {
        $callId = 123;
        $call = new CallRecord(1, 2, 'audio');
        $call->setId($callId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($callId)
            ->willReturn($call);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($call)
            ->willReturn($call);

        $result = $this->service->answerCall($callId);

        $this->assertEquals('answered', $result->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $result->getAnsweredAt());
    }

    public function testAnswerCallReturnsNullIfNotFound(): void
    {
        $callId = 999;
        $this->repository->expects($this->once())
            ->method('findById')
            ->with($callId)
            ->willReturn(null);

        $result = $this->service->answerCall($callId);

        $this->assertNull($result);
    }

    public function testEndCallCompletesCall(): void
    {
        $callId = 123;
        $call = new CallRecord(1, 2, 'audio');
        $call->setId($callId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($callId)
            ->willReturn($call);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($call)
            ->willReturn($call);

        $result = $this->service->endCall($callId);

        $this->assertEquals('ended', $result->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $result->getEndedAt());
    }

    public function testMissCallSetsMissedStatus(): void
    {
        $callId = 123;
        $call = new CallRecord(1, 2, 'audio');
        $call->setId($callId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($callId)
            ->willReturn($call);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($call)
            ->willReturn($call);

        $result = $this->service->missCall($callId);

        $this->assertEquals('missed', $result->getStatus());
    }

    public function testGetUserCallsFiltersByUser(): void
    {
        $userId = 1;
        $calls = [
            new CallRecord(1, 2, 'audio'),
            new CallRecord(1, 3, 'video'),
        ];

        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($calls);

        $result = $this->service->getUserCalls($userId);

        $this->assertCount(2, $result);
        $this->assertSame($calls[0], $result[0]);
        $this->assertSame($calls[1], $result[1]);
    }

    public function testGetRecentCallsOrdersByTime(): void
    {
        $userId = 1;
        $now = new \DateTime();

        $call1 = new CallRecord(2, $userId, 'audio');
        $ref1 = new \ReflectionProperty($call1, 'startedAt');
        $ref1->setAccessible(true);
        $ref1->setValue($call1, (clone $now)->modify('-1 hour')); // earlier

        $call2 = new CallRecord(3, $userId, 'video');
        $ref2 = new \ReflectionProperty($call2, 'startedAt');
        $ref2->setAccessible(true);
        $ref2->setValue($call2, $now); // later

        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([$call1, $call2]);

        $result = $this->service->getRecentCalls($userId, 2);

        $this->assertCount(2, $result);
        $this->assertSame($call2, $result[0]); // most recent first
        $this->assertSame($call1, $result[1]);
    }

    public function testGetMissedCallsFiltersByStatus(): void
    {
        $userId = 1;

        $missedCall = new CallRecord(2, $userId, 'audio');
        $missedCall->markMissed();

        $this->repository->expects($this->once())
            ->method('findMissedByUserId')
            ->with($userId)
            ->willReturn([$missedCall]);

        $result = $this->service->getMissedCalls($userId);

        $this->assertCount(1, $result);
        $this->assertEquals('missed', $result[0]->getStatus());
    }

    public function testGetCallStatsComputesMetrics(): void
    {
        $userId = 1;

        // Create two ended calls and one missed
        $call1 = new CallRecord(1, 2, 'audio');
        $call1->setAnswered();
        $call1->end();
        $ref1 = new \ReflectionProperty($call1, 'duration');
        $ref1->setAccessible(true);
        $ref1->setValue($call1, 30);

        $call2 = new CallRecord(2, 3, 'video');
        $call2->setAnswered();
        $call2->end();
        $ref2 = new \ReflectionProperty($call2, 'duration');
        $ref2->setAccessible(true);
        $ref2->setValue($call2, 60);

        $call3 = new CallRecord(3, 4, 'audio');
        $call3->markMissed();

        $calls = [$call1, $call2, $call3];

        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($calls);

        $stats = $this->service->getCallStats($userId);

        $this->assertEquals(3, $stats['total_calls']);
        $this->assertEquals(2, $stats['answered']);
        $this->assertEquals(1, $stats['missed']);
        $this->assertEquals(90, $stats['total_duration_seconds']);
        $this->assertEquals(30, $stats['avg_duration_seconds']);
    }

    public function testLinkToConversationUpdatesCall(): void
    {
        $callId = 123;
        $conversationId = 100;
        $call = new CallRecord(1, 2, 'audio');
        $call->setId($callId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($callId)
            ->willReturn($call);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($call)
            ->willReturn($call);

        $result = $this->service->linkToConversation($callId, $conversationId);

        $this->assertEquals($conversationId, $result->getConversationId());
    }
}
