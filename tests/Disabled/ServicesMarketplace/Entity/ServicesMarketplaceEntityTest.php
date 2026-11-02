<?php declare(strict_types=1);

namespace App\Tests\Unit\ServicesMarketplace\Entity;

use App\ServicesMarketplace\Entity\ServiceProvider;
use PHPUnit\Framework\TestCase;

class ServicesMarketplaceEntityTest extends TestCase
{
    public function testServiceProviderCreation(): void
    {
        $provider = new ServiceProvider();
        $provider->setUserId('user-123');
        $provider->setBusinessName('Clean Home Services');
        $provider->setCategory('cleaning');
        $provider->setDescription('Professional cleaning services');
        $provider->setCity('Ho Chi Minh City');
        $provider->setPhone('+84912345678');
        $provider->setRating(4.5);
        $provider->setVerified(true);
        $provider->setStartingPrice(200000.00);

        $this->assertEquals('user-123', $provider->getUserId());
        $this->assertEquals('Clean Home Services', $provider->getBusinessName());
        $this->assertEquals('cleaning', $provider->getCategory());
        $this->assertEquals('Ho Chi Minh City', $provider->getCity());
        $this->assertEquals(4.5, $provider->getRating());
        $this->assertTrue($provider->isVerified());
    }

    public function testServiceProviderDefaultStatus(): void
    {
        $provider = new ServiceProvider();
        $this->assertEquals('active', $provider->getStatus());
    }

    public function testServiceProviderStatusChange(): void
    {
        $provider = new ServiceProvider();
        
        $provider->setStatus('active');
        $this->assertEquals('active', $provider->getStatus());
        
        $provider->setStatus('suspended');
        $this->assertEquals('suspended', $provider->getStatus());
    }

    public function testServiceProviderVerification(): void
    {
        $provider = new ServiceProvider();
        
        $this->assertFalse($provider->isVerified());
        
        $provider->setVerified(true);
        $this->assertTrue($provider->isVerified());
    }

    public function testServiceProviderRating(): void
    {
        $provider = new ServiceProvider();
        
        $this->assertEquals(0, $provider->getRating());
        
        $provider->setRating(4.5);
        $this->assertEquals(4.5, $provider->getRating());
    }
}
