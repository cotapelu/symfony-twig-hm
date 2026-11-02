<?php declare(strict_types=1);

namespace App\Tests\Unit\Insurance\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Insurance\Entity\Policy;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PolicyTest extends TestCase
{
    public function testConstructorGeneratesPolicyNumberAndCreatedAt(): void
    {
        $policy = new Policy();
        $this->assertIsString($policy->getPolicyNumber());
        $this->assertStringStartsWith('INS-', $policy->getPolicyNumber());
        $this->assertInstanceOf(\DateTime::class, $policy->getCreatedAt());
    }

    public function testGettersSetters(): void
    {
        $policy = new Policy();
        $policy->setUserId('user-123');
        $this->assertSame('user-123', $policy->getUserId());

        $policy->setType('health');
        $this->assertSame('health', $policy->getType());

        $policy->setProvider('ABC Insurance');
        $this->assertSame('ABC Insurance', $policy->getProvider());

        $policy->setCoverageAmount(100000.00);
        $this->assertSame(100000.00, $policy->getCoverageAmount());

        $policy->setPremium(500.00);
        $this->assertSame(500.00, $policy->getPremium());

        $policy->setStatus('inactive');
        $this->assertSame('inactive', $policy->getStatus());

        $start = new \DateTime('2025-01-01');
        $policy->setStartDate($start);
        $this->assertSame($start, $policy->getStartDate());

        $end = new \DateTime('2025-12-31');
        $policy->setEndDate($end);
        $this->assertSame($end, $policy->getEndDate());

        $policy->setBeneficiary('John Doe');
        $this->assertSame('John Doe', $policy->getBeneficiary());
    }

    public function testIsActive(): void
    {
        $policy = new Policy();
        $now = new \DateTime();

        // Default status active and no endDate -> active
        $policy->setStatus('active');
        $policy->setEndDate(null);
        $this->assertTrue($policy->isActive());

        // Active with future endDate
        $future = (clone $now)->modify('+1 year');
        $policy->setEndDate($future);
        $this->assertTrue($policy->isActive());

        // Inactive status
        $policy->setStatus('inactive');
        $this->assertFalse($policy->isActive());

        // Active status but expired endDate
        $policy->setStatus('active');
        $past = (clone $now)->modify('-1 year');
        $policy->setEndDate($past);
        $this->assertFalse($policy->isActive());
    }

    public function testIdSetAndGet(): void
    {
        $policy = new Policy();
        // Use reflection to set id (since setter is for ORM)
        $ref = new \ReflectionProperty($policy, 'id');
        $ref->setAccessible(true);
        $ref->setValue($policy, 42);
        $this->assertSame(42, $policy->getId());
    }
}
