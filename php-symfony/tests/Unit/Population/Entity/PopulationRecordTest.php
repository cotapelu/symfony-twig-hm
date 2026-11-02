<?php declare(strict_types=1);

namespace App\Tests\Unit\Population\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Population\Entity\PopulationRecord;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PopulationRecordTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $record = new PopulationRecord();
        $this->assertInstanceOf(\DateTime::class, $record->getCreatedAt());
    }

    public function testGettersSetters(): void
    {
        $record = new PopulationRecord();

        $record->setName('John Doe');
        $this->assertSame('John Doe', $record->getName());

        $birth = new \DateTime('1990-01-01');
        $record->setBirthDate($birth);
        $this->assertSame($birth, $record->getBirthDate());

        $record->setGender('male');
        $this->assertSame('male', $record->getGender());

        $record->setAddress('123 Main St');
        $this->assertSame('123 Main St', $record->getAddress());

        $record->setHouseholdId('household-123');
        $this->assertSame('household-123', $record->getHouseholdId());

        $record->setStatus('active');
        $this->assertSame('active', $record->getStatus());

        $record->setType('citizen');
        $this->assertSame('citizen', $record->getType());

        $record->setCitizenId('CID-123456');
        $this->assertSame('CID-123456', $record->getCitizenId());

        $updated = new \DateTime('2025-01-01');
        $record->setUpdatedAt($updated);
        $this->assertSame($updated, $record->getUpdatedAt());

        $record->setUpdatedAt(null);
        $this->assertNull($record->getUpdatedAt());
    }

    public function testIdSetAndGet(): void
    {
        $record = new PopulationRecord();
        $ref = new \ReflectionProperty($record, 'id');
        $ref->setAccessible(true);
        $ref->setValue($record, 5);
        $this->assertSame(5, $record->getId());
    }
}
