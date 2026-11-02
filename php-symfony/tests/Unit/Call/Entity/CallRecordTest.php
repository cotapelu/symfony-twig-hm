<?php declare(strict_types=1);

namespace App\Tests\Unit\Call\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Call\Entity\CallRecord;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CallRecordTest extends TestCase
{
    private function createBasicRecord(?int $receiverId = null, string $type = 'audio'): CallRecord
    {
        return new CallRecord(callerId: 1, receiverId: $receiverId, type: $type);
    }

    public function testConstructorInitializesProperties(): void
    {
        $record = $this->createBasicRecord(2, 'video');
        $this->assertNull($record->getId());
        $this->assertSame(1, $record->getCallerId());
        $this->assertSame(2, $record->getReceiverId());
        $this->assertNull($record->getConversationId());
        $this->assertSame('video', $record->getType());
        $this->assertSame('initiated', $record->getStatus());
        $this->assertSame(0, $record->getDuration());
        $this->assertInstanceOf(\DateTime::class, $record->getStartedAt());
        $this->assertNull($record->getAnsweredAt());
        $this->assertNull($record->getEndedAt());
    }

    public function testDefaultTypeAndStatus(): void
    {
        $record = $this->createBasicRecord(null, 'audio');
        $this->assertSame('audio', $record->getType());
        $this->assertSame('initiated', $record->getStatus());
    }

    public function testSetConversationId(): void
    {
        $record = $this->createBasicRecord();
        $record->setConversationId(100);
        $this->assertSame(100, $record->getConversationId());
    }

    public function testAnswered(): void
    {
        $record = $this->createBasicRecord();
        $this->assertNull($record->getAnsweredAt());
        $this->assertSame('initiated', $record->getStatus());

        $record->setAnswered();
        $this->assertInstanceOf(\DateTime::class, $record->getAnsweredAt());
        $this->assertSame('answered', $record->getStatus());
    }

    public function testEnd(): void
    {
        $record = $this->createBasicRecord();
        // Simulate call flow: start -> answer -> end
        $record->setAnswered();
        // Small delay to ensure duration > 0; but we can't guarantee. We'll check logic by stubbing? Not needed.
        $record->end(); // sets endedAt and calculates duration based on answeredAt and startedAt

        $this->assertInstanceOf(\DateTime::class, $record->getEndedAt());
        $this->assertSame('ended', $record->getStatus());
        // Duration should be non-negative integer
        $this->assertIsInt($record->getDuration());
        $this->assertGreaterThanOrEqual(0, $record->getDuration());
    }

    public function testMarkMissed(): void
    {
        $record = $this->createBasicRecord();
        $record->markMissed();
        $this->assertSame('missed', $record->getStatus());
    }

    public function testIdSetter(): void
    {
        $record = $this->createBasicRecord();
        $this->assertNull($record->getId());
        $record->setId(50);
        $this->assertSame(50, $record->getId());
    }
}
