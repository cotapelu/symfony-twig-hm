<?php

declare(strict_types=1);

namespace App\Citizen\Infrastructure\Controller;

use App\Citizen\Application\Service\CitizenService;
use App\Citizen\Application\DTO\CreateCitizenRequest;
use App\Citizen\Application\DTO\CitizenResponse;
use App\Citizen\Domain\Entity\Citizen;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;
use DateTimeImmutable;

class CitizenController extends BaseController
{
    private const ROLE_CITIZEN = 'ROLE_CITIZEN';
    private const ROLE_OFFICER = 'ROLE_OFFICER';
    private const ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct(
        private readonly CitizenService $citizenService,
        private readonly ValidatorInterface $validator
    ) {
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
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return $this->handleException('controller', $e);
        }
    }

    // ==================== Endpoints ====================

    #[Route('', name: 'citizen_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->indexAction($request));
    }

    private function indexAction(Request $request): JsonResponse
    {
        $user = $this->requireAuthOr401($request);
        if ($user instanceof JsonResponse) return $user;
        $this->checkPermission($user, 'citizen:view');
        $filters = $this->extractFilters($request);
        $pagination = $this->extractPagination($request);
        $query = $request->query->get('q', '');
        $citizens = $this->citizenService->searchCitizens($query, $filters, $pagination['limit'], $pagination['offset']);
        return $this->json([
            'data' => array_map(fn($c) => $c->toArray(), $citizens),
            'meta' => ['limit'=>$pagination['limit'],'offset'=>$pagination['offset'],'query'=>$query,'filters'=>$filters]
        ]);
    }

    #[Route('', name: 'citizen_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createAction($request));
    }

    private function createAction(Request $request): JsonResponse
    {
        $user = $this->requireAuthOr401($request);
        if ($user instanceof JsonResponse) return $user;
        $this->checkPermission($user, 'citizen:create');
        $data = $this->getJsonData($request);
        $this->validateCitizenData($data);
        $data['user_id'] = $user['id'];
        $createRequest = CreateCitizenRequest::fromArray($data);
        $this->validateDto($createRequest);
        $citizen = $this->citizenService->createCitizen($createRequest);
        return $this->json(['data'=>$citizen->toArray(),'message'=>'Citizen created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'citizen_show', methods: ['GET'])]
    public function show(string $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->showAction($id, $request));
    }

    private function showAction(string $id, Request $request): JsonResponse
    {
        $user = $this->requireAuthOr401($request);
        if ($user instanceof JsonResponse) return $user;
        $this->checkPermission($user, $id === $user['id'] ? 'citizen:view_own' : 'citizen:view');
        $citizenId = $this->validateUuid($id, 'citizen ID');
        $citizen = $this->citizenService->getCitizen($citizenId, $user['id']);
        if (!$citizen) return $this->json(['error' => 'Citizen not found'], Response::HTTP_NOT_FOUND);
        $this->ensureCanAccessCitizen($user, $citizen);
        return $this->json(['data' => $citizen->toArray()]);
    }

    #[Route('/{id}', name: 'citizen_update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateAction($id, $request));
    }

    private function updateAction(string $id, Request $request): JsonResponse
    {
        $user = $this->requireAuthOr401($request);
        if ($user instanceof JsonResponse) return $user;
        $this->checkPermission($user, 'citizen:update');
        $citizenId = $this->validateUuid($id, 'citizen ID');
        $citizen = $this->citizenService->getCitizen($citizenId, $user['id']);
        if (!$citizen) return $this->json(['error' => 'Citizen not found'], Response::HTTP_NOT_FOUND);
        $this->ensureCanAccessCitizen($user, $citizen);
        $data = $this->getJsonData($request);
        $updatedCitizen = $this->citizenService->updateCitizen($citizenId, $data, $user['id']);
        return $this->json(['data'=>$updatedCitizen->toArray(),'message'=>'Citizen updated successfully']);
    }

    #[Route('/{id}', name: 'citizen_delete', methods: ['DELETE'])]
    public function delete(string $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->deleteAction($id, $request));
    }

    private function deleteAction(string $id, Request $request): JsonResponse
    {
        $user = $this->requireAuthOr401($request);
        if ($user instanceof JsonResponse) return $user;
        $this->checkPermission($user, 'citizen:delete');
        $citizenId = $this->validateUuid($id, 'citizen ID');
        $citizen = $this->citizenService->getCitizen($citizenId, $user['id']);
        if (!$citizen) return $this->json(['error' => 'Citizen not found'], Response::HTTP_NOT_FOUND);
        $this->ensureCanAccessCitizen($user, $citizen);
        $success = $this->citizenService->deleteCitizen($citizenId, $user['id']);
        if (!$success) return $this->json(['error' => 'Citizen not found'], Response::HTTP_NOT_FOUND);
        return $this->json(['message' => 'Citizen deleted successfully']);
    }

    #[Route('/statistics', name: 'citizen_statistics', methods: ['GET'])]
    public function statistics(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->statisticsAction($request));
    }

    private function statisticsAction(Request $request): JsonResponse
    {
        $user = $this->requireAuthOr401($request);
        if ($user instanceof JsonResponse) return $user;
        $this->checkPermission($user, 'citizen:view');
        $statistics = $this->citizenService->getCitizenStatistics();
        return $this->json(['data' => $statistics]);
    }

    #[Route('/household/{householdId}', name: 'citizen_by_household', methods: ['GET'])]
    public function getByHousehold(string $householdId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getByHouseholdAction($householdId, $request));
    }

    private function getByHouseholdAction(string $householdId, Request $request): JsonResponse
    {
        $user = $this->requireAuthOr401($request);
        if ($user instanceof JsonResponse) return $user;
        $this->checkPermission($user, 'citizen:view');
        $limit = $this->validatePositiveInt((int)($request->query->get('limit', 50)), 'limit', 1, 200);
        $offset = $this->validatePositiveInt((int)($request->query->get('offset', 0)), 'offset', 0, 10000);
        $citizens = $this->citizenService->getCitizensByHousehold($householdId, $limit, $offset);
        return $this->json(['data'=>array_map(fn($c)=>$c->toArray(), $citizens), 'meta'=>['householdId'=>$householdId,'limit'=>$limit,'offset'=>$offset,'count'=>count($citizens)]]);
    }

    #[Route('/elderly', name: 'citizen_elderly', methods: ['GET'])]
    public function getElderly(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getElderlyAction($request));
    }

    private function getElderlyAction(Request $request): JsonResponse
    {
        $user = $this->requireAuthOr401($request);
        if ($user instanceof JsonResponse) return $user;
        $this->checkPermission($user, 'citizen:view');
        $minAgeParam = $request->query->get('minAge', '60');
        $minAge = $this->validatePositiveInt((int)$minAgeParam, 'minAge', 0, 150);
        $limit = $this->validatePositiveInt((int)($request->query->get('limit', 50)), 'limit', 1, 200);
        $offset = $this->validatePositiveInt((int)($request->query->get('offset', 0)), 'offset', 0, 10000);
        $citizens = $this->citizenService->getElderlyCitizens($minAge, $limit, $offset);
        return $this->json(['data'=>array_map(fn($c)=>$c->toArray(), $citizens), 'meta'=>['minAge'=>$minAge,'limit'=>$limit,'offset'=>$offset,'count'=>count($citizens)]]);
    }

    #[Route('/children', name: 'citizen_children', methods: ['GET'])]
    public function getChildren(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getChildrenAction($request));
    }

    private function getChildrenAction(Request $request): JsonResponse
    {
        $user = $this->requireAuthOr401($request);
        if ($user instanceof JsonResponse) return $user;
        $this->checkPermission($user, 'citizen:view');
        $maxAge = $this->validatePositiveInt((int)($request->query->get('maxAge', 16)), 'maxAge', 0, 150);
        $limit = $this->validatePositiveInt((int)($request->query->get('limit', 50)), 'limit', 1, 200);
        $offset = $this->validatePositiveInt((int)($request->query->get('offset', 0)), 'offset', 0, 10000);
        $citizens = $this->citizenService->getChildCitizens($maxAge, $limit, $offset);
        return $this->json(['data'=>array_map(fn($c)=>$c->toArray(), $citizens), 'meta'=>['maxAge'=>$maxAge,'limit'=>$limit,'offset'=>$offset,'count'=>count($citizens)]]);
    }

    #[Route('/categories', name: 'citizen_by_categories', methods: ['GET'])]
    public function getBySpecialCategories(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getBySpecialCategoriesAction($request));
    }

    private function getBySpecialCategoriesAction(Request $request): JsonResponse
    {
        $user = $this->requireAuthOr401($request);
        if ($user instanceof JsonResponse) return $user;
        $this->checkPermission($user, 'citizen:view');
        $categories = $this->extractCategories($request);
        $this->validateCategories($categories);
        $citizens = $this->citizenService->getCitizensBySpecialCategories($categories);
        return $this->json(['data'=>array_map(fn($c)=>$c->toArray(), $citizens), 'meta'=>['categories'=>$categories,'count'=>count($citizens)]]);
    }

    // ========== PRIVATE HELPERS ==========

    private function validateCitizenData(array $data): void
    {
        if (!isset($data['full_name']) || trim($data['full_name']) === '') {
            throw new InvalidArgumentException('Full name is required');
        }
    }

    private function validateDto(object $dto): void
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new InvalidArgumentException(
                'Validation failed: ' .
                implode(', ', array_map(fn($v) => $v->getMessage(), iterator_to_array($violations)))
            );
        }
    }

    private function extractFilters(Request $request): array
    {
        $filters = $request->query->all();
        unset($filters['q'], $filters['limit'], $filters['offset']);
        return $filters;
    }

    private function extractPagination(Request $request): array
    {
        return [
            'limit' => $this->validatePositiveInt(
                (int) ($request->query->get('limit', 50)),
                'limit',
                 1,
                100
            ),
            'offset' => $this->validatePositiveInt(
                (int) ($request->query->get('offset', 0)),
                'offset',
                0
            )
        ];
    }

    private function extractCategories(Request $request): array
    {
        return $request->query->all('categories');
    }

    private function validateCategories(array $categories): void
    {
        if (empty($categories)) {
            throw new InvalidArgumentException('At least one category is required');
        }
    }

    private function validateUuid(string $id, string $fieldName): string
    {
        if (!$this->isValidUuid($id)) {
            throw new InvalidArgumentException("Invalid $fieldName format");
        }
        return $id;
    }

    private function isValidUuid(string $id): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id);
    }

    private function validatePositiveInt(?int $value, string $fieldName, int $min = 1, int $max = null): int
    {
        if ($value === null) {
            $value = 0;
        }
        if ($value < $min) {
            throw new InvalidArgumentException("$fieldName must be at least $min");
        }
        if ($max !== null && $value > $max) {
            throw new InvalidArgumentException("$fieldName must not exceed $max");
        }
        return $value;
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("CitizenController::{$context} error: " . $e->getMessage());
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

    // ========== AUTHENTICATION & AUTHORIZATION ==========

    private function authenticateUser(Request $request): array
    {
        $user = $this->authenticate($request);
        if ($user === false) {
            $this->logAction('authentication_failed', []);
            throw new InvalidArgumentException('Unauthorized');
        }
        return $user;
    }

    private function checkPermission(array $user, string $permission): void
    {
        $rolePermissions = [
            self::ROLE_ADMIN => ['*'],
            self::ROLE_OFFICER => ['citizen:view', 'citizen:create', 'citizen:update', 'citizen:delete'],
            self::ROLE_CITIZEN => ['citizen:create', 'citizen:view_own'],
        ];

        foreach ($rolePermissions as $role => $perms) {
            if (in_array($role, $user['roles'], true)) {
                if (in_array('*', $perms, true) || in_array($permission, $perms, true)) {
                    return;
                }
            }
        }

        throw new InvalidArgumentException('Forbidden');
    }

    private function ensureCanAccessCitizen(array $user, CitizenResponse $citizen): void
    {
        // Citizen can only access own record
        if (in_array(self::ROLE_CITIZEN, $user['roles'], true)) {
            if ($citizen->userId !== $user['id']) {
                throw new InvalidArgumentException('Forbidden: cannot access citizen data');
            }
        }
        // Officers and admins can access all
    }

    private function getCurrentUserId(Request $request): string
    {
        $user = $this->authenticate($request);
        if ($user === false) {
            throw new InvalidArgumentException('Unauthorized');
        }
        return $user['id'];
    }

    private function requireAuthOr401(Request $request): JsonResponse|array
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }
        return [
            'id' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (string) $user,
            'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : []
        ];
    }

    private function authenticate(Request $request): array|false
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        $id = method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (string) $user;
        return [
            'id' => $id,
            'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : []
        ];
    }
}
