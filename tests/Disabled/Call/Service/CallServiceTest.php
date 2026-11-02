<?php declare(strict_types=1);

namespace App\Tests\Unit\Call\Service;

use App\Call\Entity\CallRecord;
use App\Call\Repository\CallRepositoryInterface;
use App\Call\Service\CallService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Call\Service\CallService
 */
class CallServiceTest extends TestCase
{
    private CallService $service;
    private CallRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CallRepositoryInterface::class);
        $this->service = new CallService($this->repository);
    }

    public function testInitiateCallSuccess(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setId(1);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (CallRecord $c) {
                $c->setId(1);
                return $c;
            });

        $result = $this->service->initiateCall(1, 2, 'audio');

        $this->assertInstanceOf(CallRecord::class, $result);
        $this->assertEquals(1, $result->getCallerId());
        $this->assertEquals(2, $result->getReceiverId());
        $this->assertEquals('audio', $result->getType());
        $this->assertEquals('initiated', $result->getStatus());
    }

    public function testAnswerCall(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setId(1);
        // Call is initiated by default from constructor

        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($call);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (CallRecord $c) {
                $c->setId(1);
                $c->setAnswered();
                return $c;
            });

        $result = $this->service->answerCall(1);

        $this->assertEquals('answered', $result->getStatus());
    }

    public function testEndCall(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setId(1);
        // Answer the call first so it's in 'answered' state before ending
        $call->setAnswered();

        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($call);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (CallRecord $c) {
                $c->setId(1);
                $c->end();
                return $c;
            });

        $result = $this->service->endCall(1);

        $this->assertEquals('ended', $result->getStatus());
        $this->assertGreaterThanOrEqual(0, $result->getDuration());
    }

    public function testMissCall(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($call);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($call);

        $result = $this->service->missCall(1);

        $this->assertEquals('missed', $result->getStatus());
    }

    public function testGetUserCalls(): void
    {
        $call1 = new CallRecord(1, 2, 'audio');
        $call1->setId(1);
        $call2 = new CallRecord(1, 3, 'video');
        $call2->setId(2);
        $call3 = new CallRecord(4, 5, 'audio');
        $call3->setId(3);
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with(1)
            ->willReturn([$call1, $call2]);

        $result = $this->service->getUserCalls(1);

        $this->assertCount(2, $result);
    }

    public function testGetRecentCalls(): void
    {
        $call1 = new CallRecord(1, 2, 'audio');
        $call1->setId(1);
        $call2 = new CallRecord(1, 3, 'video');
        $call2->setId(2);
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with(1)
            ->willReturn([$call1, $call2]);

        $result = $this->service->getRecentCalls(1, 10);

        $this->assertCount(2, $result);
    }

    public function testGetMissedCalls(): void
    {
        $call1 = new CallRecord(1, 2, 'audio');
        $call1->setId(1);
        $call1->markMissed();
        $call2 = new CallRecord(1, 3, 'video');
        $call2->setId(2);
        $call2->setAnswered();
        $this->repository->expects($this->once())
            ->method('findMissedByUserId')
            ->with(1)
            ->willReturn([$call1]);

        $result = $this->service->getMissedCalls(1);

        $this->assertCount(1, $result);
        $this->assertEquals('missed', $result[0]->getStatus());
    }

    public function testLinkToConversation(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($call);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($call);

        $result = $this->service->linkToConversation(1, 100);

        $this->assertEquals(100, $result->getConversationId());
    }

    public function testGetCallStats(): void
    {
        $call1 = new CallRecord(1, 2, 'audio');
        $call1->setId(1);
        $call1->setAnswered();
        $call1->end();
        $call2 = new CallRecord(1, 3, 'video');
        $call2->setId(2);
        $call2->markMissed();
        $call3 = new CallRecord(1, 4, 'audio');
        $call3->setId(3);
        $call3->setAnswered();
        $call3->end();
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with(1)
            ->willReturn([$call1, $call2, $call3]);

        $result = $this->service->getCallStats(1);

        $this->assertEquals(3, $result['total_calls']);
        $this->assertEquals(2, $result['answered']);
        $this->assertEquals(1, $result['missed']);
        $this->assertGreaterThanOrEqual(0, $result['total_duration_seconds']);
    }
}
