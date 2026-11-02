<?php

declare(strict_types=1);

namespace App\Government\Api\Controller;

use App\Government\Application\Service\BulkExportService;
use App\Government\Application\Service\BulkImportService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/government/import')]
class BulkImportController extends BaseController
{
    public function __construct(
        private BulkImportService $bulkImportService,
        private BulkExportService $bulkExportService
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
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('bulk_import', $e);
        }
    }

    // ==================== Endpoints ====================

    #[Route('/citizens', methods: ['POST'])]
    public function importCitizens(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->importCitizensAction($request));
    }

    private function importCitizensAction(Request $request): JsonResponse
    {
        $citizens = $this->parseCitizensFromRequest($request);
        if (empty($citizens)) {
            throw new InvalidArgumentException('No citizen data provided');
        }
        $result = $this->bulkImportService->importCitizens($citizens);
        return $this->json($result->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/template', methods: ['GET'])]
    public function downloadTemplate(): Response
    {
        // Direct call, not using handle() because returns Response (CSV), not JsonResponse
        return $this->downloadTemplateAction();
    }

    private function downloadTemplateAction(): Response
    {
        $template = $this->bulkImportService->generateCSVTemplate();
        $response = new Response($template);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="citizen_import_template.csv"');
        return $response;
    }

    #[Route('/statistics', methods: ['GET'])]
    public function getImportStatistics(): JsonResponse
    {
        return $this->handle(fn() => $this->getImportStatisticsAction());
    }

    private function getImportStatisticsAction(): JsonResponse
    {
        $stats = $this->bulkImportService->getImportStatistics();
        return $this->json($stats);
    }

    #[Route('/validate', methods: ['POST'])]
    public function validateImportData(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->validateImportDataAction($request));
    }

    private function validateImportDataAction(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        if (!isset($data['citizens']) || !is_array($data['citizens'])) {
            throw new InvalidArgumentException('Invalid format. Expected {citizens: [...]}');
        }
        $citizens = $data['citizens'];
        $errors = [];
        $validCount = 0;
        foreach ($citizens as $index => $citizen) {
            $error = $this->validateCitizenRow($index, $citizen);
            if ($error) {
                $errors[] = $error;
            } else {
                $validCount++;
            }
        }
        return $this->json([
            'validCount' => $validCount,
            'invalidCount' => count($errors),
            'totalCount' => count($citizens),
            'errors' => $errors,
            'isValid' => empty($errors)
        ]);
    }

    #[Route('/export/csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        return $this->exportCsvAction($request);
    }

    private function exportCsvAction(Request $request): Response
    {
        $filters = $this->buildExportFilters($request);
        $csvContent = $this->bulkExportService->exportCitizensToCSV($filters);
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="citizens_export.csv"');
        return $response;
    }

    #[Route('/export/json', methods: ['GET'])]
    public function exportJson(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->exportJsonAction($request));
    }

    private function exportJsonAction(Request $request): JsonResponse
    {
        $filters = $this->buildExportFilters($request);
        $jsonContent = $this->bulkExportService->exportCitizensToJSON($filters);
        $decoded = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON from export');
        }
        return $this->json($decoded, Response::HTTP_OK);
    }

    // ========== PRIVATE HELPERS ==========

    private function parseCitizensFromRequest(Request $request): array
    {
        $contentType = $request->headers->get('Content-Type', '');

        if (str_contains($contentType, 'application/json')) {
            $data = $this->getJsonData($request);
            if (!isset($data['citizens']) || !is_array($data['citizens'])) {
                throw new InvalidArgumentException('Invalid JSON format. Expected {citizens: [...]}');
            }
            return $data['citizens'];
        } elseif (str_contains($contentType, 'text/csv') || str_contains($contentType, 'multipart/form-data')) {
            $file = $request->files->get('file');
            if ($file) {
                $csvContent = file_get_contents($file->getPathname());
            } else {
                $csvContent = $request->getContent();
            }
            return $this->bulkImportService->parseCSV($csvContent);
        } else {
            throw new InvalidArgumentException('Unsupported content type. Use application/json or text/csv');
        }
    }

    private function validateCitizenRow(int $index, array $citizen): array
    {
        $requiredFields = ['fullName', 'identityNumber', 'province', 'district', 'ward', 'street'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($citizen[$field])) {
                $missingFields[] = $field;
            }
        }
        if (!empty($missingFields)) {
            return [
                'row' => $index + 1,
                'error' => 'Missing required fields: ' . implode(', ', $missingFields)
            ];
        }
        return []; // no error
    }

    private function buildExportFilters(Request $request): array
    {
        $filters = [];
        if ($request->query->has('province')) {
            $filters['province'] = $request->query->get('province');
        }
        if ($request->query->has('district')) {
            $filters['district'] = $request->query->get('district');
        }
        if ($request->query->has('ward')) {
            $filters['ward'] = $request->query->get('ward');
        }
        if ($request->query->has('isActive')) {
            $filters['isActive'] = filter_var($request->query->get('isActive'), FILTER_VALIDATE_BOOLEAN);
        }
        return $filters;
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("BulkImportController::{$context} error: " . $e->getMessage());
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