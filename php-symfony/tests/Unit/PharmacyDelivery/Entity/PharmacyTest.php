<?php declare(strict_types=1);

namespace App\Tests\Unit\PharmacyDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\PharmacyDelivery\Entity\Pharmacy;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PharmacyTest extends TestCase
{
    private function createPharmacy(array $overrides = []): Pharmacy
    {
        $pharmacy = new Pharmacy();
        // Set required fields
        $pharmacy->setName('Central Pharmacy');
        $pharmacy->setLicenseNumber('LIC-123');
        $pharmacy->setAddress('123 Main St');
        $pharmacy->setLatitude(40.7128);
        $pharmacy->setLongitude(-74.0060);
        $pharmacy->setPhone('+1 123 456 7890');
        $pharmacy->setEmail('pharmacy@example.com');
        $pharmacy->setOpeningTime('08:00');
        $pharmacy->setClosingTime('22:00');
        $pharmacy->setDeliveryFee(5.99);
        $pharmacy->setEstimatedDeliveryTime(30);
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . ucfirst($prop);
            if (method_exists($pharmacy, $setter)) {
                $pharmacy->$setter($value);
            }
        }
        return $pharmacy;
    }

    public function testConstructorInitializesCreatedAt(): void
    {
        $pharmacy = $this->createPharmacy([]);
        $this->assertInstanceOf(\DateTime::class, $pharmacy->getCreatedAt());
    }

    public function testDefaultValues(): void
    {
        $pharmacy = $this->createPharmacy([]);
        $this->assertSame(Pharmacy::STATUS_ACTIVE, $pharmacy->getStatus());
        $this->assertFalse($pharmacy->is24Hours());
        $this->assertTrue($pharmacy->isPrescriptionRequired());
        $this->assertSame(0.0, $pharmacy->getRating());
        $this->assertSame(0, $pharmacy->getTotalReviews());
    }

    public function testGettersAndSetters(): void
    {
        $pharmacy = $this->createPharmacy([]);

        $pharmacy->setName('New Pharmacy');
        $this->assertSame('New Pharmacy', $pharmacy->getName());

        $pharmacy->setLicenseNumber('LIC-999');
        $this->assertSame('LIC-999', $pharmacy->getLicenseNumber());

        $pharmacy->setAddress('456 Oak Ave');
        $this->assertSame('456 Oak Ave', $pharmacy->getAddress());

        $pharmacy->setLatitude(34.0522);
        $this->assertSame(34.0522, $pharmacy->getLatitude());

        $pharmacy->setLongitude(-118.2437);
        $this->assertSame(-118.2437, $pharmacy->getLongitude());

        $pharmacy->setPhone('+1 987 654 3210');
        $this->assertSame('+1 987 654 3210', $pharmacy->getPhone());

        $pharmacy->setEmail('info@pharmacy.com');
        $this->assertSame('info@pharmacy.com', $pharmacy->getEmail());

        $pharmacy->setOpeningTime('09:00');
        $this->assertSame('09:00', $pharmacy->getOpeningTime());

        $pharmacy->setClosingTime('23:00');
        $this->assertSame('23:00', $pharmacy->getClosingTime());

        $pharmacy->setDeliveryFee(7.99);
        $this->assertSame(7.99, $pharmacy->getDeliveryFee());

        $pharmacy->setEstimatedDeliveryTime(45);
        $this->assertSame(45, $pharmacy->getEstimatedDeliveryTime());

        $pharmacy->setStatus(Pharmacy::STATUS_BUSY);
        $this->assertSame(Pharmacy::STATUS_BUSY, $pharmacy->getStatus());

        $pharmacy->setIs24Hours(true);
        $this->assertTrue($pharmacy->is24Hours());

        $pharmacy->setPrescriptionRequired(false);
        $this->assertFalse($pharmacy->isPrescriptionRequired());

        $pharmacy->setRating(4.5);
        $this->assertSame(4.5, $pharmacy->getRating());

        $pharmacy->setTotalReviews(150);
        $this->assertSame(150, $pharmacy->getTotalReviews());
    }


}
