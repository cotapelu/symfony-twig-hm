<?php declare(strict_types=1);

namespace App\Tests\Unit\PharmacyDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\PharmacyDelivery\Entity\Medicine;
use App\PharmacyDelivery\Entity\Pharmacy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MedicineTest extends TestCase
{
    private function createMockPharmacy(int $id = 1): MockObject&Pharmacy
    {
        $pharmacy = $this->createMock(Pharmacy::class);
        $pharmacy->method('getId')->willReturn($id);
        return $pharmacy;
    }

    private function createMedicine(): Medicine
    {
        return new Medicine();
    }

    public function testConstructorInitializesCreatedAt(): void
    {
        $medicine = $this->createMedicine();
        // id is generated; not accessible before persistence
        $this->assertInstanceOf(\DateTime::class, $medicine->getCreatedAt());
    }

    public function testPharmacyAssociation(): void
    {
        $medicine = $this->createMedicine();
        $pharmacy = $this->createMockPharmacy(10);
        $medicine->setPharmacy($pharmacy);
        $this->assertSame($pharmacy, $medicine->getPharmacy());
    }

    public function testBasicFields(): void
    {
        $medicine = $this->createMedicine();
        $medicine->setName('Paracetamol');
        $this->assertSame('Paracetamol', $medicine->getName());

        $medicine->setSku('PCM-500');
        $this->assertSame('PCM-500', $medicine->getSku());

        $medicine->setGenericName('Acetaminophen');
        $this->assertSame('Acetaminophen', $medicine->getGenericName());

        $medicine->setCategory(Medicine::CATEGORY_TABLET);
        $this->assertSame(Medicine::CATEGORY_TABLET, $medicine->getCategory());

        $medicine->setManufacturer('PharmaCo');
        $this->assertSame('PharmaCo', $medicine->getManufacturer());

        $medicine->setDosage('500mg');
        $this->assertSame('500mg', $medicine->getDosage());

        $medicine->setForm('Tablet');
        $this->assertSame('Tablet', $medicine->getForm());
    }

    public function testInventoryAndPricing(): void
    {
        $medicine = $this->createMedicine();
        $medicine->setQuantityInStock(100);
        $this->assertSame(100, $medicine->getQuantityInStock());

        $medicine->setPrice(9.99);
        $this->assertSame(9.99, $medicine->getPrice());

        $medicine->setOriginalPrice(12.99);
        $this->assertSame(12.99, $medicine->getOriginalPrice());
    }

    public function testOptionalFields(): void
    {
        $medicine = $this->createMedicine();
        $medicine->setDescription('Pain reliever');
        $this->assertSame('Pain reliever', $medicine->getDescription());

        $medicine->setUsageInstructions('Take one tablet daily');
        $this->assertSame('Take one tablet daily', $medicine->getUsageInstructions());

        $medicine->setSideEffects('Nausea, dizziness');
        $this->assertSame('Nausea, dizziness', $medicine->getSideEffects());

        $medicine->setContraindications('Not for liver disease');
        $this->assertSame('Not for liver disease', $medicine->getContraindications());

        $medicine->setImageUrl('https://example.com/image.jpg');
        $this->assertSame('https://example.com/image.jpg', $medicine->getImageUrl());

        // Nullify
        $medicine->setImageUrl(null);
        $this->assertNull($medicine->getImageUrl());
    }

    public function testRequiresPrescription(): void
    {
        $medicine = $this->createMedicine();
        // Set default to false
        $medicine->setRequiresPrescription(false);
        $this->assertFalse($medicine->requiresPrescription());
        $medicine->setRequiresPrescription(true);
        $this->assertTrue($medicine->requiresPrescription());
    }

    public function testIsAvailableLogic(): void
    {
        $medicine = $this->createMedicine();
        // isAvailable returns $this->isAvailable && $this->quantityInStock > 0.
        // Initially isAvailable default true, but quantityInStock uninitialized => error if called.
        // So we must set quantityInStock first.
        $medicine->setQuantityInStock(10);
        $this->assertTrue($medicine->isAvailable()); // isAvailable true by default and quantity >0

        $medicine->setIsAvailable(false);
        $this->assertFalse($medicine->isAvailable());

        $medicine->setIsAvailable(true);
        $medicine->setQuantityInStock(0);
        $this->assertFalse($medicine->isAvailable());
    }

    public function testToArray(): void
    {
        $pharmacy = $this->createMockPharmacy(5);
        $medicine = new Medicine();
        $medicine->setPharmacy($pharmacy);
        $medicine->setName('MediName');
        $medicine->setSku('SKU123');
        $medicine->setGenericName('GenName');
        $medicine->setCategory(Medicine::CATEGORY_CAPSULE);
        $medicine->setManufacturer('Manuf');
        $medicine->setDosage('250mg');
        $medicine->setForm('Capsule');
        $medicine->setQuantityInStock(50);
        $medicine->setPrice(5.00);
        $medicine->setOriginalPrice(7.00);
        $medicine->setDescription('Desc');
        $medicine->setUsageInstructions('Use twice daily');
        $medicine->setSideEffects('None');
        $medicine->setContraindications('Allergy');
        $medicine->setImageUrl('img.jpg');
        $medicine->setRequiresPrescription(true);
        $medicine->setIsAvailable(true);
        // Simulate id
        $ref = new \ReflectionProperty($medicine, 'id');
        $ref->setAccessible(true);
        $ref->setValue($medicine, 100);

        $arr = $medicine->toArray();
        $this->assertSame(100, $arr['id']);
        $this->assertSame(5, $arr['pharmacy_id']);
        $this->assertSame('MediName', $arr['name']);
        $this->assertSame('SKU123', $arr['sku']);
        $this->assertSame('GenName', $arr['generic_name']);
        $this->assertSame(Medicine::CATEGORY_CAPSULE, $arr['category']);
        $this->assertSame('Manuf', $arr['manufacturer']);
        $this->assertSame('250mg', $arr['dosage']);
        $this->assertSame('Capsule', $arr['form']);
        $this->assertSame(50, $arr['quantity_in_stock']);
        $this->assertSame(5.00, $arr['price']);
        $this->assertSame(7.00, $arr['original_price']);
        $this->assertSame('Desc', $arr['description']);
        $this->assertSame('Use twice daily', $arr['usage_instructions']);
        $this->assertSame('None', $arr['side_effects']);
        $this->assertSame('Allergy', $arr['contraindications']);
        $this->assertSame('img.jpg', $arr['image_url']);
        $this->assertTrue($arr['requires_prescription']);
        $this->assertTrue($arr['is_available']);
        // createdAt format
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $arr['created_at']);
    }
}
