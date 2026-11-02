<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccounts\Entity;

use App\BusinessAccounts\Entity\BusinessAccount;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\BusinessAccounts\Entity\BusinessAccount
 */
class BusinessAccountTest extends TestCase
{
    public function testConstructorSetsRequiredProperties(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');

        $this->assertNull($account->getId());
        $this->assertEquals(123, $account->getUserId());
        $this->assertEquals('My Business', $account->getBusinessName());
        $this->assertEquals('retail', $account->getBusinessType());
        $this->assertFalse($account->isVerified());
        $this->assertNull($account->getTaxId());
        $this->assertNull($account->getAddress());
        $this->assertNull($account->getPhone());
        $this->assertNull($account->getEmail());
        $this->assertNull($account->getWhatsappBusinessId());
    }

    public function testSetId(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(456);

        $this->assertEquals(456, $account->getId());
    }

    public function testSetTaxId(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setTaxId('123-456-789');

        $this->assertEquals('123-456-789', $account->getTaxId());
    }

    public function testSetAddress(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setAddress('123 Main St, City');

        $this->assertEquals('123 Main St, City', $account->getAddress());
    }

    public function testSetPhone(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setPhone('+849012345678');

        $this->assertEquals('+849012345678', $account->getPhone());
    }

    public function testSetEmail(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setEmail('contact@business.com');

        $this->assertEquals('contact@business.com', $account->getEmail());
    }

    public function testSetWhatsappBusinessId(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setWhatsappBusinessId('whatsapp_id_123');

        $this->assertEquals('whatsapp_id_123', $account->getWhatsappBusinessId());
    }

    public function testSetVerified(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $this->assertFalse($account->isVerified());

        $account->setVerified(true);
        $this->assertTrue($account->isVerified());
    }

    public function testMultipleSettersAreChainable(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');

        $result = $account
            ->setTaxId('123-456-789')
            ->setAddress('123 Main St')
            ->setPhone('+849012345678')
            ->setEmail('contact@business.com')
            ->setVerified(true);

        $this->assertSame($account, $result);
        $this->assertEquals('123-456-789', $account->getTaxId());
        $this->assertEquals('123 Main St', $account->getAddress());
        $this->assertEquals('+849012345678', $account->getPhone());
        $this->assertEquals('contact@business.com', $account->getEmail());
        $this->assertTrue($account->isVerified());
    }

    public function testBusinessTypesAreFlexible(): void
    {
        $retail = new BusinessAccount(1, 'Shop', 'retail');
        $restaurant = new BusinessAccount(2, 'Restaurant', 'restaurant');
        $service = new BusinessAccount(3, 'Service Co', 'service');

        $this->assertEquals('retail', $retail->getBusinessType());
        $this->assertEquals('restaurant', $restaurant->getBusinessType());
        $this->assertEquals('service', $service->getBusinessType());
    }
}
