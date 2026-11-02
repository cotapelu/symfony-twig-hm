<?php

declare(strict_types=1);

namespace App\Government\Api\Controller;

use App\Government\Application\Service\StatisticsService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/government/statistics')]
class StatisticsController extends BaseController
{
    private const BOOLEAN_FILTERS = ['isActive'];

    public function __construct(private StatisticsService $statisticsService) { parent::__construct(); }

    private function getQueryParams(Request $request): array
    {
        return $request->query->all();
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['success'=>false, 'error'=>$e->getMessage()], 400); }
        catch (\Exception $e) {
            error_log("StatisticsController error: ".$e->getMessage());
            return $this->json(['success'=>false, 'error'=>$e->getMessage()], 500);
        }
    }

    // ==================== Endpoints ====================

    #[Route('/citizens', methods: ['GET'])]
    public function getCitizenStatistics(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getCitizenStatisticsAction($request));
    }

    private function getCitizenStatisticsAction(Request $request): JsonResponse
    {
        $filters = $this->buildFilters($request, ['province','district','ward','isActive']);
        $stats = $this->statisticsService->getCitizenStatistics($filters);
        return $this->json(['success'=>true, 'data'=>$stats], 200);
    }

    #[Route('/documents', methods: ['GET'])]
    public function getDocumentStatistics(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getDocumentStatisticsAction($request));
    }

    private function getDocumentStatisticsAction(Request $request): JsonResponse
    {
        $filters = $this->buildFilters($request, ['province','district','ward','status']);
        $stats = $this->statisticsService->getDocumentStatistics($filters);
        return $this->json(['success'=>true, 'data'=>$stats], 200);
    }

    #[Route('/procedures', methods: ['GET'])]
    public function getProcedureStatistics(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getProcedureStatisticsAction($request));
    }

    private function getProcedureStatisticsAction(Request $request): JsonResponse
    {
        $filters = $this->buildFilters($request, ['province','district','ward','status']);
        $stats = $this->statisticsService->getProcedureStatistics($filters);
        return $this->json(['success'=>true, 'data'=>$stats], 200);
    }

    #[Route('/officers', methods: ['GET'])]
    public function getOfficerStatistics(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getOfficerStatisticsAction($request));
    }

    private function getOfficerStatisticsAction(Request $request): JsonResponse
    {
        $filters = $this->buildFilters($request, ['province','district','ward','isActive']);
        $stats = $this->statisticsService->getOfficerStatistics($filters);
        return $this->json(['success'=>true, 'data'=>$stats], 200);
    }

    #[Route('/dashboard', methods: ['GET'])]
    public function getDashboardStatistics(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getDashboardStatisticsAction($request));
    }

    private function getDashboardStatisticsAction(Request $request): JsonResponse
    {
        $filters = $this->buildFilters($request, ['province','district','ward']);
        $stats = $this->statisticsService->getDashboardStatistics($filters);
        return $this->json(['success'=>true, 'data'=>$stats], 200);
    }

    private function buildFilters(Request $request, array $keys): array
    {
        $filters = [];
        foreach ($keys as $key) {
            if ($request->query->has($key)) {
                $value = $request->query->get($key);
                if (in_array($key, self::BOOLEAN_FILTERS, true)) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                $filters[$key] = $value;
            }
        }
        return $filters;
    }
}