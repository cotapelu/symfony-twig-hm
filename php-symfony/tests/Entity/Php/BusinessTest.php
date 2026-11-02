<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\Business;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessTest extends TestCase
{
    private function createBusiness(
        string $id = 'business-id',
        string $ownerId = 'owner-1',
        string $name = 'My Business',
        string $type = 'LLC'
    ): Business {
        return new Business($id, $ownerId, $name, $type);
    }

    public function testConstructor(): void
    {
        $b = $this->createBusiness();
        $this->assertSame('business-id', $b->getId());
        $this->assertSame('owner-1', $b->getOwnerId());
        $this->assertSame('My Business', $b->getName());
        $this->assertNull($b->getAddress());
        $this->assertNull($b->getPhone());
        $this->assertNull($b->getEmail());
        $this->assertNull($b->getTaxCode());
        $this->assertSame('LLC', $b->getType());
        $this->assertSame('ACTIVE', $b->getStatus());
        $this->assertNull($b->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $b->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $b->getUpdatedAt());
    }

    public function testSetOwnerId(): void
    {
        $b = $this->createBusiness();
        $originalUpdatedAt = $b->getUpdatedAt();
        usleep(1000);
        $b->setOwnerId('new-owner');
        $this->assertSame('new-owner', $b->getOwnerId());
        $this->assertNotEquals($originalUpdatedAt, $b->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $b = $this->createBusiness();
        $originalUpdatedAt = $b->getUpdatedAt();
        usleep(1000);
        $b->setName('New Name');
        $this->assertSame('New Name', $b->getName());
        $this->assertNotEquals($originalUpdatedAt, $b->getUpdatedAt());
    }

    public function testSetAddress(): void
    {
        $b = $this->createBusiness();
        $originalUpdatedAt = $b->getUpdatedAt();
        usleep(1000);
        $b->setAddress('123 Business Rd');
        $this->assertSame('123 Business Rd', $b->getAddress());
        $this->assertNotEquals($originalUpdatedAt, $b->getUpdatedAt());
        $b->setAddress(null);
        $this->assertNull($b->getAddress());
    }

    public function testSetPhone(): void
    {
        $b = $this->createBusiness();
        $originalUpdatedAt = $b->getUpdatedAt();
        usleep(1000);
        $b->setPhone('0987654321');
        $this->assertSame('0987654321', $b->getPhone());
        $this->assertNotEquals($originalUpdatedAt, $b->getUpdatedAt());
        $b->setPhone(null);
        $this->assertNull($b->getPhone());
    }

    public function testSetEmail(): void
    {
        $b = $this->createBusiness();
        $originalUpdatedAt = $b->getUpdatedAt();
        usleep(1000);
        $b->setEmail('contact@example.com');
        $this->assertSame('contact@example.com', $b->getEmail());
        $this->assertNotEquals($originalUpdatedAt, $b->getUpdatedAt());
        $b->setEmail(null);
        $this->assertNull($b->getEmail());
    }

    public function testSetTaxCode(): void
    {
        $b = $this->createBusiness();
        $originalUpdatedAt = $b->getUpdatedAt();
        usleep(1000);
        $b->setTaxCode('TAX123');
        $this->assertSame('TAX123', $b->getTaxCode());
        $this->assertNotEquals($originalUpdatedAt, $b->getUpdatedAt());
        $b->setTaxCode(null);
        $this->assertNull($b->getTaxCode());
    }

    public function testSetType(): void
    {
        $b = $this->createBusiness();
        $originalUpdatedAt = $b->getUpdatedAt();
        usleep(1000);
        $b->setType('Corporation');
        $this->assertSame('Corporation', $b->getType());
        $this->assertNotEquals($originalUpdatedAt, $b->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $b = $this->createBusiness();
        $originalUpdatedAt = $b->getUpdatedAt();
        usleep(1000);
        $b->setStatus('INACTIVE');
        $this->assertSame('INACTIVE', $b->getStatus());
        $this->assertNotEquals($originalUpdatedAt, $b->getUpdatedAt());
    }

    public function testSetMetadata(): void
    {
        $b = $this->createBusiness();
        $originalUpdatedAt = $b->getUpdatedAt();
        usleep(1000);
        $meta = ['category' => 'retail', 'employees' => 10];
        $b->setMetadata($meta);
        $this->assertSame($meta, $b->getMetadata());
        $this->assertNotEquals($originalUpdatedAt, $b->getUpdatedAt());
        $b->setMetadata(null);
        $this->assertNull($b->getMetadata());
    }
}
