<?php declare(strict_types=1);

namespace App\Tests\Unit\Insurance\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Insurance\Entity\InsurancePolicy;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class InsurancePolicyTest extends TestCase
{
    public function testConstructorInitializesRequiredFields(): void
    {
        $policy = new InsurancePolicy(123, 'health', 'ABC Insurance', 'POL-001');
        $this->assertSame(123, $policy->getUserId());
        $this->assertSame('health', $policy->getType());
        $this->assertSame('ABC Insurance', $policy->getProvider());
        $this->assertSame('POL-001', $policy->getPolicyNumber());
        $this->assertSame('VND', $policy->getCurrency());
        $this->assertInstanceOf(\DateTime::class, $policy->getStartDate());
        $this->assertNull($policy->getId());
        $this->assertSame('pending', $policy->getStatus());
    }

    public function testGettersSetters(): void
    {
        $policy = new InsurancePolicy(1, 'car', 'XYZ Ins', 'CAR-123');

        $policy->setCoverageAmount(50000.00);
        $this->assertSame(50000.00, $policy->getCoverageAmount());

        $policy->setPremium(500.00);
        $this->assertSame(500.00, $policy->getPremium());

        $end = (new \DateTime())->modify('+1 year');
        $policy->setEndDate($end);
        $this->assertSame($end, $policy->getEndDate());

        $policy->setBeneficiary('John Doe');
        $this->assertSame('John Doe', $policy->getBeneficiary());

        $policy->setBeneficiary(null);
        $this->assertNull($policy->getBeneficiary());


    }

    public function testSetCoverageAmountInvalid(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'ABC', 'POL-001');
        $this->expectException(\InvalidArgumentException::class);
        $policy->setCoverageAmount(0);
    }

    public function testSetPremiumInvalid(): void
    {
        $policy = new InsurancePolicy(1, 'life', 'XYZ', 'POL-002');
        $this->expectException(\InvalidArgumentException::class);
        $policy->setPremium(-10);
    }

    public function testSetEndDateBeforeStartThrows(): void
    {
        $policy = new InsurancePolicy(1, 'property', 'DEF', 'POL-003');
        $past = (new \DateTime())->modify('-1 year');
        $this->expectException(\InvalidArgumentException::class);
        $policy->setEndDate($past);
    }

    public function testSetBeneficiaryTrimsAndEscapes(): void
    {
        $policy = new InsurancePolicy(1, 'travel', 'TRAVEL', 'POL-004');
        $policy->setBeneficiary('  John < Doe >  ');
        $this->assertSame('John &lt; Doe &gt;', $policy->getBeneficiary());
    }

    public function testSetBeneficiaryValidatesLength(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'ABC', 'POL-005');
        $long = str_repeat('a', 256);
        $this->expectException(\InvalidArgumentException::class);
        $policy->setBeneficiary($long);
    }

    public function testActivate(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'ABC', 'POL-006');
        $policy->activate();
        $this->assertSame('active', $policy->getStatus());
    }

    public function testActivateOnCancelledThrows(): void
    {
        $policy = new InsurancePolicy(1, 'life', 'XYZ', 'POL-007');
        $policy->cancel(); // first cancel
        $this->expectException(\LogicException::class);
        $policy->activate();
    }

    public function testCancelIdempotent(): void
    {
        $policy = new InsurancePolicy(1, 'car', 'CAR', 'POL-008');
        $policy->cancel();
        $this->assertSame('cancelled', $policy->getStatus());
        $policy->cancel(); // second cancel should still be cancelled, no error
        $this->assertSame('cancelled', $policy->getStatus());
    }

    public function testIdSetAndGet(): void
    {
        $policy = new InsurancePolicy(1, 'health', 'ABC', 'POL-009');
        $ref = new \ReflectionProperty($policy, 'id');
        $ref->setAccessible(true);
        $ref->setValue($policy, 42);
        $this->assertSame(42, $policy->getId());
    }
}
