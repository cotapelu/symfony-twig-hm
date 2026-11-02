<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\Subscription;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SubscriptionTest extends TestCase
{
    private function createSubscription(array $overrides = []): Subscription
    {
        $sub = new Subscription();
        $sub->setBusiness($this->createMock(Business::class));
        $sub->setPlan('basic');
        $sub->setPrice(9.99);
        $now = new \DateTime();
        $sub->setStartedAt($now);
        $sub->setExpiresAt((new \DateTime())->modify('+1 month'));
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . $prop;
            if (method_exists($sub, $setter)) {
                $sub->$setter($value);
            }
        }
        return $sub;
    }

    public function testConstructorInitializesCreatedAt(): void
    {
        $sub = $this->createSubscription([]);
        $this->assertInstanceOf(\DateTime::class, $sub->getCreatedAt());
    }

    public function testDefaultValues(): void
    {
        $sub = $this->createSubscription([]);
        $this->assertSame('monthly', $sub->getBillingCycle());
        $this->assertSame('active', $sub->getStatus());
        $this->assertNull($sub->getPaymentMethod());
        $this->assertNull($sub->getTransactionId());
    }

    public function testGettersAndSetters(): void
    {
        $sub = $this->createSubscription([]);

        $sub->setPlan('premium');
        $this->assertSame('premium', $sub->getPlan());

        $sub->setPrice(19.99);
        $this->assertSame(19.99, $sub->getPrice());

        $sub->setBillingCycle('yearly');
        $this->assertSame('yearly', $sub->getBillingCycle());

        $started = new \DateTime('2025-01-01');
        $sub->setStartedAt($started);
        $this->assertSame($started, $sub->getStartedAt());

        $expires = new \DateTime('2025-12-31');
        $sub->setExpiresAt($expires);
        $this->assertSame($expires, $sub->getExpiresAt());

        $sub->setStatus('expired');
        $this->assertSame('expired', $sub->getStatus());

        $sub->setPaymentMethod('card');
        $this->assertSame('card', $sub->getPaymentMethod());

        $sub->setTransactionId('txn-123');
        $this->assertSame('txn-123', $sub->getTransactionId());
    }
}
