<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\Entity\Officer;
use App\Government\Domain\ValueObject\Department;
use App\Government\Domain\ValueObject\OfficerId;
use App\Government\Domain\ValueObject\Position;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OfficerTest extends TestCase
{
    private function createOfficer(?string $id = null, ?string $fullName = null, ?Position $position = null, ?Department $department = null): Officer
    {
        $id ??= 'off-'.uniqid();
        $fullName ??= 'John Doe';
        $position ??= new Position('off', 'Officer', 1);
        $department ??= new Department('dept-1', 'General');
        return new Officer(new OfficerId($id), $fullName, $position, $department);
    }

    public function testConstructorInitializesDefaults(): void
    {
        $id = 'off-123';
        $fullName = 'Jane Smith';
        $position = new Position('insp', 'Inspector', 2);
        $department = new Department('police', 'Police Department');

        $officer = new Officer(new OfficerId($id), $fullName, $position, $department);

        $this->assertSame($id, $officer->getId()->getValue());
        $this->assertSame($fullName, $officer->getFullName());
        $this->assertEquals($position, $officer->getPosition());
        $this->assertEquals($department, $officer->getDepartment());
        $this->assertInstanceOf(\DateTimeImmutable::class, $officer->getCreatedAt());
        $this->assertTrue($officer->isActive());
        $this->assertNull($officer->getEmail());
        $this->assertNull($officer->getPhone());
        $this->assertNull($officer->getDateOfBirth());
        $this->assertNull($officer->getIdentityNumber());
    }

    public function testGetFullName(): void
    {
        $officer = $this->createOfficer(fullName: 'Alice');
        $this->assertSame('Alice', $officer->getFullName());
    }

    public function testSetFullName(): void
    {
        $officer = $this->createOfficer();
        $officer->setFullName('Bob');
        $this->assertSame('Bob', $officer->getFullName());
    }

    public function testSetFullNameThrowsOnEmpty(): void
    {
        $officer = $this->createOfficer();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Full name cannot be empty');
        $officer->setFullName('   ');
    }

    public function testGetEmailAndSetEmail(): void
    {
        $officer = $this->createOfficer();
        $this->assertNull($officer->getEmail());
        $officer->setEmail('jane@example.com');
        $this->assertSame('jane@example.com', $officer->getEmail());
        $officer->setEmail(null);
        $this->assertNull($officer->getEmail());
    }

    public function testGetPhoneAndSetPhone(): void
    {
        $officer = $this->createOfficer();
        $this->assertNull($officer->getPhone());
        $officer->setPhone('1234567890');
        $this->assertSame('1234567890', $officer->getPhone());
        $officer->setPhone(null);
        $this->assertNull($officer->getPhone());
    }

    public function testGetDateOfBirthAndSetDateOfBirth(): void
    {
        $officer = $this->createOfficer();
        $this->assertNull($officer->getDateOfBirth());
        $dob = new \DateTimeImmutable('1990-01-01');
        $officer->setDateOfBirth($dob);
        $this->assertSame($dob, $officer->getDateOfBirth());
        $officer->setDateOfBirth(null);
        $this->assertNull($officer->getDateOfBirth());
    }

    public function testGetIdentityNumberAndSetIdentityNumber(): void
    {
        $officer = $this->createOfficer();
        $this->assertNull($officer->getIdentityNumber());
        $officer->setIdentityNumber('123456789');
        $this->assertSame('123456789', $officer->getIdentityNumber());
        $officer->setIdentityNumber(null);
        $this->assertNull($officer->getIdentityNumber());
    }

    public function testUpdatePosition(): void
    {
        $officer = $this->createOfficer();
        $newPos = new Position('sgnt', 'Sergeant', 3);
        $officer->setPosition($newPos);
        $this->assertEquals($newPos, $officer->getPosition());
    }

    public function testUpdateDepartment(): void
    {
        $officer = $this->createOfficer();
        $newDept = new Department('dept-2', 'Investigation');
        $officer->setDepartment($newDept);
        $this->assertEquals($newDept, $officer->getDepartment());
    }

    public function testActivate(): void
    {
        $officer = $this->createOfficer();
        $officer->deactivate();
        $this->assertFalse($officer->isActive());
        $officer->activate();
        $this->assertTrue($officer->isActive());
    }

    public function testDeactivate(): void
    {
        $officer = $this->createOfficer();
        $officer->deactivate();
        $this->assertFalse($officer->isActive());
    }

    public function testUpdatedAtIsNullInitially(): void
    {
        $officer = $this->createOfficer();
        $this->assertNull($officer->getUpdatedAt());
    }

    // Note: There may be no setter for updatedAt; it's set by callbacks. So we can't test here.
}
