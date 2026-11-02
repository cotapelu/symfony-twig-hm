<?php declare(strict_types=1);

namespace App\Tests\Unit\Business\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Business\Entity\Business;
use App\Business\Entity\BusinessCatalog;
use App\Core\Entity\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessCatalogTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setPasswordHash('hashed');
        return $user;
    }

    private function createBusiness(?User $owner = null, ?string $name = null): Business
    {
        return new Business(
            owner: $owner ?? $this->createUser(),
            name: $name ?? 'My Business'
        );
    }

    private function createCatalog(?Business $business = null, ?string $name = null): BusinessCatalog
    {
        return new BusinessCatalog(
            business: $business ?? $this->createBusiness(),
            name: $name ?? 'Catalog Item'
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $catalog = $this->createCatalog();
        $this->assertInstanceOf(Business::class, $catalog->getBusiness());
        $this->assertSame('Catalog Item', $catalog->getName());
        $this->assertNull($catalog->getCategory());
        $this->assertNull($catalog->getDescription());
        $this->assertNull($catalog->getPriceCents());
        $this->assertSame(0, $catalog->getStock());
        $this->assertNull($catalog->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $catalog->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $catalog->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $catalog = $this->createCatalog();

        $catalog->setName('New Item');
        $this->assertSame('New Item', $catalog->getName());

        $catalog->setCategory('electronics');
        $this->assertSame('electronics', $catalog->getCategory());

        $catalog->setDescription('A great product');
        $this->assertSame('A great product', $catalog->getDescription());

        $catalog->setPriceCents(1999);
        $this->assertSame(1999, $catalog->getPriceCents());

        $catalog->setPriceCents(null);
        $this->assertNull($catalog->getPriceCents());

        $catalog->setStock(50);
        $this->assertSame(50, $catalog->getStock());

        $catalog->setMetadata(['size' => 'M']);
        $this->assertSame(['size' => 'M'], $catalog->getMetadata());
    }

    public function testToArray(): void
    {
        $business = $this->createBusiness();
        // We need business ID; generate one via reflection if not set? Business id is generated in constructor (string uuid). So OK.
        $catalog = new BusinessCatalog($business, 'Test Catalog');
        // Simulate DB-assigned integer ID
        $ref = new \ReflectionProperty($catalog, 'id');
        $ref->setAccessible(true);
        $ref->setValue($catalog, 123);
        $catalog->setCategory('books');
        $catalog->setPriceCents(2500);
        $catalog->setStock(10);
        $catalog->setMetadata(['author' => 'John']);

        $arr = $catalog->toArray();
        $this->assertSame(123, $arr['id']);
        $this->assertSame($business->getId(), $arr['businessId']);
        $this->assertSame('Test Catalog', $arr['name']);
        $this->assertSame('books', $arr['category']);
        $this->assertNull($arr['description']);
        $this->assertSame(2500, $arr['priceCents']);
        $this->assertSame(10, $arr['stock']);
        $this->assertSame(['author' => 'John'], $arr['metadata']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['updatedAt']);
    }
}
