<?php

declare(strict_types=1);

namespace App\BusinessAccount\Service;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\BusinessCategory;
use App\BusinessAccount\Entity\CatalogItem;
use App\BusinessAccount\Entity\Subscription;
use App\BusinessAccount\Entity\ApiKey;
use App\BusinessAccount\Repository\BusinessRepository;
use App\BusinessAccount\Repository\CatalogItemRepository;
use App\BusinessAccount\Repository\SubscriptionRepository;
use App\BusinessAccount\Repository\ApiKeyRepository;
use App\BusinessAccount\Repository\BusinessCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class BusinessAccountService
{
    private EntityManagerInterface $em;
    private BusinessRepository $businessRepo;
    private CatalogItemRepository $catalogRepo;
    private SubscriptionRepository $subscriptionRepo;
    private ApiKeyRepository $apiKeyRepo;
    private BusinessCategoryRepository $categoryRepo;
    private UserPasswordHasherInterface $passwordHasher;

    public function getCatalogRepository()
    {
        return $this->catalogRepo;
    }

    public function getApiKeyRepository()
    {
        return $this->apiKeyRepo;
    }

    public function getBusinessRepository()
    {
        return $this->businessRepo;
    }

    public function getCategoryRepository()
    {
        return $this->categoryRepo;
    }

    public function __construct(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->em = $em;
        /** @var BusinessRepository $businessRepo */
        $businessRepo = $em->getRepository(Business::class);
        $this->businessRepo = $businessRepo;
        /** @var CatalogItemRepository $catalogRepo */
        $catalogRepo = $em->getRepository(CatalogItem::class);
        $this->catalogRepo = $catalogRepo;
        /** @var SubscriptionRepository $subscriptionRepo */
        $subscriptionRepo = $em->getRepository(Subscription::class);
        $this->subscriptionRepo = $subscriptionRepo;
        /** @var ApiKeyRepository $apiKeyRepo */
        $apiKeyRepo = $em->getRepository(ApiKey::class);
        $this->apiKeyRepo = $apiKeyRepo;
        /** @var BusinessCategoryRepository $categoryRepo */
        $categoryRepo = $em->getRepository(BusinessCategory::class);
        $this->categoryRepo = $categoryRepo;
        $this->passwordHasher = $passwordHasher;
    }

    // ==================== Business Creation Helpers ====================

    private function assignBasicFields(Business $business, array $data): void
    {
        $business->setName($data['name']);
        $business->setEmail($data['email']);
        $business->setPhone($data['phone'] ?? null);
        $business->setDescription($data['description'] ?? null);
        $business->setAddress($data['address'] ?? null);
        $business->setLatitude($data['latitude'] ?? null);
        $business->setLongitude($data['longitude'] ?? null);
        $business->setWebsite($data['website'] ?? null);
        $business->setInstagram($data['instagram'] ?? null);
        $business->setFacebook($data['facebook'] ?? null);
    }

    private function assignCategory(Business $business, array $data): void
    {
        if (isset($data['category'])) {
            if ($data['category'] instanceof BusinessCategory) {
                $business->setCategory($data['category']->getName());
            } else {
                $category = $this->categoryRepo->find($data['category']);
                if ($category) {
                    $business->setCategory($category->getName());
                }
            }
        }
    }

    private function hashAndSetPassword(Business $business, array $data): void
    {
        if (isset($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($business, $data['password']);
            $business->setPassword($hashedPassword);
        }
    }

    private function setDefaultStatus(Business $business): void
    {
        $business->setStatus('pending');
        $business->setIsVerified(false);
    }

    private function persistBusiness(Business $business): void
    {
        $this->em->persist($business);
        $this->em->flush();
    }

    private function updateBasicFields(Business $business, array $data): void
    {
        $this->applyNameIfSet($business, $data);
        $this->applyDescriptionIfSet($business, $data);
        $this->applyPhoneIfSet($business, $data);
        $this->applyAddressIfSet($business, $data);
        $this->applyLatitudeIfSet($business, $data);
        $this->applyLongitudeIfSet($business, $data);
        $this->applyWebsiteIfSet($business, $data);
        $this->applyInstagramIfSet($business, $data);
        $this->applyFacebookIfSet($business, $data);
        $this->applyLogoIfSet($business, $data);
        $this->applyCoverImageIfSet($business, $data);
    }

    private function applyNameIfSet(Business $business, array $data): void
    {
        if (isset($data['name'])) {
            $business->setName($data['name']);
        }
    }

    private function applyDescriptionIfSet(Business $business, array $data): void
    {
        if (isset($data['description'])) {
            $business->setDescription($data['description']);
        }
    }

    private function applyPhoneIfSet(Business $business, array $data): void
    {
        if (isset($data['phone'])) {
            $business->setPhone($data['phone']);
        }
    }

    private function applyAddressIfSet(Business $business, array $data): void
    {
        if (isset($data['address'])) {
            $business->setAddress($data['address']);
        }
    }

    private function applyLatitudeIfSet(Business $business, array $data): void
    {
        if (isset($data['latitude'])) {
            $business->setLatitude($data['latitude']);
        }
    }

    private function applyLongitudeIfSet(Business $business, array $data): void
    {
        if (isset($data['longitude'])) {
            $business->setLongitude($data['longitude']);
        }
    }

    private function applyWebsiteIfSet(Business $business, array $data): void
    {
        if (isset($data['website'])) {
            $business->setWebsite($data['website']);
        }
    }

    private function applyInstagramIfSet(Business $business, array $data): void
    {
        if (isset($data['instagram'])) {
            $business->setInstagram($data['instagram']);
        }
    }

    private function applyFacebookIfSet(Business $business, array $data): void
    {
        if (isset($data['facebook'])) {
            $business->setFacebook($data['facebook']);
        }
    }

    private function applyLogoIfSet(Business $business, array $data): void
    {
        if (isset($data['logo'])) {
            $business->setLogo($data['logo']);
        }
    }

    private function applyCoverImageIfSet(Business $business, array $data): void
    {
        if (isset($data['coverImage'])) {
            $business->setCoverImage($data['coverImage']);
        }
    }

    public function createBusiness(array $data)
    {
        $business = new Business();
        $this->assignBasicFields($business, $data);
        $this->assignCategory($business, $data);
        $this->hashAndSetPassword($business, $data);
        $this->setDefaultStatus($business);
        $this->persistBusiness($business);
        return $business;
    }

    public function updateBusiness(Business $business, array $data)
    {
        $this->updateBasicFields($business, $data);
        $this->assignCategory($business, $data);
        $this->em->flush();
        return $business;
    }

    public function createSubscription(Business $business, string $plan, int $durationMonths)
    {
        $subscription = new Subscription();
        $subscription->setBusiness($business);
        $subscription->setPlan($plan);
        $subscription->setStatus('active');
        $subscription->setStartedAt(new \DateTime());
        $subscription->setExpiresAt(new \DateTime('+' . $durationMonths . ' months'));

        $prices = ['basic' => 10, 'professional' => 30, 'enterprise' => 100];
        $subscription->setPrice($prices[$plan] ?? 10);

        $this->em->persist($subscription);
        $this->em->flush();

        return $subscription;
    }

    public function renewSubscription(Subscription $subscription, int $months)
    {
        if ($subscription->getExpiresAt() < new \DateTime()) {
            $subscription->setStartedAt(new \DateTime());
        }
        $subscription->setExpiresAt(clone $subscription->getExpiresAt());
        $subscription->setExpiresAt($subscription->getExpiresAt()->add(new \DateInterval('P' . $months . 'M')));
        $subscription->setStatus('active');

        $this->em->flush();

        return $subscription;
    }

    public function cancelSubscription(Subscription $subscription)
    {
        $subscription->setStatus('cancelled');
        $this->em->flush();

        return $subscription;
    }

    public function generateApiKey(Business $business, ?string $name = null)
    {
        $this->apiKeyRepo->revokeOldKeys($business->getId(), 2);

        $apiKey = new ApiKey();
        $apiKey->setBusiness($business);
        $apiKey->setName($name ?? 'API Key ' . (count($this->apiKeyRepo->findByBusiness($business->getId())) + 1));
        $apiKey->setKey(bin2hex(random_bytes(32)));
        $apiKey->setStatus(ApiKey::STATUS_ACTIVE);

        $this->em->persist($apiKey);
        $this->em->flush();

        return $apiKey;
    }

    public function revokeApiKey(ApiKey $apiKey)
    {
        $apiKey->setStatus(ApiKey::STATUS_REVOKED);
        $this->em->flush();

        return $apiKey;
    }

    public function createCatalogItem(Business $business, array $data)
    {
        $item = new CatalogItem();
        $item->setBusiness($business);
        $item->setName($data['name']);
        $item->setDescription($data['description'] ?? null);
        $item->setPrice($data['price']);
        $item->setCurrency($data['currency'] ?? 'VND');
        $item->setCategory($data['category'] ?? null);
        $item->setImageUrl($data['imageUrl'] ?? null);
        $item->setStock($data['stock'] ?? null);
        $item->setIsActive(true);

        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }

    public function updateCatalogItem(CatalogItem $item, array $data)
    {
        if (isset($data['name'])) {
            $item->setName($data['name']);
        }
        if (isset($data['description'])) {
            $item->setDescription($data['description']);
        }
        if (isset($data['price'])) {
            $item->setPrice($data['price']);
        }
        if (isset($data['currency'])) {
            $item->setCurrency($data['currency']);
        }
        if (isset($data['category'])) {
            $item->setCategory($data['category']);
        }
        if (isset($data['imageUrl'])) {
            $item->setImageUrl($data['imageUrl']);
        }
        if (isset($data['stock'])) {
            $item->setStock($data['stock']);
        }
        if (isset($data['isActive'])) {
            $item->setIsActive($data['isActive']);
        }

        $this->em->flush();

        return $item;
    }

    public function deleteCatalogItem(CatalogItem $item)
    {
        $this->em->remove($item);
        $this->em->flush();
    }

    public function verifyBusiness(Business $business)
    {
        $business->setIsVerified(true);
        $business->setStatus('active');
        $this->em->flush();

        return $business;
    }

    public function approveBusiness(Business $business)
    {
        $business->setStatus('active');
        $this->em->flush();

        return $business;
    }

    public function suspendBusiness(Business $business)
    {
        $business->setStatus('suspended');
        $this->em->flush();

        return $business;
    }

    public function getActiveSubscription(Business $business)
    {
        return $this->subscriptionRepo->findActiveByBusiness($business->getId());
    }

    public function hasFeature(Business $business, string $feature)
    {
        $subscription = $this->getActiveSubscription($business);
        if (!$subscription) {
            return false;
        }

        $features = [
            'basic' => ['catalog', 'basic_analytics', 'api_access'],
            'professional' => ['catalog', 'basic_analytics', 'api_access', 'advanced_analytics', 'priority_support', 'custom_branding'],
            'enterprise' => ['catalog', 'basic_analytics', 'api_access', 'advanced_analytics', 'priority_support', 'custom_branding', 'multi_user', 'webhooks', 'white_label']
        ];

        $planFeatures = $features[$subscription->getPlan()] ?? [];

        return in_array($feature, $planFeatures, true);
    }

    public function validateApiKey(string $key)
    {
        $apiKey = $this->apiKeyRepo->findByKey($key);
        if ($apiKey) {
            return $apiKey->getBusiness();
        }

        return null;
    }

    public function createCategory(string $name, string $slug, ?string $description = null)
    {
        $category = new BusinessCategory();
        $category->setName($name);
        $category->setSlug($slug);
        $category->setDescription($description);
        $category->setIsActive(true);

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    public function getBusinessStats(Business $business)
    {
        $catalogItems = $this->catalogRepo->findByBusiness($business->getId());
        $subscriptions = $this->subscriptionRepo->findByBusiness($business->getId());
        $activeKeys = $this->apiKeyRepo->findActiveByBusiness($business->getId());
        $activeSubscription = $this->getActiveSubscription($business);

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
