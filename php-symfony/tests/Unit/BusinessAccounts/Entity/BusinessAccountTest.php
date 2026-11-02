<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccounts\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccounts\Entity\BusinessAccount;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessAccountTest extends TestCase
{
    private function createAccount(?int $userId = null, ?string $name = null, ?string $type = null): BusinessAccount
    {
        return new BusinessAccount(
            userId: $userId ?? 100,
            businessName: $name ?? 'My Business',
            businessType: $type ?? 'retail'
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $account = $this->createAccount();
        $this->assertNull($account->getId());
        $this->assertSame(100, $account->getUserId());
        $this->assertSame('My Business', $account->getBusinessName());
        $this->assertSame('retail', $account->getBusinessType());
        $this->assertNull($account->getTaxId());
        $this->assertNull($account->getAddress());
        $this->assertNull($account->getPhone());
        $this->assertNull($account->getEmail());
        $this->assertNull($account->getWhatsappBusinessId());
        $this->assertFalse($account->isVerified());
        $this->assertInstanceOf(\DateTime::class, $account->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $account->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $account = $this->createAccount();

        $account->setUserId(200);
        $this->assertSame(200, $account->getUserId());

        $account->setBusinessName('New Name');
        $this->assertSame('New Name', $account->getBusinessName());

        $account->setBusinessType('service');
        $this->assertSame('service', $account->getBusinessType());

        $account->setTaxId('TAX-123');
        $this->assertSame('TAX-123', $account->getTaxId());

        $account->setAddress('123 Main St');
        $this->assertSame('123 Main St', $account->getAddress());

        $account->setPhone('+1 555 1234');
        $this->assertSame('+1 555 1234', $account->getPhone());

        $account->setEmail('contact@business.com');
        $this->assertSame('contact@business.com', $account->getEmail());

        $account->setWhatsappBusinessId('whatsapp-id-456');
        $this->assertSame('whatsapp-id-456', $account->getWhatsappBusinessId());

        $account->setVerified(true);
        $this->assertTrue($account->isVerified());

        $now = new \DateTime();
        $account->setCreatedAt($now);
        $this->assertSame($now, $account->getCreatedAt());

        $updated = new \DateTime();
        $account->setUpdatedAt($updated);
        $this->assertSame($updated, $account->getUpdatedAt());
    }

    public function testIdSetter(): void
    {
        $account = $this->createAccount();
        $this->assertNull($account->getId());
        $account->setId(10);
        $this->assertSame(10, $account->getId());
    }
}
