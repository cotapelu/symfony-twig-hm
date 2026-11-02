<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\GovOfficer;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GovOfficerTest extends TestCase
{
    private function createOfficer(
        string $id = 'officer-id',
        string $userId = 'user-1',
        string $department = 'Health',
        string $position = 'Inspector'
    ): GovOfficer {
        return new GovOfficer($id, $userId, $department, $position);
    }

    public function testConstructor(): void
    {
        $o = $this->createOfficer();
        $this->assertSame('officer-id', $o->getId());
        $this->assertSame('user-1', $o->getUserId());
        $this->assertSame('Health', $o->getDepartment());
        $this->assertSame('Inspector', $o->getPosition());
        $this->assertSame('ACTIVE', $o->getStatus());
        $this->assertNull($o->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $o->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $o->getUpdatedAt());
        $this->assertTrue($o->isActive());
        $this->assertFalse($o->isSuspended());
        $this->assertFalse($o->isDeleted());
    }

    public function testSetDepartment(): void
    {
        $o = $this->createOfficer();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $o->setDepartment('Finance');
        $this->assertSame('Finance', $o->getDepartment());
        $this->assertNotEquals($original, $o->getUpdatedAt());
    }

    public function testSetPosition(): void
    {
        $o = $this->createOfficer();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $o->setPosition('Director');
        $this->assertSame('Director', $o->getPosition());
        $this->assertNotEquals($original, $o->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $o = $this->createOfficer();
        $o->setStatus('SUSPENDED');
        $this->assertSame('SUSPENDED', $o->getStatus());
        $this->assertTrue($o->isSuspended());
        $this->assertFalse($o->isActive());

        $o->setStatus('DELETED');
        $this->assertTrue($o->isDeleted());
        $this->assertFalse($o->isActive());
    }

    public function testSetMetadata(): void
    {
        $o = $this->createOfficer();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $meta = ['clearance' => 'level-5'];
        $o->setMetadata($meta);
        $this->assertSame($meta, $o->getMetadata());
        $this->assertNotEquals($original, $o->getUpdatedAt());
        $o->setMetadata(null);
        $this->assertNull($o->getMetadata());
    }
}
