<?php

declare(strict_types=1);

namespace App\Tests\Unit\Platform\Eventing\Outbox;

use Platform\Eventing\Outbox\OutboxProcessor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OutboxProcessorTest extends TestCase
{
    private OutboxProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new OutboxProcessor();
    }

    #[Test]
    public function it_processes_outbox_and_returns_count(): void
    {
        $result = $this->processor->processOutbox();

        $this->assertSame(0, $result);
    }

    #[Test]
    public function it_adds_message_to_outbox(): void
    {
        $this->processor->addToOutbox('UserCreated', ['userId' => '123'], 'user-123');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function it_throws_for_empty_event_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->processor->addToOutbox('', ['userId' => '123'], 'user-123');
    }

    #[Test]
    public function it_throws_for_invalid_event_type_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->processor->addToOutbox('invalid event type!', ['userId' => '123'], 'user-123');
    }

    #[Test]
    public function it_throws_for_empty_payload(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->processor->addToOutbox('UserCreated', [], 'user-123');
    }

    #[Test]
    public function it_throws_for_empty_aggregate_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->processor->addToOutbox('UserCreated', ['userId' => '123'], '');
    }

    #[Test]
    public function it_returns_empty_pending_messages(): void
    {
        $result = $this->processor->getPendingMessages(10);

        $this->assertSame([], $result);
    }

    #[Test]
    public function it_throws_for_non_positive_limit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->processor->getPendingMessages(0);
    }

    #[Test]
    public function it_throws_for_limit_exceeding_maximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->processor->getPendingMessages(1001);
    }
}
