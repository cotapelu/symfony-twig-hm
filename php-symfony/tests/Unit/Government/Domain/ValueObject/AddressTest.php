<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\ValueObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\ValueObject\Address;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AddressTest extends TestCase
{
    public function testConstructorValidationThrowsIfProvinceEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Province cannot be empty');
        new Address('', 'District', 'Ward', 'Street');
    }

    public function testConstructorValidationThrowsIfDistrictEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('District cannot be empty');
        new Address('Province', '', 'Ward', 'Street');
    }

    public function testConstructorValidationThrowsIfWardEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ward cannot be empty');
        new Address('Province', 'District', '', 'Street');
    }

    public function testConstructorValidationThrowsIfStreetEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Street cannot be empty');
        new Address('Province', 'District', 'Ward', '');
    }

    public function testConstructorWithValidData(): void
    {
        $addr = new Address('HCM', 'District 1', 'Ward 1', '123 Main St', 'Apt 4B');
        $this->assertSame('HCM', $addr->getProvince());
        $this->assertSame('District 1', $addr->getDistrict());
        $this->assertSame('Ward 1', $addr->getWard());
        $this->assertSame('123 Main St', $addr->getStreet());
        $this->assertSame('Apt 4B', $addr->getApartmentNumber());
        $expectedFull = 'Apt 4B, 123 Main St, Phường/Xã Ward 1, Quận/Huyện District 1, Tỉnh/TP HCM';
        $this->assertSame($expectedFull, $addr->getFullAddress());
        $this->assertSame($expectedFull, (string)$addr);
    }

    public function testFullAddressWithoutApartment(): void
    {
        $addr = new Address('HN', 'Ba Dinh', 'Ward A', '45 ABC');
        $expected = '45 ABC, Phường/Xã Ward A, Quận/Huyện Ba Dinh, Tỉnh/TP HN';
        $this->assertSame($expected, $addr->getFullAddress());
    }

    public function testEquals(): void
    {
        $addr1 = new Address('A', 'B', 'C', 'D', 'E');
        $addr2 = new Address('A', 'B', 'C', 'D', 'E');
        $addr3 = new Address('A', 'B', 'C', 'D', 'F');
        $this->assertTrue($addr1->equals($addr2));
        $this->assertFalse($addr1->equals($addr3));
    }

    public function testToArray(): void
    {
        $addr = new Address('X', 'Y', 'Z', 'Street 1', 'Apt 2');
        $arr = $addr->toArray();
        $this->assertIsArray($arr);
        $this->assertArrayHasKey('province', $arr);
        $this->assertArrayHasKey('district', $arr);
        $this->assertArrayHasKey('ward', $arr);
        $this->assertArrayHasKey('street', $arr);
        $this->assertArrayHasKey('apartmentNumber', $arr);
        $this->assertArrayHasKey('fullAddress', $arr);
        $this->assertSame('X', $arr['province']);
        $this->assertSame('Y', $arr['district']);
        $this->assertSame('Z', $arr['ward']);
        $this->assertSame('Street 1', $arr['street']);
        $this->assertSame('Apt 2', $arr['apartmentNumber']);
    }
}
