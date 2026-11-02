<?php declare(strict_types=1);

namespace App\Tests\Unit\Call\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Call\Entity\CallRecord;
use App\Call\Repository\CallRepositoryInterface;
use App\Call\Service\CallService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CallServiceTest extends TestCase
{
    private CallRepositoryInterface $repoMock;
    private CallService $service;

    protected function setUp(): void
    {
        $this->repoMock = $this->createMock(CallRepositoryInterface::class);
        $this->service = new CallService($this->repoMock);
    }

    private function createCall(?int $id = null, int $callerId = 1, ?int $receiverId = 2, string $type = 'audio', string $status = 'initiated', ?\DateTime $startedAt = null): CallRecord
    {
        $call = new CallRecord($callerId, $receiverId, $type);
        if ($status !== 'initiated') {
            // adjust status via methods if needed
            if ($status === 'answered') {
                $call->setAnswered();
            } elseif ($status === 'missed') {
                $call->markMissed();
            } elseif ($status === 'ended') {
                $call->end();
            }
        }
        if ($startedAt !== null) {
            // Change private startedAt via reflection
            $ref = new \ReflectionClass($call);
            $prop = $ref->getProperty('startedAt');
            $prop->setAccessible(true);
            $prop->setValue($call, $startedAt);
        }
        if ($id !== null) {
            $call->setId($id);
        }
        return $call;
    }

    // ==================== initiateCall ====================

    public function testInitiateCallWithReceiver(): void
    {
        $this->repoMock->method('save')->willReturnArgument(0);
        $result = $this->service->initiateCall(1, 2, null, 'audio');
        $this->assertInstanceOf(CallRecord::class, $result);
        $this->assertSame(1, $result->getCallerId());
        $this->assertSame(2, $result->getReceiverId());
        $this->assertSame('audio', $result->getType());
        $this->assertSame('initiated', $result->getStatus());
    }

    public function testInitiateCallWithGroup(): void
    {
        $this->repoMock->method('save')->willReturnArgument(0);
        $result = $this->service->initiateCall(1, null, 'group', 'video');
        $this->assertInstanceOf(CallRecord::class, $result);
        $this->assertSame(1, $result->getCallerId());
        $this->assertNull($result->getReceiverId());
        $this->assertSame('video', $result->getType());
    }

    public function testInitiateCallThrowsIfNeitherReceiverNorGroup(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->initiateCall(1, null, null);
    }

    // ==================== getCall ====================

    public function testGetCall(): void
    {
        $call = $this->createCall(id: 5);
        $this->repoMock->method('findById')->with(5)->willReturn($call);
        $result = $this->service->getCall(5);
        $this->assertSame($call, $result);
    }

    public function testGetCallReturnsNullIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(10)->willReturn(null);
        $result = $this->service->getCall(10);
        $this->assertNull($result);
    }

    // ==================== answerCall ====================

    public function testAnswerCallSuccess(): void
    {
        $call = $this->createCall(id: 1, status: 'initiated');
        $this->repoMock->method('findById')->with(1)->willReturn($call);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->answerCall(1);

        $this->assertSame($call, $result);
        $this->assertSame('answered', $result->getStatus());
        $this->assertNotNull($result->getAnsweredAt());
    }

    public function testAnswerCallReturnsNullIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(1)->willReturn(null);
        $result = $this->service->answerCall(1);
        $this->assertNull($result);
    }

    public function testAnswerCallDoesNothingIfNotInitiated(): void
    {
        $call = $this->createCall(id: 1, status: 'ended');
        $this->repoMock->method('findById')->with(1)->willReturn($call);
        $this->repoMock->expects($this->never())->method('save');
        $result = $this->service->answerCall(1);
        $this->assertSame($call, $result);
        $this->assertSame('ended', $result->getStatus());
    }

    // ==================== endCall ====================

    public function testEndCallSuccess(): void
    {
        $now = new \DateTime();
        $call = $this->createCall(id: 1, status: 'answered', startedAt: (clone $now)->modify('-1 minute'));
        $this->repoMock->method('findById')->with(1)->willReturn($call);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->endCall(1);

        $this->assertSame($call, $result);
        $this->assertSame('ended', $result->getStatus());
        $this->assertNotNull($result->getEndedAt());
        $this->assertGreaterThan(0, $result->getDuration());
    }

    public function testEndCallReturnsNullIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(1)->willReturn(null);
        $result = $this->service->endCall(1);
        $this->assertNull($result);
    }

    // ==================== missCall ====================

    public function testMissCallSuccess(): void
    {
        $call = $this->createCall(id: 1, status: 'initiated');
        $this->repoMock->method('findById')->with(1)->willReturn($call);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->missCall(1);

        $this->assertSame($call, $result);
        $this->assertSame('missed', $result->getStatus());
    }

    public function testMissCallReturnsNullIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(1)->willReturn(null);
        $result = $this->service->missCall(1);
        $this->assertNull($result);
    }

    // ==================== linkToConversation ====================

    public function testLinkToConversation(): void
    {
        $call = $this->createCall(id: 1);
        $this->repoMock->method('findById')->with(1)->willReturn($call);
        $this->repoMock->method('save')->willReturnArgument(0);
        $result = $this->service->linkToConversation(1, 100);
        $this->assertSame($call, $result);
        $this->assertSame(100, $result->getConversationId());
    }

    public function testLinkToConversationReturnsNullIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(1)->willReturn(null);
        $result = $this->service->linkToConversation(1, 100);
        $this->assertNull($result);
    }

    // ==================== getUserCalls ====================

    public function testGetUserCalls(): void
    {
        $calls = [$this->createCall(id: 1), $this->createCall(id: 2)];
        $this->repoMock->method('findByUserId')->with(5)->willReturn($calls);
        $result = $this->service->getUserCalls(5);
        $this->assertSame($calls, $result);
    }

    // ==================== getRecentCalls ====================

    public function testGetRecentCallsSorted(): void
    {
        $now = new \DateTime();
        $calls = [
            $this->createCall(id: 1, startedAt: (clone $now)->modify('-1 hour')),
            $this->createCall(id: 2, startedAt: (clone $now)->modify('-2 hour')),
            $this->createCall(id: 3, startedAt: (clone $now)->modify('-30 minutes')),
        ];
        $this->repoMock->method('findByUserId')->with(5)->willReturn($calls);
        $result = $this->service->getRecentCalls(5, 2);
        $this->assertCount(2, $result);
        // Most recent first: the 30 min ago then 1 hour ago
        $this->assertSame(3, $result[0]->getId());
        $this->assertSame(1, $result[1]->getId());
    }

    public function testGetRecentCallsRespectsLimit(): void
    {
        $now = new \DateTime();
        $calls = [
            $this->createCall(id: 1, startedAt: (clone $now)->modify('-1 hour')),
            $this->createCall(id: 2, startedAt: (clone $now)->modify('-2 hour')),
        ];
        $this->repoMock->method('findByUserId')->with(5)->willReturn($calls);
        $result = $this->service->getRecentCalls(5, 5);
        $this->assertCount(2, $result);
    }

    // ==================== getMissedCalls ====================

    public function testGetMissedCalls(): void
    {
        $missed = [$this->createCall(id: 1, status: 'missed'), $this->createCall(id: 2, status: 'missed')];
        $this->repoMock->method('findMissedByUserId')->with(5)->willReturn($missed);
        $result = $this->service->getMissedCalls(5);
        $this->assertSame($missed, $result);
    }

    // ==================== getUserCallsPaginated ====================

    public function testGetUserCallsPaginated(): void
    {
        $data = [$this->createCall(id: 1), $this->createCall(id: 2)];
        $this->repoMock->method('findByUserIdWithPagination')->with(5, 10, 0)->willReturn($data);
        $this->repoMock->method('countByUserId')->with(5)->willReturn(2);
        $result = $this->service->getUserCallsPaginated(5, 1, 10);
        $this->assertSame($data, $result['data']);
        $this->assertSame(2, $result['total']);
    }

    // ==================== getCallStats ====================

    public function testGetCallStats(): void
    {
        $now = new \DateTime();
        $calls = [
            $this->createCall(id: 1, status: 'ended', startedAt: (clone $now)->modify('-1 hour')),
            $this->createCall(id: 2, status: 'missed'),
            $this->createCall(id: 3, status: 'ended', startedAt: (clone $now)->modify('-3 hour')),
        ];
        // Manually set durations via reflection
        $ref = new \ReflectionClass($calls[0]);
        $prop = $ref->getProperty('duration'); $prop->setAccessible(true); $prop->setValue($calls[0], 300);
        $ref2 = new \ReflectionClass($calls[2]);
        $prop2 = $ref2->getProperty('duration'); $prop2->setAccessible(true); $prop2->setValue($calls[2], 180);
        $this->repoMock->method('findByUserId')->with(5)->willReturn($calls);
        $stats = $this->service->getCallStats(5);
        $this->assertSame(3, $stats['total_calls']);
        $this->assertSame(2, $stats['answered']);
        $this->assertSame(1, $stats['missed']);
        $this->assertSame(480, $stats['total_duration_seconds']);
        $this->assertSame(160, $stats['avg_duration_seconds']);
    }

    // ==================== getActiveCalls ====================

    public function testGetActiveCalls(): void
    {
        // No 'active' status in CallRecord; expect empty result.
        $calls = [
            $this->createCall(id: 1, status: 'ended'),
            $this->createCall(id: 2, status: 'missed'),
        ];
        $this->repoMock->method('findByUserId')->with(5)->willReturn($calls);
        $result = $this->service->getActiveCalls(5);
        $this->assertEmpty($result);
    }

    // ==================== getCallById alias ====================

    public function testGetCallByIdDelegates(): void
    {
        $call = $this->createCall(id: 7);
        $this->repoMock->method('findById')->with(7)->willReturn($call);
        $result = $this->service->getCallById(7);
        $this->assertSame($call, $result);
    }
}
