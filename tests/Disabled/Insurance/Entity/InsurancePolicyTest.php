<?php declare(strict_types=1);

namespace App\Tests\Unit\Insurance\Entity;

use App\Insurance\Entity\InsurancePolicy;
use PHPUnit\Framework\TestCase;

class InsurancePolicyTest extends TestCase
{
    public function testConstructorSetsRequiredProperties(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        
        $this->assertNull($policy->getId());
        $this->assertEquals(1, $policy->getUserId());
        $this->assertEquals('health', $policy->getType());
        $this->assertEquals('Prudential', $policy->getProvider());
        $this->assertEquals('POL-001', $policy->getPolicyNumber());
        $this->assertEquals('VND', $policy->getCurrency());
        $this->assertEquals('pending', $policy->getStatus());
    }

    public function testSetCoverageAmount(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->setCoverageAmount(1000000);
        
        $this->assertEquals(1000000, $policy->getCoverageAmount());
    }

    public function testSetPremium(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->setPremium(50000);
        
        $this->assertEquals(50000, $policy->getPremium());
    }

    public function testSetEndDate(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $endDate = new \DateTime('+1 year');
        $policy->setEndDate($endDate);
        
        $this->assertEquals($endDate, $policy->getEndDate());
    }

    public function testSetBeneficiary(): void
    {
        $policy = new InsurancePolicy(1, 'life', 'Prudential', 'POL-001');
        $policy->setBeneficiary('John Doe');
        
        $this->assertEquals('John Doe', $policy->getBeneficiary());
    }

    public function testActivate(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $this->assertEquals('pending', $policy->getStatus());
        
        $policy->activate();
        
        $this->assertEquals('active', $policy->getStatus());
    }

    public function testCancel(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->activate();
        
        $policy->cancel();
        
        $this->assertEquals('cancelled', $policy->getStatus());
    }

    public function testIsExpiredWithEndDate(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->setEndDate(new \DateTime('-1 day'));
        
        $this->assertTrue($policy->isExpired());
    }

    public function testIsExpiredWithoutEndDate(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        
        $this->assertFalse($policy->isExpired());
    }

    public function testIsActiveWhenActiveAndNotExpired(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->setEndDate(new \DateTime('+1 year'));
        $policy->activate();
        
        $this->assertTrue($policy->isActive());
    }

    public function testIsActiveWhenCancelled(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->setEndDate(new \DateTime('+1 year'));
        $policy->activate();
        $policy->cancel();
        
        $this->assertFalse($policy->isActive());
    }

    public function testIsActiveWhenExpired(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->setEndDate(new \DateTime('-1 day'));
        $policy->activate();
        
        $this->assertFalse($policy->isActive());
    }

    public function testSetId(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->setId(42);
        
        $this->assertEquals(42, $policy->getId());
    }
}
