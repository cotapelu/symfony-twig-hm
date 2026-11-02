<?php declare(strict_types=1);

namespace App\Tests\Unit\Insurance\Service;

use App\Insurance\Entity\InsurancePolicy;
use App\Insurance\Service\InsuranceService;
use PHPUnit\Framework\TestCase;

class InsuranceServiceTest extends TestCase
{
    private InsuranceService $service;

    protected function setUp(): void
    {
        $this->service = new InsuranceService();
    }

    public function testCreatePolicy(): void
    {
        $policy = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        
        $this->assertInstanceOf(InsurancePolicy::class, $policy);
        $this->assertEquals(1, $policy->getUserId());
        $this->assertEquals('health', $policy->getType());
        $this->assertEquals('Prudential', $policy->getProvider());
        $this->assertEquals('POL-001', $policy->getPolicyNumber());
    }

    public function testGetPolicy(): void
    {
        $created = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        
        $result = $this->service->getPolicy($created->getId());
        
        $this->assertSame($created, $result);
    }

    public function testGetPolicyNotFound(): void
    {
        $result = $this->service->getPolicy(999);
        
        $this->assertNull($result);
    }

    public function testGetUserPolicies(): void
    {
        $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        $this->service->createPolicy(1, 'life', 'Prudential', 'POL-002');
        $this->service->createPolicy(2, 'car', 'Prudential', 'POL-003');
        
        $results = $this->service->getUserPolicies(1);
        
        $this->assertCount(2, $results);
    }

    public function testGetPoliciesByType(): void
    {
        $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        $this->service->createPolicy(2, 'health', 'Prudential', 'POL-002');
        $this->service->createPolicy(3, 'life', 'Prudential', 'POL-003');
        
        $results = $this->service->getPoliciesByType('health');
        
        $this->assertCount(2, $results);
    }

    public function testGetActivePolicies(): void
    {
        $policy1 = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        $policy1->setEndDate(new \DateTime('+1 year'));
        $policy1->activate();
        
        $policy2 = $this->service->createPolicy(2, 'life', 'Prudential', 'POL-002');
        
        $results = $this->service->getActivePolicies();
        
        $this->assertCount(1, $results);
    }

    public function testUpdatePolicy(): void
    {
        $policy = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        
        $result = $this->service->updatePolicy($policy->getId(), [
            'coverage_amount' => 1000000,
            'premium' => 50000,
            'beneficiary' => 'John Doe'
        ]);
        
        $this->assertNotNull($result);
        $this->assertEquals(1000000, $result->getCoverageAmount());
        $this->assertEquals(50000, $result->getPremium());
        $this->assertEquals('John Doe', $result->getBeneficiary());
    }

    public function testActivatePolicy(): void
    {
        $policy = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        
        $result = $this->service->activatePolicy($policy->getId());
        
        $this->assertNotNull($result);
        $this->assertEquals('active', $result->getStatus());
    }

    public function testCancelPolicy(): void
    {
        $policy = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->setEndDate(new \DateTime('+1 year'));
        $policy->activate();
        
        $result = $this->service->cancelPolicy($policy->getId());
        
        $this->assertNotNull($result);
        $this->assertEquals('cancelled', $result->getStatus());
    }

    public function testRenewPolicy(): void
    {
        $policy = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        $policy->setEndDate(new \DateTime('+1 year'));
        
        $result = $this->service->renewPolicy($policy->getId(), 12);
        
        $this->assertNotNull($result);
        $this->assertEquals('active', $result->getStatus());
    }

    public function testSearchPoliciesByType(): void
    {
        $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        $this->service->createPolicy(2, 'life', 'Prudential', 'POL-002');
        
        $results = $this->service->searchPolicies(['type' => 'health']);
        
        $this->assertCount(1, $results);
    }

    public function testSearchPoliciesByProvider(): void
    {
        $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        $this->service->createPolicy(2, 'health', 'Manulife', 'POL-002');
        
        $results = $this->service->searchPolicies(['provider' => 'Prudential']);
        
        $this->assertCount(1, $results);
    }

    public function testGetExpiringPolicies(): void
    {
        $policy1 = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        $policy1->setEndDate(new \DateTime('+15 days'));
        $policy1->activate();
        
        $policy2 = $this->service->createPolicy(2, 'health', 'Prudential', 'POL-002');
        $policy2->setEndDate(new \DateTime('+1 year'));
        $policy2->activate();
        
        $results = $this->service->getExpiringPolicies(30);
        
        $this->assertCount(1, $results);
    }

    public function testDeletePolicy(): void
    {
        $policy = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001');
        
        $result = $this->service->deletePolicy($policy->getId());
        
        $this->assertTrue($result);
        $this->assertNull($this->service->getPolicy($policy->getId()));
    }

    public function testDeletePolicyNotFound(): void
    {
        $result = $this->service->deletePolicy(999);
        
        $this->assertFalse($result);
    }
}
