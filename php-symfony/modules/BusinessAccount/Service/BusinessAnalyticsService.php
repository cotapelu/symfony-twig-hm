<?php

declare(strict_types=1);

namespace App\BusinessAccount\Service;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Repository\CatalogItemRepository;
use App\BusinessAccount\Repository\SubscriptionRepository;
use App\BusinessAccount\Repository\ApiKeyRepository;

class BusinessAnalyticsService
{
    private CatalogItemRepository $catalogRepo;
    private SubscriptionRepository $subscriptionRepo;
    private ApiKeyRepository $apiKeyRepo;

    public function __construct(
        CatalogItemRepository $catalogRepo,
        SubscriptionRepository $subscriptionRepo,
        ApiKeyRepository $apiKeyRepo
    ) {
        $this->catalogRepo = $catalogRepo;
        $this->subscriptionRepo = $subscriptionRepo;
        $this->apiKeyRepo = $apiKeyRepo;
    }

    public function getBusinessStats(Business $business)
    {
        $catalogItems = $this->catalogRepo->findByBusiness($business->getId());
        $subscriptions = $this->subscriptionRepo->findByBusiness($business->getId());
        $activeKeys = $this->apiKeyRepo->findActiveByBusiness($business->getId());
        $activeSubscription = $this->subscriptionRepo->findActiveByBusiness($business->getId());

        return [
            'total_catalog_items' => count($catalogItems),
            'active_catalog_items' => count(array_filter($catalogItems, fn($item) => $item->isIsActive())),
            'total_subscriptions' => count($subscriptions),
            'active_api_keys' => count($activeKeys),
            'current_plan' => $activeSubscription?->getPlan(),
            'subscription_expires_at' => $activeSubscription?->getExpiresAt()?->format('Y-m-d H:i:s'),
            'is_verified' => $business->isIsVerified(),
            'status' => $business->getStatus()
        ];
    }
}
