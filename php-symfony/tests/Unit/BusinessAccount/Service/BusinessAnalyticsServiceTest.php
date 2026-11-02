<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\ApiKey;
use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\CatalogItem;
use App\BusinessAccount\Entity\Subscription;
use App\BusinessAccount\Repository\ApiKeyRepository;
use App\BusinessAccount\Repository\CatalogItemRepository;
use App\BusinessAccount\Repository\SubscriptionRepository;
use App\BusinessAccount\Service\BusinessAnalyticsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessAnalyticsServiceTest extends TestCase
{
    private CatalogItemRepository&MockObject $catalogRepo;
    private SubscriptionRepository&MockObject $subscriptionRepo;
    private ApiKeyRepository&MockObject $apiKeyRepo;
    private BusinessAnalyticsService $service;

    protected function setUp(): void
    {
        $this->catalogRepo = $this->createMock(CatalogItemRepository::class);
        $this->subscriptionRepo = $this->createMock(SubscriptionRepository::class);
        $this->apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $this->service = new BusinessAnalyticsService(
            $this->catalogRepo,
            $this->subscriptionRepo,
            $this->apiKeyRepo
        );
    }

    public function testGetBusinessStats(): void
    {
        $businessId = 42;
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn($businessId);
        $business->method('isIsVerified')->willReturn(true);
        $business->method('getStatus')->willReturn('active');

        // Mock catalog items: mix active/inactive
        $catalogItem1 = new CatalogItem();
        // Make it active: we'll use setAvailable = true (since isActive => isAvailable)
        // Use reflection to set isAvailable (private)
        $ref1 = new \ReflectionProperty($catalogItem1, 'isAvailable');
        $ref1->setAccessible(true);
        $ref1->setValue($catalogItem1, true);
        $catalogItem2 = new CatalogItem();
        $ref2 = new \ReflectionProperty($catalogItem2, 'isAvailable');
        $ref2->setAccessible(true);
        $ref2->setValue($catalogItem2, false);
        $catalogItems = [$catalogItem1, $catalogItem2];

        // Mock subscriptions
        $sub1 = $this->createMock(Subscription::class);
        $sub1->method('getPlan')->willReturn('basic');
        $sub1->method('getExpiresAt')->willReturn((new \DateTime('+1 month')));
        $sub2 = $this->createMock(Subscription::class);
        $subscriptions = [$sub1, $sub2];

        // Mock active subscription (single)
        $activeSubscription = $this->createMock(Subscription::class);
        $activeSubscription->method('getPlan')->willReturn('professional');
        $activeSubscription->method('getExpiresAt')->willReturn((new \DateTime('+6 months')));

        // Mock active API keys
        $activeKeys = [new ApiKey(), new ApiKey(), new ApiKey()];

        // Set up repository expectations
        $this->catalogRepo->expects($this->once())->method('findByBusiness')->with($businessId)->willReturn($catalogItems);
        $this->subscriptionRepo->expects($this->once())->method('findByBusiness')->with($businessId)->willReturn($subscriptions);
        $this->apiKeyRepo->expects($this->once())->method('findActiveByBusiness')->with($businessId)->willReturn($activeKeys);
        $this->subscriptionRepo->expects($this->once())->method('findActiveByBusiness')->with($businessId)->willReturn($activeSubscription);

        $result = $this->service->getBusinessStats($business);

        $this->assertIsArray($result);
        $this->assertSame(2, $result['total_catalog_items']);
        $this->assertSame(1, $result['active_catalog_items']); // only first active
        $this->assertSame(2, $result['total_subscriptions']);
        $this->assertSame(3, $result['active_api_keys']);
        $this->assertSame('professional', $result['current_plan']);
        $this->assertNotNull($result['subscription_expires_at']);
        $this->assertTrue($result['is_verified']);
        $this->assertSame('active', $result['status']);
    }

    public function testGetBusinessStatsWhenNoActiveSubscription(): void
    {
        $businessId = 99;
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn($businessId);
        $business->method('isIsVerified')->willReturn(false);
        $business->method('getStatus')->willReturn('pending');

        $this->catalogRepo->expects($this->once())->method('findByBusiness')->with($businessId)->willReturn([]);
        $this->subscriptionRepo->expects($this->once())->method('findByBusiness')->with($businessId)->willReturn([]);
        $this->apiKeyRepo->expects($this->once())->method('findActiveByBusiness')->with($businessId)->willReturn([]);
        $this->subscriptionRepo->expects($this->once())->method('findActiveByBusiness')->with($businessId)->willReturn(null);

        $result = $this->service->getBusinessStats($business);

        $this->assertSame(0, $result['total_catalog_items']);
        $this->assertSame(0, $result['active_catalog_items']);
        $this->assertSame(0, $result['total_subscriptions']);
        $this->assertSame(0, $result['active_api_keys']);
        $this->assertNull($result['current_plan']);
        $this->assertNull($result['subscription_expires_at']);
        $this->assertFalse($result['is_verified']);
        $this->assertSame('pending', $result['status']);
    }
}
