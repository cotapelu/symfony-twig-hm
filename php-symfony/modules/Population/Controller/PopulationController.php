<?php

declare(strict_types=1);

namespace App\Population\Controller;

use App\Population\Service\PopulationService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use DateTimeImmutable;

#[Route('/api/population')]
class PopulationController extends BaseController
{
    private const MAX_NAME_LENGTH = 255;
    private const MAX_ADDRESS_LENGTH = 255;
    private const MAX_HOUSEHOLD_ID_LENGTH = 50;
    private const MAX_CITIZEN_ID_LENGTH = 20;
    private const ALLOWED_GENDERS = ['Nam', 'Nữ'];
    private const ALLOWED_STATUSES = ['Thường trú', 'Tạm trú'];
    private const ALLOWED_TYPES = ['Công dân', 'Trẻ em', 'Người cao tuổi'];

    public function __construct(
        private PopulationService $populationService,
        private ?TokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authChecker = null
    ) {
        parent::__construct();
        $this->authChecker = $authChecker;
    }

    private function isAuthenticated(): bool
    {
        if ($this->tokenStorage === null || $this->authChecker === null) {
            return false;
        }
        return $this->tokenStorage->getToken() !== null
            && $this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    private function requireAuth(): JsonResponse|true
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        return true;
    }

    private function getAuthenticatedUserId(): int
    {
        if (!$this->isAuthenticated()) {
            throw new AccessDeniedException('Authentication required');
        }
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            throw new AccessDeniedException('Invalid user');
        }
        return (int) $user->getId();
    }

    private function canAccessRecord(string $recordOwnerId): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        $userId = (string) $this->getAuthenticatedUserId();
        return $recordOwnerId === $userId || $this->authChecker->isGranted('ROLE_ADMIN');
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        $filter = $request->query->get('filter', 'all');
        $search = $request->query->get('search', '');

        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }
        if ($filter !== 'all' && !in_array($filter, self::ALLOWED_TYPES, true)) {
            return $this->json(['error' => 'Invalid filter'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->populationService->getPopulationList($page, $limit, $filter, $search);

        return $this->json($result);
    }

    #[Route('/statistics', methods: ['GET'])]
    public function statistics(): JsonResponse
    {
        $stats = $this->populationService->getStatistics();
        return $this->json($stats);
    }

    #[Route('/age-distribution', methods: ['GET'])]
    public function ageDistribution(): JsonResponse
    {
        $distribution = $this->populationService->getAgeDistribution();
        return $this->json($distribution);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        if (empty($id) || strlen($id) > 50) {
            return $this->json(['error' => 'Invalid ID'], Response::HTTP_BAD_REQUEST);
        }

        $record = $this->populationService->getById($id);

        if (!$record) {
            return $this->json(['error' => 'Population record not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessRecord((string) ($record['user_id'] ?? 0))) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($record);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) {
            return $authCheck;
        }

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;

        try {
            $userId = $this->getAuthenticatedUserId();
            $record = $this->populationService->create($data, $userId);
            return $this->json($record, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) {
            return $authCheck;
        }

        if (empty($id) || strlen($id) > 50) {
            return $this->json(['error' => 'Invalid ID'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->populationService->getById($id);
        if ($existing && !$this->canAccessRecord((string) ($existing['user_id'] ?? 0))) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;

        try {
            $userId = $this->getAuthenticatedUserId();
            $record = $this->populationService->update($id, $data, $userId);

            if (!$record) {
                return $this->json(['error' => 'Population record not found'], Response::HTTP_NOT_FOUND);
            }

            return $this->json($record);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $authCheck = $this->requireAuth();
        if ($authCheck instanceof JsonResponse) {
            return $authCheck;
        }

        if (empty($id) || strlen($id) > 50) {
            return $this->json(['error' => 'Invalid ID'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->populationService->getById($id);
        if ($existing && !$this->canAccessRecord((string) ($existing['user_id'] ?? 0))) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $result = $this->populationService->delete($id, $this->getAuthenticatedUserId());

        if (!$result) {
            return $this->json(['error' => 'Population record not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['message' => 'Deleted successfully']);
    }

    #[Route('/household/{householdId}', methods: ['GET'])]
    public function getByHousehold(string $householdId): JsonResponse
    {
        if (empty(trim($householdId)) || strlen($householdId) > self::MAX_HOUSEHOLD_ID_LENGTH) {
            return $this->json(['error' => 'Invalid household ID'], Response::HTTP_BAD_REQUEST);
        }

        $records = $this->populationService->getByHousehold($householdId);
        return $this->json($records);
    }

    #[Route('/search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) > 100) {
            return $this->json(['error' => 'Search query too long'], Response::HTTP_BAD_REQUEST);
        }

        $results = $this->populationService->search($query);
        return $this->json($results);
    }
}