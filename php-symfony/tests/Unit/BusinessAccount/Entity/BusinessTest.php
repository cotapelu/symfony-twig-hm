<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\Business;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessTest extends TestCase
{
    private function createBusiness(array $overrides = []): Business
    {
        // Business has no required constructor args; but ownerId and name are required fields but can be set after construction.
        $business = new Business();
        // Set required fields that are not nullable
        $business->setOwnerId('owner-123');
        $business->setName('My Business');
        $business->setCategory('General');
        // Apply overrides
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . $prop;
            if (method_exists($business, $setter)) {
                $business->$setter($value);
            }
        }
        return $business;
    }

    public function testConstructorInitializesCreatedAt(): void
    {
        $business = $this->createBusiness();
        $this->assertInstanceOf(\DateTime::class, $business->getCreatedAt());
    }

    public function testDefaultValues(): void
    {
        $business = $this->createBusiness();
        $this->assertSame('pending', $business->getStatus());
        $this->assertSame('basic', $business->getSubscription());
        $this->assertFalse($business->isVerified());
        $this->assertSame(1, $business->getTotalEmployees());
        $this->assertSame(0, $business->getTotalCatalogItems());
        $this->assertSame(0, $business->getTotalMessages());
        $this->assertNull($business->getDisplayName());
        $this->assertNull($business->getDescription());
        $this->assertNull($business->getSubcategory());
        $this->assertNull($business->getLogoUrl());
        $this->assertNull($business->getCoverUrl());
        $this->assertNull($business->getAddress());
        $this->assertNull($business->getPhone());
        $this->assertNull($business->getEmail());
        $this->assertNull($business->getWebsite());
        $this->assertNull($business->getSubscriptionExpiresAt());
        $this->assertNull($business->getBusinessHours());
        $this->assertNull($business->getAutoReplyMessage());
        $this->assertNull($business->getUpdatedAt());
        // Additional fields
        $this->assertNull($business->getLatitude());
        $this->assertNull($business->getLongitude());
        $this->assertNull($business->getInstagram());
        $this->assertNull($business->getFacebook());
        $this->assertNull($business->getPassword());
        $this->assertNull($business->getLogo());
        $this->assertNull($business->getCoverImage());
        $this->assertTrue($business->isActive());
    }

    public function testGettersAndSetters(): void
    {
        $business = $this->createBusiness();

        $business->setDisplayName('Display');
        $this->assertSame('Display', $business->getDisplayName());

        $business->setDescription('Desc');
        $this->assertSame('Desc', $business->getDescription());

        $business->setCategory('Cat');
        $this->assertSame('Cat', $business->getCategory());

        $business->setSubcategory('Sub');
        $this->assertSame('Sub', $business->getSubcategory());

        $business->setLogoUrl('logo.jpg');
        $this->assertSame('logo.jpg', $business->getLogoUrl());

        $business->setCoverUrl('cover.jpg');
        $this->assertSame('cover.jpg', $business->getCoverUrl());

        $business->setAddress('Addr');
        $this->assertSame('Addr', $business->getAddress());

        $business->setPhone('1234567890');
        $this->assertSame('1234567890', $business->getPhone());

        $business->setEmail('email@example.com');
        $this->assertSame('email@example.com', $business->getEmail());

        $business->setWebsite('https://example.com');
        $this->assertSame('https://example.com', $business->getWebsite());

        $business->setStatus('active');
        $this->assertSame('active', $business->getStatus());

        $business->setSubscription('premium');
        $this->assertSame('premium', $business->getSubscription());

        $date = new \DateTime('2025-01-01');
        $business->setSubscriptionExpiresAt($date);
        $this->assertSame($date, $business->getSubscriptionExpiresAt());

        $business->setVerified(true);
        $this->assertTrue($business->isVerified());

        $business->setBusinessHours('9-5');
        $this->assertSame('9-5', $business->getBusinessHours());

        $business->setAutoReplyMessage('Auto');
        $this->assertSame('Auto', $business->getAutoReplyMessage());

        $business->setTotalEmployees(10);
        $this->assertSame(10, $business->getTotalEmployees());

        $business->setTotalCatalogItems(5);
        $this->assertSame(5, $business->getTotalCatalogItems());

        $business->setTotalMessages(3);
        $this->assertSame(3, $business->getTotalMessages());

        $business->setUpdatedAt($date);
        $this->assertSame($date, $business->getUpdatedAt());

        // Additional fields
        $business->setLatitude(12.34);
        $this->assertSame(12.34, $business->getLatitude());

        $business->setLongitude(56.78);
        $this->assertSame(56.78, $business->getLongitude());

        $business->setInstagram('@insta');
        $this->assertSame('@insta', $business->getInstagram());

        $business->setFacebook('@fb');
        $this->assertSame('@fb', $business->getFacebook());

        $business->setPassword('pass');
        $this->assertSame('pass', $business->getPassword());

        $business->setLogo('logo.png');
        $this->assertSame('logo.png', $business->getLogo());

        $business->setCoverImage('cover.png');
        $this->assertSame('cover.png', $business->getCoverImage());

        $business->setIsActive(false);
        $this->assertFalse($business->isActive());
    }

    public function testUserInterfaceMethods(): void
    {
        $business = $this->createBusiness();
        $this->assertSame(['ROLE_BUSINESS'], $business->getRoles());
        $this->assertSame('owner-123', $business->getUserIdentifier());
        // eraseCredentials does nothing
        $this->assertNull($business->eraseCredentials());
    }
}
