<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\Citizen;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CitizenTest extends TestCase
{
    private function createCitizen(
        string $id = 'citizen-id',
        string $name = 'John Doe',
        string $identityNumber = 'ID-123'
    ): Citizen {
        return new Citizen($id, $name, $identityNumber);
    }

    public function testConstructor(): void
    {
        $c = $this->createCitizen();
        $this->assertSame('citizen-id', $c->getId());
        $this->assertSame('John Doe', $c->getName());
        $this->assertSame('ID-123', $c->getIdentityNumber());
        $this->assertNull($c->getHouseholdId());
        $this->assertSame('ACTIVE', $c->getStatus());
        $this->assertNull($c->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $c->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $c->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $c = $this->createCitizen();
        $originalUpdatedAt = $c->getUpdatedAt();
        usleep(1000);
        $c->setName('Jane Doe');
        $this->assertSame('Jane Doe', $c->getName());
        $this->assertNotEquals($originalUpdatedAt, $c->getUpdatedAt());
    }

    public function testSetIdentityNumber(): void
    {
        $c = $this->createCitizen();
        $originalUpdatedAt = $c->getUpdatedAt();
        usleep(1000);
        $c->setIdentityNumber('NEW-ID');
        $this->assertSame('NEW-ID', $c->getIdentityNumber());
        $this->assertNotEquals($originalUpdatedAt, $c->getUpdatedAt());
    }

    public function testSetHouseholdId(): void
    {
        $c = $this->createCitizen();
        $originalUpdatedAt = $c->getUpdatedAt();
        usleep(1000);
        $c->setHouseholdId('household-1');
        $this->assertSame('household-1', $c->getHouseholdId());
        $this->assertTrue($c->hasHousehold());
        $this->assertNotEquals($originalUpdatedAt, $c->getUpdatedAt());
        $c->setHouseholdId(null);
        $this->assertNull($c->getHouseholdId());
        $this->assertFalse($c->hasHousehold());
    }

    public function testSetStatus(): void
    {
        $c = $this->createCitizen();
        $c->setStatus('SUSPENDED');
        $this->assertSame('SUSPENDED', $c->getStatus());
        $this->assertTrue($c->isSuspended());
        $this->assertFalse($c->isActive());
        $this->assertFalse($c->isDeleted());

        $c->setStatus('DELETED');
        $this->assertTrue($c->isDeleted());
        $this->assertFalse($c->isActive());
        $this->assertFalse($c->isSuspended());

        $c->setStatus('ACTIVE');
        $this->assertTrue($c->isActive());
        $this->assertFalse($c->isSuspended());
        $this->assertFalse($c->isDeleted());
    }

    public function testSetMetadata(): void
    {
        $c = $this->createCitizen();
        $originalUpdatedAt = $c->getUpdatedAt();
        usleep(1000);
        $meta = ['ethnicity' => 'Kinh', 'religion' => 'Buddhist'];
        $c->setMetadata($meta);
        $this->assertSame($meta, $c->getMetadata());
        $this->assertNotEquals($originalUpdatedAt, $c->getUpdatedAt());
        $c->setMetadata(null);
        $this->assertNull($c->getMetadata());
    }
}
