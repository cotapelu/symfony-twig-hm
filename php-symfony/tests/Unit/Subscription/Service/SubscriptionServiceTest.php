<?php

declare(strict_types=1);

namespace Tests\Unit\Subscription\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Subscription\Service\SubscriptionService;
use App\Subscription\Entity\Subscription;
use App\Subscription\Entity\SubscriptionPlan;
use App\Subscription\Repository\SubscriptionRepository;
use App\Subscription\Repository\SubscriptionPlanRepository;
use App\Shared\EventBus\EventBus;
use App\Subscription\Event\SubscriptionCreatedEvent;
use App\Subscription\Event\SubscriptionExpiredEvent;
use App\User\Repository\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SubscriptionServiceTest extends TestCase
{
    private MockObject $subscriptionRepository;
    private MockObject $planRepository;
    private MockObject $eventBus;
    private MockObject $userRepository;
    private SubscriptionService $service;

    protected function setUp(): void
    {
        $this->subscriptionRepository = $this->createMock(SubscriptionRepository::class);
        $this->planRepository = $this->createMock(SubscriptionPlanRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->service = new SubscriptionService(
            $this->subscriptionRepository,
            $this->planRepository,
            $this->eventBus,
            $this->userRepository
        );
    }

    public function testCreateSubscriptionSuccess(): void
    {
        $userId = 'user-123';
        $planId = 'plan-pro';

        $plan = new SubscriptionPlan(
            id: $planId,
            name: 'Pro Plan',
            price: '29.99',
            description: 'Pro features',
            durationDays: 30,
            maxMembers: 5,
            maxFileSize: 1000,
            maxMessages: 50000,
            features: ['feature1', 'feature2']
        );

        $user = $this->createMock(\App\User\Entity\User::class);
        $user->method('getId')->willReturn($userId);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $this->planRepository->expects($this->once())
            ->method('find')
            ->with($planId)
            ->willReturn($plan);

        $this->subscriptionRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Subscription::class));

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SubscriptionCreatedEvent::class));

        $result = $this->service->createSubscription($userId, $planId);

        $this->assertInstanceOf(Subscription::class, $result);
        $this->assertSame($planId, $result->getPlanId());
        $this->assertSame(29.99, $result->getAmount());
    }

    public function testCreateSubscriptionFailsWhenPlanNotFound(): void
    {
        $this->planRepository->expects($this->once())
            ->method('find')
            ->with('invalid-plan')
            ->willReturn(null);

        $this->userRepository->expects($this->never())
            ->method('find');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Plan not found');

        $this->service->createSubscription('user-123', 'invalid-plan');
    }

    public function testCancelSubscriptionSuccess(): void
    {
        $subscriptionId = 'sub-123';
        $user = $this->createMock(\App\User\Entity\User::class);
        $user->method('getId')->willReturn('user-123');

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getPlanId')->willReturn('plan-123');
        $subscription->method('getEndedAt')->willReturn(new \DateTime());

        $this->subscriptionRepository->expects($this->once())
            ->method('find')
            ->with($subscriptionId)
            ->willReturn($subscription);

        $subscription->expects($this->once())
            ->method('setStatus')
            ->with('cancelled');

        $subscription->expects($this->once())
            ->method('setEndedAt')
            ->with($this->isInstanceOf(\DateTime::class));

        $this->subscriptionRepository->expects($this->once())
            ->method('save')
            ->with($subscription);

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SubscriptionExpiredEvent::class));

        $this->service->cancelSubscription($subscriptionId);
    }

    public function testCancelSubscriptionFailsWhenNotFound(): void
    {
        $this->subscriptionRepository->expects($this->once())
            ->method('find')
            ->with('notfound')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Subscription not found');

        $this->service->cancelSubscription('notfound');
    }

    public function testRenewSubscriptionSuccess(): void
    {
        $subscriptionId = 'sub-123';
        $subscription = $this->createMock(Subscription::class);

        $this->subscriptionRepository->expects($this->once())
            ->method('find')
            ->with($subscriptionId)
            ->willReturn($subscription);

        $subscription->expects($this->once())
            ->method('setStatus')
            ->with('active');

        $this->subscriptionRepository->expects($this->once())
            ->method('save')
            ->with($subscription);

        $this->service->renewSubscription($subscriptionId);
    }

    public function testGetSubscriptionFound(): void
    {
        $subscription = $this->createMock(Subscription::class);
        $this->subscriptionRepository->expects($this->once())
            ->method('find')
            ->with('sub-123')
            ->willReturn($subscription);

        $result = $this->service->getSubscription('sub-123');
        $this->assertSame($subscription, $result);
    }

    public function testGetSubscriptionNotFoundReturnsNull(): void
    {
        $this->subscriptionRepository->expects($this->once())
            ->method('find')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->service->getSubscription('notfound');
        $this->assertNull($result);
    }

    public function testGetUserSubscription(): void
    {
        $subscription = $this->createMock(Subscription::class);
        $this->subscriptionRepository->expects($this->once())
            ->method('findActiveByUser')
            ->with('user-123')
            ->willReturn($subscription);

        $result = $this->service->getUserSubscription('user-123');
        $this->assertSame($subscription, $result);
    }

    public function testGetAllPlans(): void
    {
        $plan1 = new SubscriptionPlan('p1', 'Basic', '9.99', null, 30, 1, 100, 1000, []);
        $plan2 = new SubscriptionPlan('p2', 'Pro', '29.99', null, 30, 5, 1000, 10000, []);
        $this->planRepository->expects($this->once())
            ->method('findAllActive')
            ->willReturn([$plan1, $plan2]);

        $result = $this->service->getAllPlans();
        $this->assertCount(2, $result);
    }

    public function testCreatePlan(): void
    {
        $data = [
            'id' => 'new-plan',
            'name' => 'New Plan',
            'price' => '19.99',
            'description' => 'Nice plan',
            'duration_days' => 60,
            'max_members' => 3,
            'max_file_size' => 500,
            'max_messages' => 5000,
            'features' => ['f1', 'f2']
        ];

        $this->planRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(SubscriptionPlan::class));

        $result = $this->service->createPlan($data);

        $this->assertInstanceOf(SubscriptionPlan::class, $result);
        $this->assertSame('new-plan', $result->getId());
        $this->assertSame(60, $result->getDurationDays());
    }
}
