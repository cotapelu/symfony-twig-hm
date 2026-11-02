<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\ValueObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\ValueObject\Position;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PositionTest extends TestCase
{
    public function testConstructorThrowsIfCodeEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Position code and name cannot be empty');
        new Position('', 'Manager');
    }

    public function testConstructorThrowsIfNameEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Position code and name cannot be empty');
        new Position('MGR', '');
    }

    public function testConstructorWithValidData(): void
    {
        $pos = new Position('MGR', 'Manager', 5);
        $this->assertSame('MGR', $pos->getCode());
        $this->assertSame('Manager', $pos->getName());
        $this->assertSame(5, $pos->getLevel());
    }

    public function testDefaultLevelIsZero(): void
    {
        $pos = new Position('STF', 'Staff');
        $this->assertSame(0, $pos->getLevel());
    }

    public function testEquals(): void
    {
        $pos1 = new Position('DIR', 'Director');
        $pos2 = new Position('DIR', 'Directorate'); // different name
        $pos3 = new Position('MGR', 'Manager');

        $this->assertTrue($pos1->equals($pos2)); // same code
        $this->assertFalse($pos1->equals($pos3));
    }
}
