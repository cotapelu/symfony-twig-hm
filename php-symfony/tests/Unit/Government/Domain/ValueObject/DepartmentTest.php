<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\ValueObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\ValueObject\Department;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DepartmentTest extends TestCase
{
    public function testConstructorThrowsIfCodeEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Department code and name cannot be empty');
        new Department('', 'HR');
    }

    public function testConstructorThrowsIfNameEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Department code and name cannot be empty');
        new Department('IT', '');
    }

    public function testConstructorWithValidData(): void
    {
        $dept = new Department('IT', 'Information Technology', 'CORP');
        $this->assertSame('IT', $dept->getCode());
        $this->assertSame('Information Technology', $dept->getName());
        $this->assertSame('CORP', $dept->getParentCode());
    }

    public function testGetParentCodeReturnsNullWhenNotProvided(): void
    {
        $dept = new Department('HR', 'Human Resources');
        $this->assertNull($dept->getParentCode());
    }

    public function testEquals(): void
    {
        $dept1 = new Department('FIN', 'Finance');
        $dept2 = new Department('FIN', 'Finance Department'); // different name
        $dept3 = new Department('HR', 'Human Resources');

        $this->assertTrue($dept1->equals($dept2)); // same code
        $this->assertFalse($dept1->equals($dept3));
    }
}
