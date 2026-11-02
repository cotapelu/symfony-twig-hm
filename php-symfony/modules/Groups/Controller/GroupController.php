<?php

declare(strict_types=1);

namespace App\Groups\Controller;

use App\Groups\Service\GroupService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/groups')]
class GroupController extends BaseController
{
    public function __construct(private readonly GroupService $groupService)
    {
        parent::__construct();
    }

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->handleException('groups', $e);
        }
    }

    // ==================== Endpoints ====================

    #[Route('', name: 'groups_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createAction($request));
    }

    private function createAction(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        $this->validateName($data['name'] ?? '');
        $this->validateOptionalCreateFields($data);
        $ownerId = $this->getAuthenticatedUserId();
        $params = $this->buildCreateGroupParams($data);
        $group = $this->groupService->createGroup(
            $params['name'],
            $ownerId,
            $params['description'],
            $params['avatar'],
            $params['isPublic'],
            $params['isCommunity'],
            $params['isChannel'],
            $params['isForum'],
            $params['maxMembers'],
            $params['settings']
        );
        $this->logAction('group_created', ['group_id' => $group->getId(), 'owner_id' => $ownerId]);
        $this->incrementMetric('groups_created');
        return $this->json(['group' => $group->toArray()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'groups_update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateAction($id, $request));
    }

    private function updateAction(string $id, Request $request): JsonResponse
    {
        $this->validateId($id);
        $userId = $this->getAuthenticatedUserId();
        $group = $this->groupService->getGroup($id);
        if (!$group) throw new NotFoundHttpException('Group not found');
        if ($group->getOwnerId() !== $userId) throw new InvalidArgumentException('Access denied: only owner can update group');
        $data = $this->getJsonData($request);
        if (isset($data['name'])) $this->validateName($data['name']);
        if (isset($data['maxMembers'])) $this->validateMaxMembers((int)$data['maxMembers']);
        if (isset($data['avatar'])) $this->validateAvatarUrl($data['avatar']);
        $updated = $this->groupService->updateGroup($id, $data);
        if (!$updated) throw new InvalidArgumentException('Failed to update group');
        $this->logAction('group_updated', ['group_id' => $id, 'user_id' => $userId]);
        return $this->json(['group' => $updated->toArray()]);
    }

    #[Route('/{id}', name: 'groups_delete', methods: ['DELETE'])]
    public function delete(string $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->deleteAction($id, $request));
    }

    private function deleteAction(string $id, Request $request): JsonResponse
    {
        $this->validateId($id);
        $userId = $this->getAuthenticatedUserId();
        $group = $this->groupService->getGroup($id);
        if (!$group) throw new NotFoundHttpException('Group not found');
        if ($group->getOwnerId() !== $userId) throw new InvalidArgumentException('Access denied: only owner can delete group');
        $success = $this->groupService->deleteGroup($id);
        if (!$success) throw new InvalidArgumentException('Failed to delete group');
        $this->logAction('group_deleted', ['group_id' => $id, 'owner_id' => $userId]);
        $this->incrementMetric('groups_deleted');
        return $this->json(['message' => 'Group deleted successfully']);
    }

    #[Route('/{id}', name: 'groups_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->handle(fn() => $this->showAction($id));
    }

    private function showAction(string $id): JsonResponse
    {
        $this->validateId($id);
        $group = $this->groupService->getGroup($id);
        if (!$group) throw new NotFoundHttpException('Group not found');
        return $this->json(['group' => $group->toArray()]);
    }

    #[Route('', name: 'groups_list_public', methods: ['GET'])]
    public function listPublic(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->listPublicAction($request));
    }

    private function listPublicAction(Request $request): JsonResponse
    {
        $limit = $this->validatePositiveInt((int) $request->query->get('limit', 50), 'limit', 1, 100);
        $offset = $this->validatePositiveInt((int) $request->query->get('offset', 0), 'offset', 0);
        $groups = $this->groupService->getPublicGroups($limit, $offset);
        $this->logAction('public_groups_listed', ['count' => count($groups)]);
        return $this->json([
            'groups' => array_map(fn($g) => $g->toArray(), $groups),
            'total' => count($groups),
        ]);
    }

    #[Route('/communities', name: 'groups_list_communities', methods: ['GET'])]
    public function listCommunities(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->listCommunitiesAction($request));
    }

    private function listCommunitiesAction(Request $request): JsonResponse
    {
        $limit = $this->validatePositiveInt((int) $request->query->get('limit', 50), 'limit', 1, 100);
        $offset = $this->validatePositiveInt((int) $request->query->get('offset', 0), 'offset', 0);
        $groups = $this->groupService->getCommunities($limit, $offset);
        $this->logAction('communities_listed', ['count' => count($groups)]);
        return $this->json([
            'groups' => array_map(fn($g) => $g->toArray(), $groups),
            'total' => count($groups),
        ]);
    }

    #[Route('/channels', name: 'groups_list_channels', methods: ['GET'])]
    public function listChannels(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->listChannelsAction($request));
    }

    private function listChannelsAction(Request $request): JsonResponse
    {
        $limit = $this->validatePositiveInt((int) $request->query->get('limit', 50), 'limit', 1, 100);
        $offset = $this->validatePositiveInt((int) $request->query->get('offset', 0), 'offset', 0);
        $groups = $this->groupService->getChannels($limit, $offset);
        $this->logAction('channels_listed', ['count' => count($groups)]);
        return $this->json([
            'groups' => array_map(fn($g) => $g->toArray(), $groups),
            'total' => count($groups),
        ]);
    }

    #[Route('/user/{userId}', name: 'groups_user', methods: ['GET'])]
    public function getUserGroups(string $userId): JsonResponse
    {
        return $this->handle(fn() => $this->getUserGroupsAction($userId));
    }

    private function getUserGroupsAction(string $userId): JsonResponse
    {
        $this->validateId($userId);
        $authUserId = $this->getAuthenticatedUserId();
        if ($userId !== $authUserId) throw new InvalidArgumentException('Access denied');
        $groups = $this->groupService->getUserGroups($userId);
        $this->logAction('user_groups_fetched', ['user_id' => $userId, 'count' => count($groups)]);
        return $this->json([
            'groups' => array_map(fn($g) => $g->toArray(), $groups),
            'total' => count($groups),
        ]);
    }

    #[Route('/search', name: 'groups_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchAction($request));
    }

    private function searchAction(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = $this->validatePositiveInt((int) $request->query->get('limit', 20), 'limit', 1, 100);
        if (empty($query)) throw new InvalidArgumentException('Search query is required');
        $groups = $this->groupService->searchGroups($query, $limit);
        $this->logAction('groups_searched', ['query' => $query, 'results' => count($groups)]);
        return $this->json([
            'groups' => array_map(fn($g) => $g->toArray(), $groups),
            'total' => count($groups),
        ]);
    }

    // ========== PRIVATE HELPERS ==========

    private function getAuthenticatedUserId(): string
    {
        $user = $this->getUser();
        if (!$user) throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Authentication required');
        return $user->getId();
    }

    private function validateName(string $name): void
    {
        $name = trim($name);
        if (empty($name)) throw new InvalidArgumentException('Group name is required');
        if (strlen($name) > 255) throw new InvalidArgumentException('Group name cannot exceed 255 characters');
    }

    private function validateMaxMembers(int $maxMembers): void
    {
        if ($maxMembers < 1) throw new InvalidArgumentException('maxMembers must be at least 1');
        if ($maxMembers > 100000) throw new InvalidArgumentException('maxMembers cannot exceed 100000');
    }

    private function validateAvatarUrl(?string $url): void
    {
        if ($url !== null && !filter_var($url, FILTER_VALIDATE_URL)) throw new InvalidArgumentException('Avatar must be a valid URL');
    }

    private function validateId(string $id): void
    {
        if (empty(trim($id))) throw new InvalidArgumentException('ID cannot be empty');
        if (strlen($id) > 36) throw new InvalidArgumentException('ID cannot exceed 36 characters');
    }

    private function validatePositiveInt(int $value, string $fieldName, int $min = 1, ?int $max = null): int
    {
        if ($value < $min) throw new InvalidArgumentException("$fieldName must be at least $min");
        if ($max !== null && $value > $max) throw new InvalidArgumentException("$fieldName must not exceed $max");
        return $value;
    }

    private function validateOptionalCreateFields(array $data): void
    {
        if (isset($data['maxMembers'])) $this->validateMaxMembers((int)$data['maxMembers']);
        if (isset($data['avatar'])) $this->validateAvatarUrl($data['avatar']);
    }

    private function buildCreateGroupParams(array $data): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'isPublic' => $data['isPublic'] ?? false,
            'isCommunity' => $data['isCommunity'] ?? false,
            'isChannel' => $data['isChannel'] ?? false,
            'isForum' => $data['isForum'] ?? false,
            'maxMembers' => $data['maxMembers'] ?? 500,
            'settings' => $data['settings'] ?? null,
        ];
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("GroupController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        $this->incrementMetric("{$context}_errors");
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
}