<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\Subscription;
use App\BusinessAccount\Repository\SubscriptionRepository;
use App\BusinessAccount\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SubscriptionServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private SubscriptionRepository&MockObject $subscriptionRepo;
    private SubscriptionService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->subscriptionRepo = $this->createMock(SubscriptionRepository::class);
        $this->em->method('getRepository')->with(Subscription::class)->willReturn($this->subscriptionRepo);
        $this->service = new SubscriptionService($this->em);
    }

    public function testCreateSubscription(): void
    {
        $business = $this->createMock(Business::class);
        $plan = 'professional';
        $duration = 12;

        // Use a partial Subscription to capture state
        $subscription = new Subscription();
        // We'll set business via reflection later (since setter exists)
        // But we can just verify persist and flush called with a Subscription object

        $this->em->expects($this->once())->method('persist')->with($this->callback(function($arg) use ($plan, $business) {
            $this->assertInstanceOf(Subscription::class, $arg);
            // Check business is set
            $this->assertSame($business, $arg->getBusiness());
            $this->assertSame($plan, $arg->getPlan());
            $this->assertSame('active', $arg->getStatus());
            $this->assertNotNull($arg->getStartedAt());
            $this->assertNotNull($arg->getExpiresAt());
            return true;
        }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createSubscription($business, $plan, $duration);
        $this->assertInstanceOf(Subscription::class, $result);
    }

    public function testCreateSubscriptionDefaultPriceForUnknownPlan(): void
    {
        $business = $this->createMock(Business::class);
        $plan = 'unknown';
        $this->em->expects($this->once())->method('persist')->with($this->callback(fn($arg) => $arg->getPrice() === 10.0));
        $this->em->expects($this->once())->method('flush');
        $this->service->createSubscription($business, $plan, 1);
    }

    public function testRenewSubscriptionWhenExpired(): void
    {
        $subscription = new Subscription();
        // Set initial state to expired
        $ref = new \ReflectionProperty($subscription, 'expiresAt');
        $ref->setAccessible(true);
        $ref->setValue($subscription, new \DateTime('-1 day'));
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->renewSubscription($subscription, 6);
        $this->assertSame($subscription, $result);
        $this->assertSame('active', $subscription->getStatus());
        $this->assertNotNull($subscription->getStartedAt());
        $this->assertGreaterThan((new \DateTime('-1 day'))->getTimestamp(), $subscription->getExpiresAt()->getTimestamp());
    }

    public function testRenewSubscriptionWhenNotExpired(): void
    {
        $subscription = new Subscription();
        $now = new \DateTime();
        // Set initial state: startedAt in the past, expiresAt in the future
        $refStarted = new \ReflectionProperty($subscription, 'startedAt');
        $refStarted->setAccessible(true);
        $refStarted->setValue($subscription, (clone $now)->modify('-1 month'));
        $future = (clone $now)->modify('+1 month');
        $refExpires = new \ReflectionProperty($subscription, 'expiresAt');
        $refExpires->setAccessible(true);
        $refExpires->setValue($subscription, $future);
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->renewSubscription($subscription, 6);
        $this->assertSame($subscription, $result);
        $this->assertSame('active', $subscription->getStatus());
        // startedAt should remain the same
        $this->assertEquals((clone $now)->modify('-1 month'), $subscription->getStartedAt());
        // expiresAt should be extended by 6 months
        $expected = (clone $future)->modify('+6 months');
        $this->assertGreaterThan($future->getTimestamp(), $subscription->getExpiresAt()->getTimestamp());
    }

    public function testCancelSubscription(): void
    {
        $subscription = $this->createMock(Subscription::class);
        $subscription->expects($this->once())->method('setStatus')->with('cancelled');
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->cancelSubscription($subscription);
        $this->assertSame($subscription, $result);
    }

    public function testGetActiveSubscription(): void
    {
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn(5);
        $subscription = $this->createMock(Subscription::class);
        $this->subscriptionRepo->expects($this->once())->method('findActiveByBusiness')->with(5)->willReturn($subscription);
        $result = $this->service->getActiveSubscription($business);
        $this->assertSame($subscription, $result);
    }

    public function testGetActiveSubscriptionWhenNone(): void
    {
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn(5);
        $this->subscriptionRepo->expects($this->once())->method('findActiveByBusiness')->with(5)->willReturn(null);
        $result = $this->service->getActiveSubscription($business);
        $this->assertNull($result);
    }

    public function testHasFeature(): void
    {
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn(5);
        $active = $this->createMock(Subscription::class);
        $active->method('getPlan')->willReturn('basic');
        $this->subscriptionRepo->expects($this->any())->method('findActiveByBusiness')->with(5)->willReturn($active);
        $this->assertTrue($this->service->hasFeature($business, 'catalog'));
        $this->assertTrue($this->service->hasFeature($business, 'api_access'));
        $this->assertFalse($this->service->hasFeature($business, 'white_label'));
    }

    public function testHasFeatureWhenNoActiveSubscription(): void
    {
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn(5);
        $this->subscriptionRepo->expects($this->once())->method('findActiveByBusiness')->with(5)->willReturn(null);
        $this->assertFalse($this->service->hasFeature($business, 'catalog'));
    }
}
