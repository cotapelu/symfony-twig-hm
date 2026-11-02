<?php

declare(strict_types=1);

namespace App\BusinessAccount\Controller;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\BusinessCategory;
use App\BusinessAccount\Entity\CatalogItem;
use App\BusinessAccount\Entity\Subscription;
use App\BusinessAccount\Entity\ApiKey;
use App\BusinessAccount\Service\BusinessAccountService;
use App\BusinessAccount\Repository\BusinessCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use InvalidArgumentException;
use DateTimeImmutable;

/**
 * Business Account Controller - Refactored for security & clarity.
 * All endpoints enforce ownership where appropriate.
 * All functions ≤20 lines, complexity ≤10.
 */
#[Route('/api/business')]
class BusinessAccountController extends BaseController
{
    private BusinessAccountService $service;

    public function __construct(BusinessAccountService $service)
    {
        $this->service = $service;
    }

    // ========== PUBLIC API ==========

    #[Route('/register', name: 'business_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $this->validateRegistrationData($data);
            $business = $this->service->createBusiness($data);
            return $this->json([
                'message' => 'Business registered successfully',
                'business' => $this->formatBusiness($business)
            ], Response::HTTP_CREATED);
        });
    }

    #[Route('/profile', name: 'business_profile', methods: ['GET'])]
    public function getProfile(#[CurrentUser] Business $business): JsonResponse
    {
        return $this->handle(fn() => $this->json([
            'business' => $this->formatBusiness($business),
            'stats' => $this->service->getBusinessStats($business)
        ]));
    }

    #[Route('/profile', name: 'business_profile_update', methods: ['PUT'])]
    public function updateProfile(#[CurrentUser] Business $business, Request $request): JsonResponse
    {
        return $this->handle(function() use ($business, $request) {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $business = $this->service->updateBusiness($business, $data);
            return $this->json([
                'message' => 'Profile updated successfully',
                'business' => $this->formatBusiness($business)
            ]);
        });
    }

    #[Route('/catalog', name: 'business_catalog_list', methods: ['GET'])]
    public function listCatalog(#[CurrentUser] Business $business): JsonResponse
    {
        return $this->handle(function() use ($business) {
            $items = $this->service->getCatalogRepository()->findActiveByBusiness($business->getId());
            return $this->json([
                'items' => array_map(fn($item) => $this->formatCatalogItem($item), $items)
            ]);
        });
    }

    #[Route('/catalog', name: 'business_catalog_create', methods: ['POST'])]
    public function createCatalogItem(#[CurrentUser] Business $business, Request $request): JsonResponse
    {
        return $this->handle(function() use ($business, $request) {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $this->validateCatalogItemData($data);
            if (!$this->service->hasFeature($business, 'catalog')) {
                throw new InvalidArgumentException('Catalog feature not available in your plan', Response::HTTP_FORBIDDEN);
            }
            $item = $this->service->createCatalogItem($business, $data);
            return $this->json([
                'message' => 'Catalog item created successfully',
                'item' => $this->formatCatalogItem($item)
            ], Response::HTTP_CREATED);
        });
    }

    #[Route('/catalog/{id}', name: 'business_catalog_update', methods: ['PUT'])]
    public function updateCatalogItem(#[CurrentUser] Business $currentBusiness, CatalogItem $item, Request $request): JsonResponse
    {
        return $this->handle(function() use ($currentBusiness, $item, $request) {
            if ($item->getBusiness()->getId() !== $currentBusiness->getId()) {
                throw new AccessDeniedException('Not catalog item owner');
            }
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
                        $item = $this->service->updateCatalogItem($item, $data);
            return $this->json([
                'message' => 'Catalog item updated successfully',
                'item' => $this->formatCatalogItem($item)
            ]);
        });
    }

    #[Route('/catalog/{id}', name: 'business_catalog_delete', methods: ['DELETE'])]
    public function deleteCatalogItem(#[CurrentUser] Business $currentBusiness, CatalogItem $item): JsonResponse
    {
        return $this->handle(function() use ($currentBusiness, $item) {
            if ($item->getBusiness()->getId() !== $currentBusiness->getId()) {
                throw new AccessDeniedException('Not catalog item owner');
            }
            $this->service->deleteCatalogItem($item);
            return $this->json(['message' => 'Catalog item deleted successfully']);
        });
    }

    #[Route('/subscription', name: 'business_subscription', methods: ['GET'])]
    public function getSubscription(#[CurrentUser] Business $business): JsonResponse
    {
        return $this->handle(function() use ($business) {
            $subscription = $this->service->getActiveSubscription($business);
            if (!$subscription) {
                return $this->json(['message' => 'No active subscription', 'subscription' => null]);
            }
            return $this->json(['subscription' => $this->formatSubscription($subscription)]);
        });
    }

    #[Route('/subscription', name: 'business_subscription_create', methods: ['POST'])]
    public function createSubscription(#[CurrentUser] Business $business, Request $request): JsonResponse
    {
        return $this->handle(function() use ($business, $request) {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $this->validateSubscriptionData($data);
            $subscription = $this->service->createSubscription($business, $data['plan'], $data['months']);
            return $this->json([
                'message' => 'Subscription created successfully',
                'subscription' => $this->formatSubscription($subscription)
            ], Response::HTTP_CREATED);
        });
    }

    #[Route('/subscription/renew', name: 'business_subscription_renew', methods: ['POST'])]
    public function renewSubscription(#[CurrentUser] Business $business, Request $request): JsonResponse
    {
        return $this->handle(function() use ($business, $request) {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $months = $data['months'] ?? 1;
            $this->validatePositiveInt($months, 'months', 1);
            $subscription = $this->service->getActiveSubscription($business);
            if (!$subscription) {
                throw new InvalidArgumentException('No active subscription to renew', Response::HTTP_BAD_REQUEST);
            }
            $subscription = $this->service->renewSubscription($subscription, $months);
            return $this->json([
                'message' => 'Subscription renewed successfully',
                'subscription' => $this->formatSubscription($subscription)
            ]);
        });
    }

    #[Route('/api-keys', name: 'business_api_keys', methods: ['GET'])]
    public function listApiKeys(#[CurrentUser] Business $business): JsonResponse
    {
        return $this->handle(function() use ($business) {
            $keys = $this->service->getApiKeyRepository()->findByBusiness($business->getId());
            return $this->json([
                'keys' => array_map(fn($key) => $this->formatApiKey($key), $keys)
            ]);
        });
    }

    #[Route('/api-keys', name: 'business_api_keys_create', methods: ['POST'])]
    public function generateApiKey(#[CurrentUser] Business $business, Request $request): JsonResponse
    {
        return $this->handle(function() use ($business, $request) {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $name = $data['name'] ?? null;
            if (!$this->service->hasFeature($business, 'api_access')) {
                throw new AccessDeniedException('API access not available in your plan');
            }
            $apiKey = $this->service->generateApiKey($business, $name);
            return $this->json([
                'message' => 'API key generated successfully',
                'key' => $this->formatApiKey($apiKey, true)
            ], Response::HTTP_CREATED);
        });
    }

    #[Route('/api-keys/{id}/revoke', name: 'business_api_keys_revoke', methods: ['POST'])]
    public function revokeApiKey(#[CurrentUser] Business $currentBusiness, ApiKey $apiKey): JsonResponse
    {
        return $this->handle(function() use ($currentBusiness, $apiKey) {
            if ($apiKey->getBusiness()->getId() !== $currentBusiness->getId()) {
                throw new InvalidArgumentException('Not API key owner', Response::HTTP_FORBIDDEN);
            }
            $this->service->revokeApiKey($apiKey);
            return $this->json(['message' => 'API key revoked successfully']);
        });
    }

    #[Route('/verify/{id}', name: 'business_verify', methods: ['POST'])]
    public function verifyBusiness(#[CurrentUser] Business $currentBusiness, int $id): JsonResponse
    {
        return $this->handle(function() use ($currentBusiness, $id) {
            if ((string)$currentBusiness->getId() !== (string)$id) {
                throw new InvalidArgumentException('Cannot verify business you do not own', Response::HTTP_FORBIDDEN);
            }
            $business = $this->service->getBusinessRepository()->find($id);
            if (!$business) {
                throw new InvalidArgumentException('Business not found', Response::HTTP_NOT_FOUND);
            }
            $business = $this->service->verifyBusiness($business);
            return $this->json([
                'message' => 'Business verified successfully',
                'business' => $this->formatBusiness($business)
            ]);
        });
    }

    #[Route('/approve/{id}', name: 'business_approve', methods: ['POST'])]
    public function approveBusiness(#[CurrentUser] Business $currentBusiness, int $id): JsonResponse
    {
        return $this->handle(function() use ($currentBusiness, $id) {
            if ((string)$currentBusiness->getId() !== (string)$id) {
                throw new InvalidArgumentException('Cannot approve business you do not own', Response::HTTP_FORBIDDEN);
            }
            $business = $this->service->getBusinessRepository()->find($id);
            if (!$business) {
                throw new InvalidArgumentException('Business not found', Response::HTTP_NOT_FOUND);
            }
            $business = $this->service->approveBusiness($business);
            return $this->json([
                'message' => 'Business approved successfully',
                'business' => $this->formatBusiness($business)
            ]);
        });
    }

    #[Route('/suspend/{id}', name: 'business_suspend', methods: ['POST'])]
    public function suspendBusiness(#[CurrentUser] Business $currentBusiness, int $id): JsonResponse
    {
        return $this->handle(function() use ($currentBusiness, $id) {
            if ((string)$currentBusiness->getId() !== (string)$id) {
                throw new InvalidArgumentException('Cannot suspend business you do not own', Response::HTTP_FORBIDDEN);
            }
            $business = $this->service->getBusinessRepository()->find($id);
            if (!$business) {
                throw new InvalidArgumentException('Business not found', Response::HTTP_NOT_FOUND);
            }
            $business = $this->service->suspendBusiness($business);
            return $this->json([
                'message' => 'Business suspended successfully',
                'business' => $this->formatBusiness($business)
            ]);
        });
    }

    #[Route('/categories', name: 'business_categories', methods: ['GET'])]
    public function listCategories(BusinessCategoryRepository $repo): JsonResponse
    {
        return $this->handle(function() use ($repo) {
            $categories = $repo->findActiveCategories();
            return $this->json([
                'categories' => array_map(fn($cat) => $this->formatCategory($cat), $categories)
            ]);
        });
    }

    #[Route('/categories', name: 'business_categories_create', methods: ['POST'])]
    public function createCategory(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handle(function() use ($request, $em) {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $this->validateCategoryData($data);
            $category = $this->service->createCategory(
                $data['name'],
                $data['slug'],
                $data['description'] ?? null
            );
            return $this->json([
                'message' => 'Category created successfully',
                'category' => $this->formatCategory($category)
            ], Response::HTTP_CREATED);
        });
    }

    #[Route('/search', name: 'business_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $name = $request->query->get('name', '');
            $categoryId = $request->query->get('category');
            $status = $request->query->get('status', 'active');
            $verified = $request->query->get('verified');
            $businesses = $this->service->getBusinessRepository()->findByStatus($status);
            if ($name) {
                $businesses = array_filter($businesses, fn($b) => stripos($b->getName(), $name) !== false);
            }
            if ($categoryId) {
                $businesses = array_filter($businesses, fn($b) => $b->getCategory() && $b->getCategory()->getId() == $categoryId);
            }
            if ($verified !== null) {
                $isVerified = $verified === 'true';
                $businesses = array_filter($businesses, fn($b) => $b->isVerified() === $isVerified);
            }
            return $this->json([
                'businesses' => array_map(fn($b) => $this->formatBusiness($b), $businesses),
                'count' => count($businesses)
            ]);
        });
    }

    // ========== PRIVATE HELPERS ==========


    private function validateRegistrationData(array $data): void
    {
        $this->validateName($data);
        $this->validateEmail($data);
        $this->validatePassword($data);
        $this->validateLatitude($data);
        $this->validateLongitude($data);
        $this->validateWebsite($data);
        $this->validateSocialUrls($data);
    }

    private function validateName(array $data): void
    {
        if (!isset($data['name']) || trim($data['name']) === '') {
            throw new InvalidArgumentException('Name is required');
        }
    }

    private function validateEmail(array $data): void
    {
        if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Valid email is required');
        }
    }

    private function validatePassword(array $data): void
    {
        if (!isset($data['password']) || strlen($data['password']) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters');
        }
    }

    private function validateLatitude(array $data): void
    {
        if (isset($data['latitude']) && $data['latitude'] !== null && $data['latitude'] !== '' && (($lat = (float)$data['latitude']) < -90 || $lat > 90)) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90 degrees');
        }
    }

    private function validateLongitude(array $data): void
    {
        if (isset($data['longitude']) && $data['longitude'] !== null && $data['longitude'] !== '' && (($lng = (float)$data['longitude']) < -180 || $lng > 180)) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180 degrees');
        }
    }

    private function validateWebsite(array $data): void
    {
        if (isset($data['website']) && $data['website'] !== null && $data['website'] !== '' && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid website URL');
        }
    }

    private function validateSocialUrls(array $data): void
    {
        foreach (['facebook', 'instagram'] as $field) {
            if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '' && !filter_var($data[$field], FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException("Invalid $field URL");
            }
        }
    }

    private function validateCatalogItemData(array $data): void
    {
        $this->validateCatalogName($data);
        $this->validateCatalogPrice($data);
        $this->validateCatalogDescription($data);
        $this->validateCatalogImageUrl($data);
        $this->validateCatalogStock($data);
    }

    private function validateCatalogName(array $data): void
    {
        if (!isset($data['name']) || trim($data['name']) === '') {
            throw new InvalidArgumentException('Name is required');
        }
    }

    private function validateCatalogPrice(array $data): void
    {
        if (!isset($data['price']) || !is_numeric($data['price']) || (float)$data['price'] <= 0) {
            throw new InvalidArgumentException('Valid price > 0 required');
        }
    }

    private function validateCatalogDescription(array $data): void
    {
        if (isset($data['description']) && $data['description'] !== null && strlen($data['description']) > 1000) {
            throw new InvalidArgumentException('Description ≤1000 chars');
        }
    }

    private function validateCatalogImageUrl(array $data): void
    {
        if (isset($data['imageUrl']) && $data['imageUrl'] !== null && $data['imageUrl'] !== '' && !filter_var($data['imageUrl'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid image URL');
        }
    }

    private function validateCatalogStock(array $data): void
    {
        if (isset($data['stock']) && $data['stock'] !== null && (!is_numeric($data['stock']) || (int)$data['stock'] < 0)) {
            throw new InvalidArgumentException('Stock must be non-negative integer');
        }
    }

    private function validateSubscriptionData(array $data): void
    {
        if (!isset($data['plan']) || trim($data['plan']) === '') throw new InvalidArgumentException('Plan is required');
        if (!isset($data['months']) || !is_numeric($data['months']) || (int)$data['months'] <= 0) throw new InvalidArgumentException('Months must be positive integer');
        if (!in_array($data['plan'], ['basic', 'professional', 'enterprise'], true)) throw new InvalidArgumentException('Invalid plan');
    }

    private function validateCategoryData(array $data): void
    {
        if (!isset($data['name']) || trim($data['name']) === '') throw new InvalidArgumentException('Name is required');
        if (!isset($data['slug']) || trim($data['slug']) === '') throw new InvalidArgumentException('Slug is required');
    }

    private function validatePositiveInt(int $value, string $fieldName, int $min = 1): void
    {
        if ($value < $min) throw new InvalidArgumentException("$fieldName must be at least $min");
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            $status = is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : Response::HTTP_BAD_REQUEST;
            return $this->json(['error' => $e->getMessage()], $status);
        } catch (\Exception $e) {
            $this->logAction('controller_error', ['error' => $e->getMessage()]);
            $this->incrementMetric('controller_errors');
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function logAction(string $action, array $context = []): void
    {
        $logEntry = [
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'action' => $action,
            'context' => $context,
        ];
        error_log(json_encode($logEntry));
    }

    private function incrementMetric(string $metricName): void
    {
        error_log(json_encode([
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'type' => 'metric',
            'name' => $metricName,
            'value' => 1,
        ]));
    }

    private function formatBusiness(Business $business): array
    {
        return [
            'id' => $business->getId(),
            'name' => $business->getName(),
            'email' => $business->getEmail(),
            'phone' => $business->getPhone(),
            'description' => $business->getDescription(),
            'address' => $business->getAddress(),
            'website' => $business->getWebsite(),
            'category' => $business->getCategory() ? ['id' => null, 'name' => $business->getCategory()] : null,
            'isVerified' => $business->isVerified(),
            'status' => $business->getStatus(),
            'createdAt' => $business->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $business->getUpdatedAt()?->format('Y-m-d H:i:s')
        ];
    }

    private function formatCatalogItem(CatalogItem $item): array
    {
        return [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'description' => $item->getDescription(),
            'price' => $item->getPrice(),
            'currency' => $item->getCurrency(),
            'category' => $item->getCategory(),
            'imageUrl' => $item->getImageUrl(),
            'stock' => $item->getStock(),
            'isActive' => $item->isAvailable(),
            'createdAt' => $item->getCreatedAt()?->format('Y-m-d H:i:s')
        ];
    }

    private function formatSubscription(Subscription $subscription): array
    {
        return [
            'id' => $subscription->getId(),
            'plan' => $subscription->getPlan(),
            'price' => $subscription->getPrice(),
            'status' => $subscription->getStatus(),
            'startedAt' => $subscription->getStartedAt()?->format('Y-m-d H:i:s'),
            'expiresAt' => $subscription->getExpiresAt()?->format('Y-m-d H:i:s')
        ];
    }

    private function formatApiKey(ApiKey $key, bool $showValue = false): array
    {
        return [
            'id' => $key->getId(),
            'name' => $key->getName(),
            'key' => $showValue ? $key->getKey() : substr($key->getKey(), 0, 8) . '...',
            'isActive' => $key->getStatus() === ApiKey::STATUS_ACTIVE,
            'createdAt' => $key->getCreatedAt()?->format('Y-m-d H:i:s'),
            'lastUsedAt' => $key->getLastUsedAt()?->format('Y-m-d H:i:s')
        ];
    }

    private function formatCategory(BusinessCategory $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'isActive' => $category->isActive()
        ];
    }
}
