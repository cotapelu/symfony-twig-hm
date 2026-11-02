<?php declare(strict_types=1);

namespace App\Tests\Unit\Insurance\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Insurance\Entity\Claim;
use App\Insurance\Entity\Policy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ClaimTest extends TestCase
{
    private function createMockPolicy(): MockObject&Policy
    {
        $policy = $this->createMock(Policy::class);
        $policy->method('getId')->willReturn(1);
        $policy->method('getPolicyNumber')->willReturn('POL-123');
        return $policy;
    }

    public function testConstructorGeneratesClaimNumberAndCreatedAt(): void
    {
        $claim = new Claim();
        $this->assertIsString($claim->getClaimNumber());
        $this->assertStringStartsWith('CLM-', $claim->getClaimNumber());
        $this->assertInstanceOf(\DateTime::class, $claim->getCreatedAt());
    }

    public function testGettersSetters(): void
    {
        $policy = $this->createMockPolicy();
        $claim = new Claim();
        $claim->setPolicy($policy);
        $this->assertSame($policy, $claim->getPolicy());

        $claim->setClaimAmount(2500.00);
        $this->assertSame(2500.00, $claim->getClaimAmount());

        $claim->setDescription('Car accident');
        $this->assertSame('Car accident', $claim->getDescription());

        $claim->setStatus(Claim::STATUS_APPROVED);
        $this->assertSame(Claim::STATUS_APPROVED, $claim->getStatus());

        $processed = new \DateTime('2025-01-15');
        $claim->setProcessedAt($processed);
        $this->assertSame($processed, $claim->getProcessedAt());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('submitted', Claim::STATUS_SUBMITTED);
        $this->assertSame('reviewing', Claim::STATUS_REVIEWING);
        $this->assertSame('approved', Claim::STATUS_APPROVED);
        $this->assertSame('rejected', Claim::STATUS_REJECTED);
        $this->assertSame('paid', Claim::STATUS_PAID);
    }

    public function testIdSetAndGet(): void
    {
        $claim = new Claim();
        $ref = new \ReflectionProperty($claim, 'id');
        $ref->setAccessible(true);
        $ref->setValue($claim, 100);
        $this->assertSame(100, $claim->getId());
    }
}
