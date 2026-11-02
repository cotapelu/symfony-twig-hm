<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\DomainEvent;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\DomainEvent\DomainEvent;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]

class TestDomainEvent extends DomainEvent
{
    private string $name;

    public function __construct(string $name, array $metadata = [])
    {
        $this->name = $name;
        parent::__construct($metadata);
    }

    public function eventType(): string
    {
        return 'test.event';
    }

    protected function eventData(): array
    {
        return ['name' => $this->name];
    }
}

class DomainEventTest extends TestCase
{
    public function testConstructGeneratesEventIdAndOccurredOn(): void
    {
        $event = new TestDomainEvent('test');
        $this->assertNotEmpty($event->eventId());
        $this->assertMatchesRegularExpression('/^event_[0-9a-f]+(?:\.[0-9a-f]+)?$/', $event->eventId());
        $this->assertNotEmpty($event->occurredOn());
        // Check ISO 8601 format
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $event->occurredOn());
    }

    public function testMetadataIncludesVersionAndSource(): void
    {
        $event = new TestDomainEvent('test');
        $meta = $event->metadata();
        $this->assertEquals('1.0', $meta['version']);
        $this->assertEquals(TestDomainEvent::class, $meta['source']);
    }

    public function testMetadataCanBeExtended(): void
    {
        $event = new TestDomainEvent('test', ['custom' => 'value', 'tags' => ['a', 'b']]);
        $meta = $event->metadata();
        $this->assertEquals('value', $meta['custom']);
        $this->assertEquals(['a', 'b'], $meta['tags']);
    }

    public function testToArrayIncludesAllFields(): void
    {
        $event = new TestDomainEvent('myname', ['key' => 'val']);
        $arr = $event->toArray();
        $this->assertArrayHasKey('eventId', $arr);
        $this->assertArrayHasKey('eventType', $arr);
        $this->assertArrayHasKey('occurredOn', $arr);
        $this->assertArrayHasKey('metadata', $arr);
        $this->assertArrayHasKey('data', $arr);
        $this->assertEquals('test.event', $arr['eventType']);
        $this->assertEquals(['name' => 'myname'], $arr['data']);
        $this->assertEquals('val', $arr['metadata']['key']);
    }

    public function testEventDataIsAbstract(): void
    {
        $this->expectNotToPerformAssertions(); // just ensure instantiating subclass works
        new TestDomainEvent('ok');
    }
}
