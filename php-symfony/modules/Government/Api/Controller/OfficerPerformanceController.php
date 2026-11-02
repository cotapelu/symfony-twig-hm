<?php

declare(strict_types=1);

namespace App\Government\Api\Controller;

use App\Government\Application\Service\OfficerPerformanceService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/government/officer-performance')]
class OfficerPerformanceController extends BaseController
{
    public function __construct(
        private OfficerPerformanceService $officerPerformanceService
    ) {
    }

    #[Route('/{officerId}', methods: ['GET'])]
    public function getOfficerPerformance(string $officerId, Request $request): JsonResponse
    {
        $timePeriod = $request->query->get('timePeriod', (new \DateTimeImmutable())->format('Y-m'));

        $metrics = $this->officerPerformanceService->getOfficerMetrics($officerId, $timePeriod);

        return $this->json([
            'success' => true,
            'data' => $metrics
        ]);
    }

    #[Route('/ward/{wardId}', methods: ['GET'])]
    public function getWardPerformance(string $wardId, Request $request): JsonResponse
    {
        $timePeriod = $request->query->get('timePeriod', (new \DateTimeImmutable())->format('Y-m'));

        // This would require a service that gets officers by ward and then aggregates their performance.
        // For now, we return an empty array as a placeholder.
        $metrics = [];

        return $this->json([
            'success' => true,
            'data' => $metrics
        ]);
    }
}