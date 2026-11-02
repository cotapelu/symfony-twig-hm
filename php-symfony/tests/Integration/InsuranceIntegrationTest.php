<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Insurance\Entity\InsurancePolicy;
use App\Insurance\Service\InsuranceService;
use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class InsuranceIntegrationTest extends DatabaseIntegrationTestCase
{
    private InsuranceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(InsuranceService::class);
    }

    public function testFullPolicyLifecycle(): void
    {
        $policy = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001', 1000000, 50000);
        $this->assertNotNull($policy->getId());
        
        $this->service->updatePolicy($policy->getId(), [
            'coverage_amount' => 1000000,
            'premium' => 50000,
            'end_date' => '+1 year'
        ]);
        
        $retrieved = $this->service->getPolicy($policy->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals(1000000, $retrieved->getCoverageAmount());
    }

    public function testPolicyActivation(): void
    {
        $policy = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001', 1000000, 50000, null, null, null, 'pending');
        $this->assertEquals('pending', $policy->getStatus());
        
        $this->service->activatePolicy($policy->getId());
        $active = $this->service->getPolicy($policy->getId());
        
        $this->assertEquals('active', $active->getStatus());
    }

    public function testPolicyRenewal(): void
    {
        $policy = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001', 1000000, 50000);
        $policy->setEndDate(new \DateTime('+1 year'));
        
        $this->service->renewPolicy($policy->getId(), 12);
        $renewed = $this->service->getPolicy($policy->getId());
        
        $this->assertEquals('active', $renewed->getStatus());
    }

    public function testPolicySearch(): void
    {
        $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001', 1000000, 50000);
        $this->service->createPolicy(2, 'life', 'Prudential', 'POL-002', 2000000, 75000);
        $this->service->createPolicy(3, 'health', 'Manulife', 'POL-003', 1500000, 60000);
        
        $health = $this->service->searchPolicies(1, ['type' => 'health']);
        $this->assertCount(1, $health);
        
        $prudential = $this->service->searchPolicies(1, ['provider' => 'Prudential']);
        $this->assertCount(1, $prudential);
    }

    public function testPolicyCancellation(): void
    {
        $policy = $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001', 1000000, 50000);
        $policy->setEndDate(new \DateTime('+1 year'));
        $this->service->activatePolicy($policy->getId());
        
        $this->service->cancelPolicy($policy->getId());
        $cancelled = $this->service->getPolicy($policy->getId());
        
        $this->assertEquals('cancelled', $cancelled->getStatus());
    }

    public function testUserPolicies(): void
    {
        $this->service->createPolicy(1, 'health', 'Prudential', 'POL-001', 1000000, 50000);
        $this->service->createPolicy(1, 'life', 'Prudential', 'POL-002', 2000000, 75000);
        $this->service->createPolicy(2, 'car', 'Prudential', 'POL-003', 500000, 30000);
        
        $user1Policies = $this->service->getUserPolicies(1);
        $this->assertCount(2, $user1Policies);
    }
}
