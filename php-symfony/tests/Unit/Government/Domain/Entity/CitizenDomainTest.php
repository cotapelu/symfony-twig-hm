<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\Entity;

use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\ValueObject\Address;
use App\Government\Domain\ValueObject\CitizenId;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the DOMAIN Citizen entity (App\Government\Domain\Entity\Citizen).
 * Note: tests/Entity/Government/CitizenTest.php covers a DIFFERENT class
 * (App\Entity\Government\Citizen) — both exist; this one is the DDD entity.
 */
class CitizenDomainTest extends TestCase
{
    private function makeCitizen(): Citizen
    {
        return new Citizen(
            new CitizenId('cit-1'),
            'Nguyen Van A',
            '012345678901',
            new Address('TP HCM', 'Quan 1', 'Phuong Ben Nghe', 'Le Loi')
        );
    }

    // ================= constructor / identity =================
    public function testConstructorInitializesState(): void
    {
        $citizen = $this->makeCitizen();

        $this->assertSame('cit-1', $citizen->getId()->getValue());
        $this->assertSame('Nguyen Van A', $citizen->getFullName());
        $this->assertSame('012345678901', $citizen->getIdentityNumber());
        $this->assertInstanceOf(Address::class, $citizen->getRegisteredAddress());
        $this->assertTrue($citizen->isActive());
        $this->assertFalse($citizen->isDeceased());
        $this->assertNull($citizen->getEmail());
        $this->assertNull($citizen->getPhone());
        $this->assertNull($citizen->getAge());
        $this->assertInstanceOf(DateTimeImmutable::class, $citizen->getCreatedAt());
        $this->assertNull($citizen->getUpdatedAt());
    }

    public function testGetIdReturnsNewValueObject(): void
    {
        $citizen = $this->makeCitizen();
        $id1 = $citizen->getId();
        $id2 = $citizen->getId();

        $this->assertTrue($id1->equals($id2));
        $this->assertNotSame($id1, $id2); // fresh VO per read
    }

    // ================= validation setters =================
    public function testSetFullNameTrimsAndSanitizes(): void
    {
        $citizen = $this->makeCitizen();

        $citizen->setFullName('  <b>Tran Thi B</b>  ');

        // XSS guard: names are html-escaped on persistence
        $this->assertSame(
            htmlspecialchars('<b>Tran Thi B</b>', ENT_QUOTES, 'UTF-8'),
            $citizen->getFullName()
        );
        $this->assertNotNull($citizen->getUpdatedAt());
    }

    public function testSetFullNameRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Full name cannot be empty');
        $this->makeCitizen()->setFullName('   ');
    }

    public function testSetFullNameRejectsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Full name cannot exceed 255 characters');
        $this->makeCitizen()->setFullName(str_repeat('a', 256));
    }

    public function testSetEmailValidatesFormat(): void
    {
        $citizen = $this->makeCitizen();

        $citizen->setEmail('a@example.com');
        $this->assertSame('a@example.com', $citizen->getEmail());

        $citizen->setEmail(null);
        $this->assertNull($citizen->getEmail());

        $citizen->setEmail('  ');
        $this->assertSame('', $citizen->getEmail()); // empty treated as blank
    }

    public function testSetEmailRejectsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        $this->makeCitizen()->setEmail('not-an-email');
    }

    public function testSetPhoneValidatesFormat(): void
    {
        $citizen = $this->makeCitizen();

        $citizen->setPhone('0909 123 456');
        $this->assertSame('0909 123 456', $citizen->getPhone());

        $citizen->setPhone(null);
        $this->assertNull($citizen->getPhone());
    }

    public function testSetPhoneRejectsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number format');
        $this->makeCitizen()->setPhone('abc-defg');
    }

    public function testSetDateOfBirthRejectsFuture(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Date of birth cannot be in the future');
        $this->makeCitizen()->setDateOfBirth(new DateTimeImmutable('+1 day'));
    }

    public function testSetDateOfBirthRejectsBefore1900(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Date of birth cannot be before 1900');
        $this->makeCitizen()->setDateOfBirth(new DateTimeImmutable('1899-12-31'));
    }

    public function testSetDateOfBirthAcceptsValidPastDate(): void
    {
        $citizen = $this->makeCitizen();
        $dob = new DateTimeImmutable('1990-05-20');

        $citizen->setDateOfBirth($dob);

        $this->assertSame($dob, $citizen->getDateOfBirth());
    }

    // ================= lifecycle =================
    public function testActivateDeactivate(): void
    {
        $citizen = $this->makeCitizen();

        $citizen->deactivate();
        $this->assertFalse($citizen->isActive());

        $citizen->activate();
        $this->assertTrue($citizen->isActive());
    }

    public function testIsDeceasedReflectsDeathDate(): void
    {
        $citizen = $this->makeCitizen();
        $this->assertFalse($citizen->isDeceased());
    }

    // ================= age logic =================
    public function testGetAgeNullWhenNoDob(): void
    {
        $this->assertNull($this->makeCitizen()->getAge());
    }

    public function testGetAgeComputesFromDob(): void
    {
        $citizen = $this->makeCitizen();
        // 30 years before today, birthday-safe (same month/day in the past)
        $dob = (new DateTimeImmutable('-30 years'));
        $citizen->setDateOfBirth($dob);

        $this->assertSame(30, $citizen->getAge());
    }

    // ================= optional profile fields =================
    public function testProfileSetters(): void
    {
        // setters return void on this entity — call them individually
        $citizen = $this->makeCitizen();
        $citizen->setGender('male'); // entity accepts male/female/other
        $citizen->setEthnicity('Kinh');
        $citizen->setReligion('Khong');
        $citizen->setNationality('Viet Nam');
        $citizen->setBloodType('O+'); // entity validates A/B/AB/O with ±
        $citizen->setOccupation('Nong dan');
        $citizen->setEducationLevel('Dai hoc');
        $citizen->setMaritalStatus('single'); // entity validates single/married/divorced/widowed
        $citizen->setHouseholdId('HK-001');
        $citizen->setIdentityIssueDate('2020-01-01');
        $citizen->setIdentityIssuePlace('Cuc CSQL');
        $citizen->setTemporaryAddress(null);

        $this->assertSame('male', $citizen->getGender());
        $this->assertSame('Kinh', $citizen->getEthnicity());
        $this->assertSame('O+', $citizen->getBloodType());
        $this->assertSame('HK-001', $citizen->getHouseholdId());
    }

    public function testSetTemporaryAddress(): void
    {
        $citizen = $this->makeCitizen();
        $temp = new Address('Ha Noi', 'Ba Dinh', 'Dien Bien', 'Hoang Hoa');

        $citizen->setTemporaryAddress($temp);

        $this->assertSame($temp, $citizen->getTemporaryAddress());
    }

    // ================= serialization =================
    public function testToArraySerializesCoreFields(): void
    {
        $citizen = $this->makeCitizen();
        $citizen->setGender('male');
        $citizen->setDateOfBirth(new DateTimeImmutable('1990-05-20'));

        $arr = $citizen->toArray();

        $this->assertIsArray($arr);
        $this->assertSame('cit-1', $arr['id']);
        $this->assertSame('012345678901', $arr['identityNumber']);
        $this->assertSame('1990-05-20', $arr['dateOfBirth']);
        $this->assertIsArray($arr['registeredAddress']);
    }
}
