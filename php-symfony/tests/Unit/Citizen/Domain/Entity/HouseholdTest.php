<?php

declare(strict_types=1);

namespace App\Tests\Unit\Citizen\Domain\Entity;

use PHPUnit\Framework\TestCase;
use App\Citizen\Domain\Entity\Household;
use App\Citizen\Domain\Entity\Citizen;

class HouseholdTest extends TestCase
{
    private function createCitizenMock(int $id, ?bool $isElderly = null, ?bool $isChild = null): \PHPUnit\Framework\MockObject\MockObject&Citizen
    {
        $citizen = $this->createMock(Citizen::class);
        $citizen->method('getId')->willReturn((string)$id);
        if ($isElderly !== null) {
            $citizen->method('isElderly')->willReturn($isElderly);
        }
        if ($isChild !== null) {
            $citizen->method('isChild')->willReturn($isChild);
        }
        // For setHousehold and getHousehold
        $citizen->method('getHousehold')->willReturn(null);
        $citizen->expects($this->any())->method('setHousehold')->with($this->anything());
        return $citizen;
    }

    public function testConstructorInitializesProperties(): void
    {
        $householdNumber = 'HH-001';
        $address = '123 Main St';
        $ward = 'Ward 1';
        $district = 'District 1';
        $province = 'Province A';

        $household = new Household($householdNumber, $address, $ward, $district, $province);

        $this->assertIsString($household->getId());
        $this->assertSame(36, strlen($household->getId())); // UUID
        $this->assertSame($householdNumber, $household->getHouseholdNumber());
        $this->assertSame($address, $household->getAddress());
        $this->assertSame($ward, $household->getWard());
        $this->assertSame($district, $household->getDistrict());
        $this->assertSame($province, $household->getProvince());
        $this->assertSame([], $household->getEconomicConditions());
        $this->assertSame([], $household->getHousingConditions());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $household->getMembers());
        $this->assertSame(0, $household->getMembers()->count());
        $this->assertNull($household->getHouseholdHead());
        $this->assertNull($household->getPhoneNumber());
        $this->assertNull($household->getHouseholdType());
        $this->assertInstanceOf(\DateTimeInterface::class, $household->getRegistrationDate());
    }

    public function testSetters(): void
    {
        $household = new Household('1', 'addr', 'w', 'd', 'p');
        $household->setHouseholdNumber('HH-002');
        $this->assertSame('HH-002', $household->getHouseholdNumber());

        $household->setAddress('456 St');
        $this->assertSame('456 St', $household->getAddress());

        $household->setWard('Ward 2');
        $this->assertSame('Ward 2', $household->getWard());

        $household->setDistrict('District 2');
        $this->assertSame('District 2', $household->getDistrict());

        $household->setProvince('Province B');
        $this->assertSame('Province B', $household->getProvince());

        $household->setPhoneNumber('0123456789');
        $this->assertSame('0123456789', $household->getPhoneNumber());

        $household->setHouseholdType('NUCLEAR');
        $this->assertSame('NUCLEAR', $household->getHouseholdType());

        $date = new \DateTime();
        $household->setRegistrationDate($date);
        $this->assertSame($date, $household->getRegistrationDate());

        $household->setEconomicConditions(['income' => 50000]);
        $this->assertSame(['income' => 50000], $household->getEconomicConditions());

        $household->setHousingConditions(['type' => 'apartment']);
        $this->assertSame(['type' => 'apartment'], $household->getHousingConditions());

        $household->setNotes('Some notes');
        $this->assertSame('Some notes', $household->getNotes());
    }

    public function testGetFullAddress(): void
    {
        $household = new Household('1', '123 Main', 'W1', 'D1', 'P1');
        $expected = '123 Main, W1, D1, P1';
        $this->assertSame($expected, $household->getFullAddress());
    }

    public function testMembersManagement(): void
    {
        $household = new Household('1', 'addr', 'w', 'd', 'p');
        $this->assertSame(0, $household->getMemberCount());

        $citizen1 = $this->createCitizenMock(1);
        $household->addMember($citizen1);
        $this->assertSame(1, $household->getMemberCount());
        $this->assertTrue($household->getMembers()->contains($citizen1));

        // Adding same member again should not duplicate
        $household->addMember($citizen1);
        $this->assertSame(1, $household->getMemberCount());

        $citizen2 = $this->createCitizenMock(2);
        $household->addMember($citizen2);
        $this->assertSame(2, $household->getMemberCount());

        // Remove member
        $household->removeMember($citizen1);
        $this->assertSame(1, $household->getMemberCount());
        $this->assertFalse($household->getMembers()->contains($citizen1));
    }

    public function testElderlyAndChildCounts(): void
    {
        $household = new Household('1', 'addr', 'w', 'd', 'p');

        $young = $this->createCitizenMock(1, false, true); // child
        $adult = $this->createCitizenMock(2, false, false);
        $elder = $this->createCitizenMock(3, true, false); // elderly

        $household->addMember($young);
        $household->addMember($adult);
        $household->addMember($elder);

        $this->assertSame(3, $household->getMemberCount());
        $this->assertSame(1, $household->getChildCount());
        $this->assertSame(1, $household->getElderlyCount());
    }

    public function testPovertyStatus(): void
    {
        $household = new Household('1', 'addr', 'w', 'd', 'p');

        // Default empty -> Non-poor
        $this->assertSame('Non-poor', $household->getPovertyStatus());

        $household->setEconomicConditions(['isPoorHousehold' => true]);
        $this->assertSame('Poor', $household->getPovertyStatus());

        $household->setEconomicConditions(['isPoorHousehold' => true, 'povertyLevel' => 'Very Poor']);
        $this->assertSame('Very Poor', $household->getPovertyStatus());

        $household->setEconomicConditions(['isPoorHousehold' => false]);
        $this->assertSame('Non-poor', $household->getPovertyStatus());
    }

    public function testHouseholdHead(): void
    {
        $household = new Household('1', 'addr', 'w', 'd', 'p');
        $this->assertNull($household->getHouseholdHead());

        $head = $this->createCitizenMock(100);
        $household->setHouseholdHead($head);
        $this->assertSame($head, $household->getHouseholdHead());
    }
}
