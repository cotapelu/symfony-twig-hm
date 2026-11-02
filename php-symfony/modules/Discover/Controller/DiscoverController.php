<?php

declare(strict_types=1);

namespace App\Discover\Controller;

use App\Discover\Service\DiscoverService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[Route('/api/discover')]
class DiscoverController extends BaseController
{
    private DiscoverService $service;

    public function __construct(DiscoverService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    private function getUserIdFromRequest(Request $request): int
    {
        $userId = $request->attributes->get('user_id');
        if ($userId === null) $userId = $request->query->get('user_id', 0);
        if ($userId === null) {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
            $data = $dataOr400;
            $userId = $data['user_id'] ?? 0;
        }
        $userId = (int)$userId;
        if ($userId <= 0) throw new InvalidArgumentException('Valid user ID is required');
        return $userId;
    }

    private function validateItemCreationData(array $data): void
    {
        if (empty(trim($data['title'] ?? ''))) throw new InvalidArgumentException('Title is required');
        if (strlen($data['title']) > 255) throw new InvalidArgumentException('Title must not exceed 255 characters');
        if (empty(trim($data['type'] ?? ''))) throw new InvalidArgumentException('Type is required');
        if (!in_array($data['type'], ['place','event','activity'])) {
            throw new InvalidArgumentException('Invalid type. Valid types: place, event, activity');
        }
    }

    private function validateFloat(?string $value, string $fieldName): float
    {
        if ($value === null || !is_numeric($value)) throw new InvalidArgumentException("Valid $fieldName is required");
        $floatVal = (float)$value;
        if ($fieldName === 'latitude' && ($floatVal < -90 || $floatVal > 90)) throw new InvalidArgumentException('Latitude must be between -90 and 90');
        if ($fieldName === 'longitude' && ($floatVal < -180 || $floatVal > 180)) throw new InvalidArgumentException('Longitude must be between -180 and 180');
        return $floatVal;
    }

    private function validatePositiveFloat(float $value, string $fieldName, float $min = 0, ?float $max = null): float
    {
        if ($value <= $min) throw new InvalidArgumentException("$fieldName must be greater than $min");
        if ($max !== null && $value >= $max) throw new InvalidArgumentException("$fieldName must be less than $max");
        return $value;
    }

    private function validatePositiveInt(int $value, string $fieldName, int $min = 1, ?int $max = null): int
    {
        if ($value < $min) throw new InvalidArgumentException("$fieldName must be at least $min");
        if ($max !== null && $value > $max) throw new InvalidArgumentException("$fieldName must not exceed $max");
        return $value;
    }

    private function applyItemOptions(object $item, array $data): void
    {
        if (isset($data['description'])) $item->setDescription($data['description']);
        if (isset($data['image_url'])) $item->setImageUrl($data['image_url']);
        if (isset($data['address'])) $item->setAddress($data['address']);
        if (isset($data['latitude']) && isset($data['longitude'])) $item->setLocation((float)$data['latitude'], (float)$data['longitude']);
        if (isset($data['tags']) && is_array($data['tags'])) $item->setTags($data['tags']);
    }

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("DiscoverController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['error' => 'Internal server error'], 500);
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context]));
    }

    private function incrementMetric(string $metric): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'value'=>1]));
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->getUser();
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }
        return $user;
    }

    // ==================== Endpoints ====================

    #[Route('/items', name: 'discover_create_item', methods: ['POST'])]
    public function createItem(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createItemAction($request));
    }

    private function createItemAction(Request $request): JsonResponse
    {
        $userId = $this->getUserIdFromRequest($request);
        $data = $this->getJsonData($request);
        $this->validateItemCreationData($data);

        $item = $this->service->createItem($userId, $data['type'] ?? 'place', $data['title'] ?? '');
        $this->applyItemOptions($item, $data);

        $this->logAction('item_created', ['item_id'=>$item->getId(), 'user_id'=>$userId]);
        $this->incrementMetric('items_created');

        return $this->json(['data'=>$item], JsonResponse::HTTP_CREATED);
    }

    #[Route('/items', name: 'discover_get_items', methods: ['GET'])]
    public function getItems(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getItemsAction($request));
    }

    private function getItemsAction(Request $request): JsonResponse
    {
        $type = $request->query->get('type');
        $items = $type ? $this->service->getItemsByType($type) : $this->service->getTrendingItems(50);
        return $this->json(['data'=>array_values($items)]);
    }

    #[Route('/items/{id}', name: 'discover_get_item', methods: ['GET'])]
    public function getItem(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getItemAction($id));
    }

    private function getItemAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Item ID must be a positive integer');
        $item = $this->service->getItem($id);
        if (!$item) return $this->json(['error' => 'Item not found'], 404);
        return $this->json(['data'=>$item]);
    }

    #[Route('/items/{id}', name: 'discover_update_item', methods: ['PUT'])]
    public function updateItem(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateItemAction($id, $request));
    }

    private function updateItemAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Item ID must be a positive integer');
        $data = $this->getJsonData($request);
        $item = $this->service->updateItem($id, $data);
        if (!$item) return $this->json(['error' => 'Item not found'], 404);
        $this->logAction('item_updated', ['item_id'=>$id]);
        return $this->json(['data'=>$item]);
    }

    #[Route('/items/{id}', name: 'discover_delete_item', methods: ['DELETE'])]
    public function deleteItem(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->deleteItemAction($id, $request));
    }

    private function deleteItemAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Item ID must be a positive integer');
        $deleted = $this->service->deleteItem($id);
        if (!$deleted) return $this->json(['error' => 'Item not found'], 404);
        $this->logAction('item_deleted', ['item_id'=>$id]);
        return $this->json([], JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/nearby', name: 'discover_get_nearby_items', methods: ['GET'])]
    public function getNearbyItems(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getNearbyItemsAction($request));
    }

    private function getNearbyItemsAction(Request $request): JsonResponse
    {
        $lat = $this->validateFloat($request->query->get('lat'), 'latitude');
        $lng = $this->validateFloat($request->query->get('lng'), 'longitude');
        $radius = $this->validatePositiveFloat((float)($request->query->get('radius', 5)), 'radius', 0.1, 100);
        $items = $this->service->getNearbyItems($lat, $lng, $radius);
        return $this->json(['data'=>array_values($items)]);
    }

    #[Route('/search', name: 'discover_search_items', methods: ['GET'])]
    public function searchItems(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchItemsAction($request));
    }

    private function searchItemsAction(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $items = $this->service->searchItems($query);
        return $this->json(['data'=>array_values($items)]);
    }

    #[Route('/trending', name: 'discover_get_trending_items', methods: ['GET'])]
    public function getTrendingItems(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getTrendingItemsAction($request));
    }

    private function getTrendingItemsAction(Request $request): JsonResponse
    {
        $limit = $this->validatePositiveInt((int)($request->query->get('limit', 10)), 'limit', 1, 100);
        $items = $this->service->getTrendingItems($limit);
        return $this->json(['data'=>array_values($items)]);
    }

    #[Route('/items/{id}/score', name: 'discover_increment_score', methods: ['POST'])]
    public function incrementScore(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->incrementScoreAction($id));
    }

    private function incrementScoreAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Item ID must be a positive integer');
        $item = $this->service->incrementScore($id);
        if (!$item) return $this->json(['error' => 'Item not found'], 404);
        $this->logAction('item_score_incremented', ['item_id'=>$id]);
        return $this->json(['data'=>$item]);
    }

    #[Route('/trending', name: 'discover_get_trending', methods: ['GET'])]
    public function getTrending(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getTrendingAction($request));
    }

    private function getTrendingAction(Request $request): JsonResponse
    {
        $limit = $this->validatePositiveInt((int)($request->query->get('limit', 20)), 'limit', 1, 100);
        $type = $request->query->get('type');
        if ($type) {
            $items = $this->service->getTrendingByType($type, $limit);
        } else {
            $items = $this->service->getTrendingContent($limit);
        }
        return $this->json(array_values($items));
    }

    #[Route('/recommended', name: 'discover_get_recommended', methods: ['GET'])]
    public function getRecommended(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getRecommendedAction($request));
    }

    private function getRecommendedAction(Request $request): JsonResponse
    {
        $limit = $this->validatePositiveInt((int)($request->query->get('limit', 20)), 'limit', 1, 100);
        $user = $this->getCurrentUser();
        if (!$user) throw new AccessDeniedHttpException('Authentication required');
        $userId = $user->getId();
        $items = $this->service->getRecommendedContent($userId, $limit);
        return $this->json($items);
    }

    #[Route('/actions/track', name: 'discover_track_action', methods: ['POST'])]
    public function trackAction(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->trackActionAction($request));
    }

    private function trackActionAction(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        if (empty($data['action_type']) || !is_string($data['action_type'])) {
            throw new InvalidArgumentException('action_type, content_type, and content_id are required');
        }
        if (empty($data['content_type']) || !is_string($data['content_type'])) {
            throw new InvalidArgumentException('action_type, content_type, and content_id are required');
        }
        $user = $this->getCurrentUser();
        if (!$user) throw new AccessDeniedHttpException('Authentication required');
        $userId = $user->getId();
        $contentId = $data['content_id'] ?? null;
        $result = $this->service->trackAction($userId, $data['action_type'], $data['content_type'], $contentId);
        if (!$result) {
            return $this->json(['error' => 'Failed to track action'], 500);
        }
        if (is_object($result) && method_exists($result, 'toArray')) {
            return $this->json(['message' => 'Action tracked successfully', 'action' => $result->toArray()], 201);
        }
        return $this->json(['message' => 'Action tracked'], 200);
    }

    #[Route('/items/{id}/view', name: 'discover_track_view', methods: ['POST'])]
    public function trackView(mixed $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->trackViewAction($id));
    }

    private function trackViewAction(mixed $id): JsonResponse
    {
        $id = (int)$id;
        if ($id <= 0) throw new InvalidArgumentException('Item ID must be positive');
        $user = $this->getCurrentUser();
        if (!$user) throw new AccessDeniedHttpException('Authentication required');
        $userId = $user->getId();
        $this->service->incrementViewCount('item', $id);
        $this->service->trackAction($userId, 'view', 'item', $id);
        return $this->json(['message' => 'View tracked successfully']);
    }

    #[Route('/history', name: 'discover_user_history', methods: ['GET'])]
    public function getUserHistory(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getUserHistoryAction($request));
    }

    private function getUserHistoryAction(Request $request): JsonResponse
    {
        $limit = $this->validatePositiveInt((int)($request->query->get('limit', 20)), 'limit', 1, 100);
        $user = $this->getCurrentUser();
        if (!$user) throw new AccessDeniedHttpException('Authentication required');
        $userId = $user->getId();
        $history = $this->service->getUserDiscoverHistory($userId, $limit);
        return $this->json(array_values($history));
    }

    #[Route('/recommendations/refresh', name: 'discover_refresh_recommendations', methods: ['POST'])]
    public function refreshRecommendations(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->refreshRecommendationsAction($request));
    }

    private function refreshRecommendationsAction(Request $request): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (!$user) throw new AccessDeniedHttpException('Authentication required');
        $userId = $user->getId();
        $this->service->refreshRecommendations($userId);
        return $this->json(['message' => 'Recommendations refreshed successfully']);
    }

    #[Route('/search', name: 'discover_search', methods: ['GET'])]
    public function searchContent(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchContentAction($request));
    }

    private function searchContentAction(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        if (empty($query)) {
            throw new InvalidArgumentException('Search query is required');
        }
        $type = $request->query->get('type');
        $limit = $this->validatePositiveInt((int)($request->query->get('limit', 20)), 'limit', 1, 100);
        $results = $this->service->searchContent($query, $type, $limit);
        return $this->json(array_values($results));
    }
}

