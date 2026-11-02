<?php declare(strict_types=1);

namespace App\Tests\Unit\Call\Entity;

use App\Call\Entity\CallRecord;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Call\Entity\CallRecord
 */
class CallRecordTest extends TestCase
{
    public function testConstructorSetsRequiredProperties(): void
    {
        $call = new CallRecord(1, 2, 'audio');

        $this->assertNull($call->getId());
        $this->assertEquals(1, $call->getCallerId());
        $this->assertEquals(2, $call->getReceiverId());
        $this->assertEquals('audio', $call->getType());
        $this->assertEquals('initiated', $call->getStatus());
        $this->assertEquals(0, $call->getDuration());
    }

    public function testVideoCall(): void
    {
        $call = new CallRecord(1, 2, 'video');

        $this->assertEquals('video', $call->getType());
    }

    public function testSetId(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setId(123);

        $this->assertEquals(123, $call->getId());
    }

    public function testSetConversationId(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setConversationId(456);

        $this->assertEquals(456, $call->getConversationId());
    }

    public function testSetAnswered(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $this->assertEquals('initiated', $call->getStatus());
        $this->assertNull($call->getAnsweredAt());

        $call->setAnswered();

        $this->assertEquals('answered', $call->getStatus());
        $this->assertInstanceOf(\DateTime::class, $call->getAnsweredAt());
    }

    public function testEndInitiatedCall(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->end();

        $this->assertEquals('ended', $call->getStatus());
        $this->assertInstanceOf(\DateTime::class, $call->getEndedAt());
        $this->assertEquals(0, $call->getDuration()); // Never answered
    }

    public function testEndAnsweredCall(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->setAnswered();
        sleep(1); // Wait 1 second
        $call->end();

        $this->assertEquals('ended', $call->getStatus());
        $this->assertGreaterThan(0, $call->getDuration());
    }

    public function testMarkMissed(): void
    {
        $call = new CallRecord(1, 2, 'audio');
        $call->markMissed();

        $this->assertEquals('missed', $call->getStatus());
    }

    public function testSettersAreChainable(): void
    {
        $call = new CallRecord(1, 2, 'audio');

        $result = $call
            ->setId(123)
            ->setConversationId(456)
            ->markMissed();

        $this->assertSame($call, $result);
    }

    public function testGettersReturnCorrectValues(): void
    {
        $call = new CallRecord(100, 200, 'video');

        $this->assertEquals(100, $call->getCallerId());
        $this->assertEquals(200, $call->getReceiverId());
        $this->assertEquals('video', $call->getType());
        $this->assertEquals('initiated', $call->getStatus());
        $this->assertEquals(0, $call->getDuration());
        $this->assertInstanceOf(\DateTime::class, $call->getStartedAt());
    }
}
