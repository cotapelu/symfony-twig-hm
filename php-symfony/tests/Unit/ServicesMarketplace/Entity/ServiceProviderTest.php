<?php declare(strict_types=1);

namespace App\Tests\Unit\ServicesMarketplace\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ServicesMarketplace\Entity\ServiceProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServiceProviderTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $sp = new ServiceProvider();
        $this->assertInstanceOf(\DateTime::class, $sp->getCreatedAt());
    }

    public function testGettersSetters(): void
    {
        $sp = new ServiceProvider();

        $sp->setUserId('user-123');
        $this->assertSame('user-123', $sp->getUserId());

        $sp->setBusinessName('My Business');
        $this->assertSame('My Business', $sp->getBusinessName());

        $sp->setDisplayName('Display Name');
        $this->assertSame('Display Name', $sp->getDisplayName());

        $sp->setDescription('A description');
        $this->assertSame('A description', $sp->getDescription());

        $sp->setCategory('Category1');
        $this->assertSame('Category1', $sp->getCategory());

        $sp->setSubcategory('Subcat');
        $this->assertSame('Subcat', $sp->getSubcategory());

        $sp->setAddress('123 Main St');
        $this->assertSame('123 Main St', $sp->getAddress());

        $sp->setLatitude(10.123456);
        $this->assertSame(10.123456, $sp->getLatitude());

        $sp->setLongitude(20.654321);
        $this->assertSame(20.654321, $sp->getLongitude());

        $sp->setPhone('+1234567890');
        $this->assertSame('+1234567890', $sp->getPhone());

        $sp->setPhone(null);
        $this->assertNull($sp->getPhone());

        $sp->setImageUrl('https://example.com/image.jpg');
        $this->assertSame('https://example.com/image.jpg', $sp->getImageUrl());

        $sp->setRating(4.5);
        $this->assertSame(4.5, $sp->getRating());

        $sp->setReviewCount(100);
        $this->assertSame(100, $sp->getReviewCount());

        $sp->setStartingPrice(99.99);
        $this->assertSame(99.99, $sp->getStartingPrice());

        $sp->setStatus('active');
        $this->assertSame('active', $sp->getStatus());

        $sp->setVerified(true);
        $this->assertTrue($sp->isVerified());

        $sp->setCompletedJobs(250);
        $this->assertSame(250, $sp->getCompletedJobs());

        $updated = new \DateTime('2025-01-01');
        $sp->setUpdatedAt($updated);
        $this->assertSame($updated, $sp->getUpdatedAt());

        $sp->setUpdatedAt(null);
        $this->assertNull($sp->getUpdatedAt());
    }

    public function testDefaultValues(): void
    {
        $sp = new ServiceProvider();
        $this->assertSame(0.0, $sp->getRating());
        $this->assertSame(0, $sp->getReviewCount());
        $this->assertSame(0.0, $sp->getStartingPrice());
        $this->assertSame('pending', $sp->getStatus());
        $this->assertFalse($sp->isVerified());
        $this->assertSame(0, $sp->getCompletedJobs());
    }

    public function testIdSetAndGet(): void
    {
        $sp = new ServiceProvider();
        $ref = new \ReflectionProperty($sp, 'id');
        $ref->setAccessible(true);
        $ref->setValue($sp, 42);
        $this->assertSame(42, $sp->getId());
    }
}
