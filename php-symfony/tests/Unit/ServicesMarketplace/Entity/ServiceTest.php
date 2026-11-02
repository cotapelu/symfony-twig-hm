<?php declare(strict_types=1);

namespace App\Tests\Unit\ServicesMarketplace\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ServicesMarketplace\Entity\Service;
use App\ServicesMarketplace\Entity\ServiceProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServiceTest extends TestCase
{
    private function createMockProvider(): MockObject&ServiceProvider
    {
        $provider = $this->createMock(ServiceProvider::class);
        $provider->method('getId')->willReturn(1);
        return $provider;
    }

    public function testConstructorInitializesCreatedAt(): void
    {
        $service = new Service();
        $this->assertInstanceOf(\DateTime::class, $service->getCreatedAt());
    }

    public function testGettersSetters(): void
    {
        $provider = $this->createMockProvider();
        $service = new Service();

        $service->setProvider($provider);
        $this->assertSame($provider, $service->getProvider());

        $service->setName('Cleaning Service');
        $this->assertSame('Cleaning Service', $service->getName());

        $service->setDescription('Home cleaning');
        $this->assertSame('Home cleaning', $service->getDescription());

        $service->setCategory('Home');
        $this->assertSame('Home', $service->getCategory());

        $service->setSubcategory('Cleaning');
        $this->assertSame('Cleaning', $service->getSubcategory());

        $service->setPrice(50.00);
        $this->assertSame(50.00, $service->getPrice());

        $service->setPriceType('hourly');
        $this->assertSame('hourly', $service->getPriceType());

        $service->setEstimatedDuration(120);
        $this->assertSame(120, $service->getEstimatedDuration());

        $service->setIncludes('Detergents');
        $this->assertSame('Detergents', $service->getIncludes());

        $service->setExcludes('Heavy lifting');
        $this->assertSame('Heavy lifting', $service->getExcludes());

        $service->setImageUrl('https://example.com/service.jpg');
        $this->assertSame('https://example.com/service.jpg', $service->getImageUrl());

        $service->setActive(false);
        $this->assertFalse($service->isActive());

        $service->setBookedCount(15);
        $this->assertSame(15, $service->getBookedCount());
    }

    public function testDefaultValues(): void
    {
        $service = new Service();
        $this->assertSame('fixed', $service->getPriceType());
        $this->assertTrue($service->isActive());
        $this->assertSame(0, $service->getBookedCount());
    }

    public function testIdSetAndGet(): void
    {
        $service = new Service();
        $ref = new \ReflectionProperty($service, 'id');
        $ref->setAccessible(true);
        $ref->setValue($service, 101);
        $this->assertSame(101, $service->getId());
    }
}
