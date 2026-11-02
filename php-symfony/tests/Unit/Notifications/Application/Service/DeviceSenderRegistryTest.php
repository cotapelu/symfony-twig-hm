<?php declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notifications\Application\Service\DeviceSenderRegistry;
use App\Notifications\Application\Service\DeviceSenderInterface;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeviceSenderRegistryTest extends TestCase
{
    public function testConstructorThrowsIfInvalidSenderProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DeviceSenderRegistry([new \stdClass()]);
    }

    public function testGetSenderForTypeReturnsNullWhenNoneSupport(): void
    {
        $sender1 = $this->createMock(DeviceSenderInterface::class);
        $sender1->method('supports')->willReturn(false);
        $sender2 = $this->createMock(DeviceSenderInterface::class);
        $sender2->method('supports')->willReturn(false);

        $registry = new DeviceSenderRegistry([$sender1, $sender2]);

        $result = $registry->getSenderForType('unknown');
        $this->assertNull($result);
    }

    public function testGetSenderForTypeReturnsFirstMatchingSender(): void
    {
        $sender1 = $this->createMock(DeviceSenderInterface::class);
        $sender1->method('supports')->willReturn(false);
        $sender2 = $this->createMock(DeviceSenderInterface::class);
        $sender2->method('supports')->willReturn(true);
        $sender3 = $this->createMock(DeviceSenderInterface::class);
        $sender3->method('supports')->willReturn(true);

        $registry = new DeviceSenderRegistry([$sender1, $sender2, $sender3]);

        $result = $registry->getSenderForType('some_type');
        $this->assertSame($sender2, $result);
    }
}
