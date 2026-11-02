<?php declare(strict_types=1);

namespace App\Tests\Unit\Call\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Call\Entity\Call;
use App\User\Entity\User;
use App\Conversation\Entity\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CallTest extends TestCase
{
    private function createMockUser(int $id = 1): MockObject&User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    private function createMockGroup(int $id = 1): MockObject&Group
    {
        $group = $this->createMock(Group::class);
        $group->method('getId')->willReturn($id);
        return $group;
    }

    private function createCall(?User $initiator = null): Call
    {
        return new Call(); // constructor generates callId, status='ringing', startedAt, participants=[]
    }

    public function testConstructorInitializesProperties(): void
    {
        $call = $this->createCall();
        // id not accessible
        $this->assertIsString($call->getCallId());
        $this->assertStringStartsWith('call_', $call->getCallId());
        $this->assertInstanceOf(\DateTime::class, $call->getStartedAt());
        $this->assertSame([], $call->getParticipants());
        // Default status is 'ringing'
        $this->assertSame('ringing', $call->getStatus());
    }

    public function testInitiatorAndType(): void
    {
        $call = $this->createCall();
        $initiator = $this->createMockUser(5);

        $call->setInitiator($initiator);
        $this->assertSame($initiator, $call->getInitiator());

        $call->setType('video');
        $this->assertSame('video', $call->getType());
    }

    public function testRecipientAndGroup(): void
    {
        $call = $this->createCall();
        $recipient = $this->createMockUser(10);
        $group = $this->createMockGroup(20);

        $call->setRecipient($recipient);
        $this->assertSame($recipient, $call->getRecipient());

        $call->setRecipient(null);
        $this->assertNull($call->getRecipient());

        $call->setGroup($group);
        $this->assertSame($group, $call->getGroup());

        $call->setGroup(null);
        $this->assertNull($call->getGroup());
    }

    public function testStatusAndIsActive(): void
    {
        $call = $this->createCall();
        $this->assertTrue($call->isActive()); // ringing

        $call->setStatus('connected');
        $this->assertTrue($call->isActive());

        $call->setStatus('ended');
        $this->assertFalse($call->isActive());

        $call->setStatus('missed');
        $this->assertFalse($call->isActive());
    }

    public function testStartedAtAndEndedAtAndDuration(): void
    {
        $call = $this->createCall();
        $started = $call->getStartedAt();
        $this->assertInstanceOf(\DateTimeInterface::class, $started);

        // Set endedAt manually
        $ended = new \DateTime();
        $call->setEndedAt($ended);
        $this->assertSame($ended, $call->getEndedAt());
        $this->assertNotNull($call->getDuration());
        $expectedDuration = $ended->getTimestamp() - $started->getTimestamp();
        $this->assertSame($expectedDuration, $call->getDuration());

        // setEndedAt with null unsets duration? Actually setDuration called only if $endedAt truthy. If null, duration remains unchanged. So we can set null after.
        $call->setEndedAt(null);
        $this->assertNull($call->getEndedAt());
        // duration not altered; but we can set duration manually
        $call->setDuration(123);
        $this->assertSame(123, $call->getDuration());
    }

    public function testParticipants(): void
    {
        $call = $this->createCall();
        $this->assertSame([], $call->getParticipants());

        $call->addParticipant('user-1');
        $this->assertContains('user-1', $call->getParticipants());

        $call->addParticipant('user-2');
        $this->assertCount(2, $call->getParticipants());

        $call->removeParticipant('user-1');
        $this->assertNotContains('user-1', $call->getParticipants());
        $this->assertCount(1, $call->getParticipants());
    }

    public function testRecordingUrl(): void
    {
        $call = $this->createCall();
        $this->assertNull($call->getRecordingUrl());

        $call->setRecordingUrl('https://example.com/recording.mp4');
        $this->assertSame('https://example.com/recording.mp4', $call->getRecordingUrl());

        $call->setRecordingUrl(null);
        $this->assertNull($call->getRecordingUrl());
    }

    public function testEndCall(): void
    {
        $call = $this->createCall();
        $call->setType('video');
        $call->addParticipant('user-1');

        // Ensure status was ringing
        $this->assertSame('ringing', $call->getStatus());
        $this->assertNull($call->getEndedAt());
        $this->assertNull($call->getDuration());

        // Small delay
        usleep(10000);
        $call->endCall();

        $this->assertSame('ended', $call->getStatus());
        $this->assertInstanceOf(\DateTime::class, $call->getEndedAt());
        $this->assertNotNull($call->getDuration());
        $this->assertGreaterThanOrEqual(0, $call->getDuration());
    }
}
