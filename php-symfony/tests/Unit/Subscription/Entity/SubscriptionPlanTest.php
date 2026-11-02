<?php declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Subscription\Entity\SubscriptionPlan;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SubscriptionPlanTest extends TestCase
{
    public function testConstructor(): void
    {
        $plan = new SubscriptionPlan(
            id: 'plan-basic',
            name: 'Basic',
            price: '9.99',
            description: 'Basic plan',
            durationDays: 30,
            maxMembers: 5,
            maxFileSize: 100,
            maxMessages: 1000,
            features: ['feature1', 'feature2']
        );

        $this->assertSame('plan-basic', $plan->getId());
        $this->assertSame('Basic', $plan->getName());
        $this->assertSame('Basic plan', $plan->getDescription());
        $this->assertSame('9.99', $plan->getPrice());
        $this->assertSame('USD', $plan->getCurrency());
        $this->assertSame(30, $plan->getDurationDays());
        $this->assertTrue($plan->isActive());
        $this->assertSame(5, $plan->getMaxMembers());
        $this->assertSame(100, $plan->getMaxFileSize());
        $this->assertSame(1000, $plan->getMaxMessages());
        $this->assertSame(['feature1', 'feature2'], $plan->getFeatures());
        $this->assertInstanceOf(\DateTime::class, $plan->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $plan->getUpdatedAt());
    }

    public function testSetters(): void
    {
        $plan = new SubscriptionPlan(
            id: 'plan-pro',
            name: 'Pro',
            price: '29.99'
        );

        $plan->setName('Pro Plus');
        $this->assertSame('Pro Plus', $plan->getName());

        $plan->setDescription('Pro description');
        $this->assertSame('Pro description', $plan->getDescription());

        $plan->setPrice('19.99');
        $this->assertSame('19.99', $plan->getPrice());

        $plan->setCurrency('EUR');
        $this->assertSame('EUR', $plan->getCurrency());

        $plan->setDurationDays(60);
        $this->assertSame(60, $plan->getDurationDays());

        $plan->setActive(false);
        $this->assertFalse($plan->isActive());

        $plan->setMaxMembers(10);
        $this->assertSame(10, $plan->getMaxMembers());

        $plan->setMaxFileSize(200);
        $this->assertSame(200, $plan->getMaxFileSize());

        $plan->setMaxMessages(5000);
        $this->assertSame(5000, $plan->getMaxMessages());

        $plan->setFeatures(['f1', 'f2']);
        $this->assertSame(['f1','f2'], $plan->getFeatures());
    }

    public function testToArray(): void
    {
        $created = new \DateTime('2025-01-01 00:00:00');
        $updated = new \DateTime('2025-01-02 00:00:00');
        $plan = new SubscriptionPlan(
            id: 'plan-id-123',
            name: 'Standard',
            price: '14.99',
            description: 'Standard plan',
            durationDays: 30,
            maxMembers: 3,
            maxFileSize: 50,
            maxMessages: 5000,
            features: ['chat', 'support']
        );
        // Use reflection to set timestamps
        $rc = new \ReflectionClass($plan);
        $propCreated = $rc->getProperty('createdAt');
        $propCreated->setAccessible(true);
        $propCreated->setValue($plan, $created);
        $propUpdated = $rc->getProperty('updatedAt');
        $propUpdated->setAccessible(true);
        $propUpdated->setValue($plan, $updated);

        $arr = $plan->toArray();

        $this->assertSame([
            'id' => 'plan-id-123',
            'name' => 'Standard',
            'description' => 'Standard plan',
            'price' => '14.99',
            'currency' => 'USD',
            'durationDays' => 30,
            'maxMembers' => 3,
            'maxFileSize' => 50,
            'maxMessages' => 5000,
            'features' => ['chat', 'support'],
            'isActive' => true,
            'createdAt' => '2025-01-01 00:00:00',
            'updatedAt' => '2025-01-02 00:00:00',
        ], $arr);
    }
}
