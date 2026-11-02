<?php

declare(strict_types=1);

namespace App\Government\Api\Controller;

use App\Government\Application\Service\OfficerService;
use App\Government\Contracts\DTO\CreateOfficerRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;
use App\Shared\Controller\BaseController;

#[Route('/api/government/officers')]
class OfficerController extends BaseController
{
    private const VALID_GENDERS = ['male', 'female', 'other'];
    private const MAX_PER_PAGE = 100;
    private const MIN_PER_PAGE = 1;

    public function __construct(private OfficerService $officerService) { parent::__construct(); }

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
        error_log("OfficerController::{$ctx} error: ".$e->getMessage());
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

    // ==================== Endpoints ====================

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createAction($request));
    }

    private function createAction(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        $this->validateRequiredFields($data, ['fullName', 'positionCode', 'positionName', 'departmentCode', 'departmentName']);

        $createRequest = new CreateOfficerRequest(
            fullName: $data['fullName'],
            positionCode: $data['positionCode'],
            positionName: $data['positionName'],
            departmentCode: $data['departmentCode'],
            departmentName: $data['departmentName'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            dateOfBirth: $data['dateOfBirth'] ?? null,
            identityNumber: $data['identityNumber'] ?? null,
            positionLevel: $data['positionLevel'] ?? 0,
            parentDepartmentCode: $data['parentDepartmentCode'] ?? null
        );

        $officer = $this->officerService->createOfficer($createRequest);

        $this->logAction('officer_created', ['officer_id'=>$officer['id'] ?? null]);
        $this->incrementMetric('officers_created');

        return $this->json($officer, Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        return $this->handle(fn() => $this->getAction($id));
    }

    private function getAction(string $id): JsonResponse
    {
        $this->validateId($id);
        $officer = $this->officerService->getOfficerById($id);
        if (!$officer) return $this->json(['error' => 'Officer not found'], 404);
        return $this->json($officer);
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
        if ($request->query->has('department')) $filters['departmentCode'] = $request->query->get('department');
        if ($request->query->has('active')) $filters['isActive'] = filter_var($request->query->get('active'), FILTER_VALIDATE_BOOLEAN);

        $page = (int)$request->query->get('page', 1);
        $perPage = (int)$request->query->get('perPage', 20);
        $this->validatePagination($page, $perPage);

        $officers = $this->officerService->listOfficers($filters, $page, $perPage);

        return $this->json(['data'=>$officers, 'page'=>$page, 'perPage'=>$perPage]);
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

        $officers = $this->officerService->searchOfficers($query);
        $this->logAction('officers_searched', ['query_length'=>strlen($query)]);
        return $this->json(['data'=>$officers]);
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
