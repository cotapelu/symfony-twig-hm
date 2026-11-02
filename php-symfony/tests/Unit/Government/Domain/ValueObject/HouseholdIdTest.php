<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\ValueObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\ValueObject\HouseholdId;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HouseholdIdTest extends TestCase
{
    public function testConstructorThrowsIfEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Household ID cannot be empty');
        new HouseholdId('');
    }

    public function testConstructorTrimsAndThrowsIfWhitespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Household ID cannot be empty');
        new HouseholdId('   ');
    }

    public function testConstructorAcceptsValid(): void
    {
        $id = new HouseholdId('HH-001');
        $this->assertSame('HH-001', $id->getValue());
        $this->assertSame('HH-001', (string)$id);
    }

    public function testEquals(): void
    {
        $id1 = new HouseholdId('A');
        $id2 = new HouseholdId('A');
        $id3 = new HouseholdId('B');
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }
}
