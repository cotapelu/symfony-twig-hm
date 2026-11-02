<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\EventBus;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\DomainEvent\DomainEvent;
use App\Shared\EventBus\EventBus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AllowMockObjectsWithoutExpectations]

class EventBusTest extends TestCase
{
    private EventDispatcherInterface&MockObject $dispatcherMock;
    private EventBus $bus;

    protected function setUp(): void
    {
        $this->dispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->bus = new EventBus($this->dispatcherMock);
    }

    public function testDispatchDispatchesEvent(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $this->dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with($event);

        $this->bus->dispatch($event);
    }
}
