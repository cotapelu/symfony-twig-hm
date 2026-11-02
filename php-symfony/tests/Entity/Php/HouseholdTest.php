<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\Household;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HouseholdTest extends TestCase
{
    private function createHousehold(
        string $id = 'household-id',
        string $address = '123 Main St'
    ): Household {
        return new Household($id, $address);
    }

    public function testConstructor(): void
    {
        $h = $this->createHousehold();
        $this->assertSame('household-id', $h->getId());
        $this->assertNull($h->getWardId());
        $this->assertNull($h->getHeadId());
        $this->assertSame('123 Main St', $h->getAddress());
        $this->assertNull($h->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $h->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $h->getUpdatedAt());
        $this->assertFalse($h->hasHead());
        $this->assertFalse($h->hasWard());
    }

    public function testSetWardId(): void
    {
        $h = $this->createHousehold();
        $originalUpdatedAt = $h->getUpdatedAt();
        usleep(1000);
        $h->setWardId('ward-1');
        $this->assertSame('ward-1', $h->getWardId());
        $this->assertTrue($h->hasWard());
        $this->assertNotEquals($originalUpdatedAt, $h->getUpdatedAt());
        $h->setWardId(null);
        $this->assertNull($h->getWardId());
        $this->assertFalse($h->hasWard());
    }

    public function testSetHeadId(): void
    {
        $h = $this->createHousehold();
        $originalUpdatedAt = $h->getUpdatedAt();
        usleep(1000);
        $h->setHeadId('head-1');
        $this->assertSame('head-1', $h->getHeadId());
        $this->assertTrue($h->hasHead());
        $this->assertNotEquals($originalUpdatedAt, $h->getUpdatedAt());
        $h->setHeadId(null);
        $this->assertNull($h->getHeadId());
        $this->assertFalse($h->hasHead());
    }

    public function testSetAddress(): void
    {
        $h = $this->createHousehold();
        $originalUpdatedAt = $h->getUpdatedAt();
        usleep(1000);
        $h->setAddress('New Address');
        $this->assertSame('New Address', $h->getAddress());
        $this->assertNotEquals($originalUpdatedAt, $h->getUpdatedAt());
    }

    public function testSetMetadata(): void
    {
        $h = $this->createHousehold();
        $originalUpdatedAt = $h->getUpdatedAt();
        usleep(1000);
        $meta = ['type' => 'apartment', 'floor' => 3];
        $h->setMetadata($meta);
        $this->assertSame($meta, $h->getMetadata());
        $this->assertNotEquals($originalUpdatedAt, $h->getUpdatedAt());
        $h->setMetadata(null);
        $this->assertNull($h->getMetadata());
    }
}
