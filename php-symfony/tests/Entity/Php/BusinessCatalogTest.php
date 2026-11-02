<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\BusinessCatalog;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessCatalogTest extends TestCase
{
    private function createCatalog(
        string $id = 'catalog-id',
        string $businessId = 'business-1',
        string $name = 'Menu',
        string $category = 'Food'
    ): BusinessCatalog {
        return new BusinessCatalog($id, $businessId, $name, $category);
    }

    public function testConstructor(): void
    {
        $c = $this->createCatalog();
        $this->assertSame('catalog-id', $c->getId());
        $this->assertSame('business-1', $c->getBusinessId());
        $this->assertSame('Menu', $c->getName());
        $this->assertNull($c->getDescription());
        $this->assertSame('Food', $c->getCategory());
        $this->assertSame('ACTIVE', $c->getStatus());
        $this->assertNull($c->getItems());
        $this->assertNull($c->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $c->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $c->getUpdatedAt());
        $this->assertTrue($c->isActive());
        $this->assertFalse($c->isInactive());
        $this->assertFalse($c->hasItems());
        $this->assertSame(0, $c->getItemCount());
    }

    public function testSetBusinessId(): void
    {
        $c = $this->createCatalog();
        $original = $c->getUpdatedAt();
        usleep(1000);
        $c->setBusinessId('business-2');
        $this->assertSame('business-2', $c->getBusinessId());
        $this->assertNotEquals($original, $c->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $c = $this->createCatalog();
        $original = $c->getUpdatedAt();
        usleep(1000);
        $c->setName('Products');
        $this->assertSame('Products', $c->getName());
        $this->assertNotEquals($original, $c->getUpdatedAt());
    }

    public function testSetDescription(): void
    {
        $c = $this->createCatalog();
        $original = $c->getUpdatedAt();
        usleep(1000);
        $c->setDescription('All items available');
        $this->assertSame('All items available', $c->getDescription());
        $this->assertNotEquals($original, $c->getUpdatedAt());
        $c->setDescription(null);
        $this->assertNull($c->getDescription());
    }

    public function testSetCategory(): void
    {
        $c = $this->createCatalog();
        $original = $c->getUpdatedAt();
        usleep(1000);
        $c->setCategory('Drinks');
        $this->assertSame('Drinks', $c->getCategory());
        $this->assertNotEquals($original, $c->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $c = $this->createCatalog();
        $original = $c->getUpdatedAt();
        usleep(1000);
        $c->setStatus('INACTIVE');
        $this->assertSame('INACTIVE', $c->getStatus());
        $this->assertTrue($c->isInactive());
        $this->assertFalse($c->isActive());
        $this->assertNotEquals($original, $c->getUpdatedAt());
    }

    public function testSetItems(): void
    {
        $c = $this->createCatalog();
        $original = $c->getUpdatedAt();
        usleep(1000);
        $items = ['item1', 'item2', 'item3'];
        $c->setItems($items);
        $this->assertSame($items, $c->getItems());
        $this->assertTrue($c->hasItems());
        $this->assertSame(3, $c->getItemCount());
        $this->assertNotEquals($original, $c->getUpdatedAt());

        $c->setItems([]);
        $this->assertFalse($c->hasItems());
        $this->assertSame(0, $c->getItemCount());

        $c->setItems(null);
        $this->assertFalse($c->hasItems());
        $this->assertSame(0, $c->getItemCount());
    }

    public function testSetMetadata(): void
    {
        $c = $this->createCatalog();
        $original = $c->getUpdatedAt();
        usleep(1000);
        $meta = ['lang' => 'en'];
        $c->setMetadata($meta);
        $this->assertSame($meta, $c->getMetadata());
        $this->assertNotEquals($original, $c->getUpdatedAt());
        $c->setMetadata(null);
        $this->assertNull($c->getMetadata());
    }

    public function testGetItemCountWithNull(): void
    {
        $c = $this->createCatalog();
        $c->setItems(null);
        $this->assertSame(0, $c->getItemCount());
    }
}
