<?php

declare(strict_types=1);

namespace App\Tests\Integration\Subscription;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Subscription\Entity\SubscriptionPlan;

#[AllowMockObjectsWithoutExpectations]
class SubscriptionPlanIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrievePlan(): void
    {
        $plan = new SubscriptionPlan(
            id: 'plan-basic',
            name: 'Basic Plan',
            price: '9.99'
        );
        $plan->setDescription('Basic subscription');
        $plan->setActive(true);
        $plan->setFeatures(['feature1' => true, 'feature2' => false]);

        $em = self::getEntityManager();
        $em->persist($plan);
        $em->flush();

        $this->assertSame('plan-basic', $plan->getId());

        $repo = $em->getRepository(SubscriptionPlan::class);
        $found = $repo->find('plan-basic');

        $this->assertNotNull($found);
        $this->assertSame('Basic Plan', $found->getName());
        $this->assertSame('Basic subscription', $found->getDescription());
        $this->assertSame('9.99', $found->getPrice());
        $this->assertSame('USD', $found->getCurrency());
        $this->assertSame(30, $found->getDurationDays());
        $this->assertTrue($found->isActive());
        $this->assertSame(['feature1' => true, 'feature2' => false], $found->getFeatures());
    }

    public function testUpdatePlan(): void
    {
        $plan = new SubscriptionPlan(
            id: 'plan-pro',
            name: 'Pro Plan',
            price: '19.99'
        );
        $plan->setActive(false);

        $em = self::getEntityManager();
        $em->persist($plan);
        $em->flush();

        $plan->setName('Updated Pro Plan');
        $plan->setPrice('29.99');
        $plan->setActive(true);
        $em->flush();

        $repo = $em->getRepository(SubscriptionPlan::class);
        $found = $repo->find('plan-pro');

        $this->assertNotNull($found);
        $this->assertSame('Updated Pro Plan', $found->getName());
        $this->assertSame('29.99', $found->getPrice());
        $this->assertTrue($found->isActive());
    }

    public function testFindByActive(): void
    {
        $activePlan = new SubscriptionPlan(
            id: 'plan-active',
            name: 'Active',
            price: '5.00'
        );
        $activePlan->setActive(true);

        $inactivePlan = new SubscriptionPlan(
            id: 'plan-inactive',
            name: 'Inactive',
            price: '10.00'
        );
        $inactivePlan->setActive(false);

        $em = self::getEntityManager();
        $em->persist($activePlan);
        $em->persist($inactivePlan);
        $em->flush();

        $repo = $em->getRepository(SubscriptionPlan::class);
        $activePlans = $repo->findBy(['isActive' => true]);

        $this->assertCount(1, $activePlans);
        $this->assertSame('plan-active', $activePlans[0]->getId());
    }

    public function testDeletePlan(): void
    {
        $plan = new SubscriptionPlan(
            id: 'plan-delete',
            name: 'To Delete',
            price: '1.00'
        );
        $plan->setActive(true);

        $em = self::getEntityManager();
        $em->persist($plan);
        $em->flush();

        $id = $plan->getId();

        $em->remove($plan);
        $em->flush();

        $deleted = $em->getRepository(SubscriptionPlan::class)->find($id);
        $this->assertNull($deleted);
    }
}
