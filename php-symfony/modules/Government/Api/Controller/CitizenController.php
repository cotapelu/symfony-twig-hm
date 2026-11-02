<?php

declare(strict_types=1);

namespace App\Government\Api\Controller;

use App\Government\Application\Service\CitizenService;
use App\Government\Contracts\DTO\CreateCitizenRequest;
use App\Government\Contracts\DTO\UpdateCitizenRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Controller\BaseController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/government/citizens')]
#[IsGranted('ROLE_OFFICER')]
class CitizenController extends BaseController
{
    private const VALID_GENDERS = ['male', 'female', 'other'];
    private const MAX_PER_PAGE = 100;
    private const MIN_PER_PAGE = 1;

    public function __construct(
        private CitizenService $citizenService
    ) {
        parent::__construct();
    }

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context]));
    }

    private function incrementMetric(string $metric): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'value'=>1]));
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("CitizenController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['error' => 'Internal server error'], 500);
    }

    private function buildCreateCitizenRequest(array $data): CreateCitizenRequest
    {
        $d = array_merge([
            'apartmentNumber'=>null, 'email'=>null, 'phone'=>null, 'dateOfBirth'=>null,
            'gender'=>'other', 'ethnicity'=>null, 'nationality'=>'Vietnam',
            'occupation'=>null, 'educationLevel'=>null, 'maritalStatus'=>null, 'householdId'=>null
        ], $data);
        return new CreateCitizenRequest($d['fullName'], $d['identityNumber'], $d['province'], $d['district'], $d['ward'], $d['street'], $d['apartmentNumber'], $d['email'], $d['phone'], $d['dateOfBirth'], $d['gender'], $d['ethnicity'], $d['nationality'], $d['occupation'], $d['educationLevel'], $d['maritalStatus'], $d['householdId']);
    }

    // ==================== Endpoints ====================

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createAction($request));
    }

    private function createAction(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        $this->validateRequiredFields($data, ['fullName', 'identityNumber', 'province', 'district', 'ward', 'street']);
        $this->validateOptionalFields($data);

        $createRequest = $this->buildCreateCitizenRequest($data);

        $citizen = $this->citizenService->createCitizen($createRequest);

        $this->logAction('citizen_created', ['citizen_id'=>$citizen->id, 'identity_number'=>substr($data['identityNumber'], -4)]);
        $this->incrementMetric('citizens_created');

        return $this->json($citizen->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        return $this->handle(fn() => $this->getAction($id));
    }

    private function getAction(string $id): JsonResponse
    {
        $this->validateId($id);
        $citizen = $this->citizenService->getCitizenById($id);
        if (!$citizen) return $this->json(['error' => 'Citizen not found'], 404);
        return $this->json($citizen->toArray());
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->listAction($request));
    }

    private function listAction(Request $request): JsonResponse
    {
        $filters = [];

        if ($request->query->has('search')) {
            $search = $request->query->get('search');
            if (strlen($search) > 100) throw new InvalidArgumentException('Search query cannot exceed 100 characters');
            $filters['search'] = $search;
        }
        if ($request->query->has('province')) $filters['province'] = $request->query->get('province');
        if ($request->query->has('district')) $filters['district'] = $request->query->get('district');
        if ($request->query->has('ward')) $filters['ward'] = $request->query->get('ward');
        if ($request->query->has('active')) $filters['isActive'] = filter_var($request->query->get('active'), FILTER_VALIDATE_BOOLEAN);

        $page = (int)$request->query->get('page', 1);
        $perPage = (int)$request->query->get('perPage', 20);
        $this->validatePagination($page, $perPage);

        $citizens = $this->citizenService->listCitizens($filters, $page, $perPage);
        return $this->json([
            'data' => array_map(fn($c) => $c->toArray(), $citizens),
            'page' => $page,
            'perPage' => $perPage
        ]);
    }

    #[Route('/search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchAction($request));
    }

    private function searchAction(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        if (empty($query)) throw new InvalidArgumentException('Search query is required');
        if (strlen($query) > 100) throw new InvalidArgumentException('Search query cannot exceed 100 characters');

        $citizens = $this->citizenService->searchCitizens($query);
        $this->logAction('citizens_searched', ['query_length'=>strlen($query)]);
        return $this->json(['data' => array_map(fn($c) => $c->toArray(), $citizens)]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateAction($id, $request));
    }

    private function updateAction(string $id, Request $request): JsonResponse
    {
        $this->validateId($id);
        $data = $this->getJsonData($request);
        $this->validateOptionalFields($data);

        $updateRequest = new UpdateCitizenRequest(
            fullName: $data['fullName'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            dateOfBirth: $data['dateOfBirth'] ?? null,
            occupation: $data['occupation'] ?? null,
            educationLevel: $data['educationLevel'] ?? null,
            maritalStatus: $data['maritalStatus'] ?? null,
            householdId: $data['householdId'] ?? null
        );

        $citizen = $this->citizenService->updateCitizen($id, $updateRequest);
        if (!$citizen) return $this->json(['error' => 'Citizen not found'], 404);

        $this->logAction('citizen_updated', ['citizen_id'=>$id]);
        return $this->json($citizen->toArray());
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
    public function deactivate(string $id): JsonResponse
    {
        return $this->handle(fn() => $this->deactivateAction($id));
    }

    private function deactivateAction(string $id): JsonResponse
    {
        $this->validateId($id);
        $success = $this->citizenService->deactivateCitizen($id);
        if (!$success) return $this->json(['error' => 'Citizen not found'], 404);
        $this->logAction('citizen_deactivated', ['citizen_id'=>$id]);
        return $this->json(['message' => 'Citizen deactivated successfully']);
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    public function activate(string $id): JsonResponse
    {
        return $this->handle(fn() => $this->activateAction($id));
    }

    private function activateAction(string $id): JsonResponse
    {
        $this->validateId($id);
        $success = $this->citizenService->activateCitizen($id);
        if (!$success) return $this->json(['error' => 'Citizen not found'], 404);
        $this->logAction('citizen_activated', ['citizen_id'=>$id]);
        return $this->json(['message' => 'Citizen activated successfully']);
    }

    #[Route('/household/{householdId}', methods: ['GET'])]
    public function getByHousehold(string $householdId): JsonResponse
    {
        return $this->handle(fn() => $this->getByHouseholdAction($householdId));
    }

    private function getByHouseholdAction(string $householdId): JsonResponse
    {
        $this->validateId($householdId);
        $citizens = $this->citizenService->getCitizensByHousehold($householdId);
        return $this->json(['data' => array_map(fn($c) => $c->toArray(), $citizens)]);
    }

    #[Route('/statistics', methods: ['GET'])]
    public function statistics(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->statisticsAction($request));
    }

    private function statisticsAction(Request $request): JsonResponse
    {
        $filters = [];
        if ($request->query->has('province')) $filters['province'] = $request->query->get('province');
        if ($request->query->has('district')) $filters['district'] = $request->query->get('district');
        $stats = $this->citizenService->getStatistics($filters);
        return $this->json($stats);
    }

    // ==================== Validation Helpers ====================

    private function validateRequiredFields(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                throw new InvalidArgumentException("Field '$field' is required");
            }
        }
    }

    private function validateOptionalFields(array $data): void
    {
        $this->validateFullName($data);
        $this->validateEmail($data);
        $this->validatePhone($data);
        $this->validateGender($data);
        $this->validateDateOfBirth($data);
    }

    private function validateFullName(array $data): void
    {
        if (isset($data['fullName']) && strlen($data['fullName']) > 100) {
            throw new InvalidArgumentException('Full name cannot exceed 100 characters');
        }
    }

    private function validateEmail(array $data): void
    {
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
    }

    private function validatePhone(array $data): void
    {
        if (!empty($data['phone']) && !preg_match('/^[\d\s\+\-\(\)]{8,20}$/', $data['phone'])) {
            throw new InvalidArgumentException('Invalid phone number format');
        }
    }

    private function validateGender(array $data): void
    {
        if (isset($data['gender']) && !in_array($data['gender'], self::VALID_GENDERS)) {
            throw new InvalidArgumentException('Invalid gender. Valid values: '.implode(', ', self::VALID_GENDERS));
        }
    }

    private function validateDateOfBirth(array $data): void
    {
        if (!empty($data['dateOfBirth'])) {
            try { new \DateTimeImmutable($data['dateOfBirth']); }
            catch (\Exception) { throw new InvalidArgumentException('Invalid date of birth format'); }
        }
    }

    private function validateId(string $id): void
    {
        if (empty(trim($id))) throw new InvalidArgumentException('ID cannot be empty');
        if (strlen($id) > 36) throw new InvalidArgumentException('ID cannot exceed 36 characters');
    }

    private function validatePagination(int $page, int $perPage): void
    {
        if ($page < 1) throw new InvalidArgumentException('Page must be at least 1');
        if ($perPage < self::MIN_PER_PAGE || $perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException('Per page must be between '.self::MIN_PER_PAGE.' and '.self::MAX_PER_PAGE);
        }
    }
}
