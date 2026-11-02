<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\CatalogItem;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CatalogItemTest extends TestCase
{
    private function createCatalogItem(array $overrides = []): CatalogItem
    {
        $item = new CatalogItem();
        // Required non-null fields
        $item->setBusiness($this->createMock(Business::class));
        $item->setName('Product');
        $item->setPrice(19.99);
        $item->setCategory('General');
        // Apply overrides
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . $prop;
            if (method_exists($item, $setter)) {
                $item->$setter($value);
            }
        }
        return $item;
    }

    public function testConstructorInitializesCreatedAt(): void
    {
        $item = $this->createCatalogItem([]);
        $this->assertInstanceOf(\DateTime::class, $item->getCreatedAt());
    }

    public function testDefaultValues(): void
    {
        $item = $this->createCatalogItem([]);
        $this->assertNull($item->getDescription());
        $this->assertNull($item->getCurrency());
        $this->assertNull($item->getImageUrl());
        $this->assertNull($item->getSku());
        $this->assertNull($item->getStock());
        $this->assertNull($item->getStatus());
        $this->assertTrue($item->isAvailable());
        $this->assertSame(0, $item->getViewCount());
        $this->assertSame(0, $item->getOrderCount());
    }

    public function testGettersAndSetters(): void
    {
        $item = $this->createCatalogItem([]);

        $item->setDescription('Desc');
        $this->assertSame('Desc', $item->getDescription());

        $item->setPrice(29.99);
        $this->assertSame(29.99, $item->getPrice());

        $item->setCurrency('USD');
        $this->assertSame('USD', $item->getCurrency());

        $item->setCategory('Electronics');
        $this->assertSame('Electronics', $item->getCategory());

        $item->setImageUrl('image.jpg');
        $this->assertSame('image.jpg', $item->getImageUrl());

        $item->setSku('SKU123');
        $this->assertSame('SKU123', $item->getSku());

        $item->setStock(100);
        $this->assertSame(100, $item->getStock());

        $item->setStatus('in_stock');
        $this->assertSame('in_stock', $item->getStatus());

        $item->setAvailable(false);
        $this->assertFalse($item->isAvailable());

        $item->setViewCount(5);
        $this->assertSame(5, $item->getViewCount());

        $item->setOrderCount(2);
        $this->assertSame(2, $item->getOrderCount());
    }
}
