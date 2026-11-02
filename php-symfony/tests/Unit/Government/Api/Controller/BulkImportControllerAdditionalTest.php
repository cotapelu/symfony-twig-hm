<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government\Api\Controller;

use PHPUnit\Framework\TestCase;
use App\Government\Api\Controller\BulkImportController;
use App\Government\Application\Service\BulkImportService;
use App\Government\Application\Service\BulkExportService;
use App\Government\Contracts\DTO\BulkImportResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class BulkImportControllerAdditionalTest extends TestCase
{
    private BulkImportService $importService;
    private BulkExportService $exportService;
    private BulkImportController $controller;

    protected function setUp(): void
    {
        $this->importService = $this->createMock(BulkImportService::class);
        $this->exportService = $this->createMock(BulkExportService::class);
        $this->controller = new BulkImportController($this->importService, $this->exportService);
        $this->setupContainer();
    }

    private function setupContainer(): void
    {
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $this->controller->setContainer($container);
    }

    private function jsonRequest(array $data = []): Request
    {
        return Request::create('/api/government/import/test', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    private function csvRequest(string $csv): Request
    {
        return Request::create('/api/government/import/test', 'POST', [], [], [], ['CONTENT_TYPE' => 'text/csv'], $csv);
    }

    // ==================== Additional Tests ====================

    public function testImportCitizens_CsvPayload_DelegatesToService(): void
    {
        $csv = "fullName,identityNumber,province,district,ward,street\nJohn Doe,123,Hanoi,District,Ward,Street";
        $request = $this->csvRequest($csv);

        $citizens = [['fullName' => 'John Doe', 'identityNumber' => '123', 'province' => 'Hanoi', 'district' => 'District', 'ward' => 'Ward', 'street' => 'Street']];
        $this->importService->expects($this->once())
            ->method('parseCSV')
            ->with($csv)
            ->willReturn($citizens);

        $result = $this->createMock(BulkImportResponse::class);
        $result->method('toArray')->willReturn(['imported' => 1, 'failed' => 0]);
        $this->importService->expects($this->once())
            ->method('importCitizens')
            ->with($citizens)
            ->willReturn($result);

        $response = $this->controller->importCitizens($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['imported']);
    }

    public function testExportFilters_IncludesIsActive(): void
    {
        $filters = ['isActive' => true];
        $this->exportService->expects($this->once())
            ->method('exportCitizensToCSV')
            ->with($this->callback(function ($actual) use ($filters) {
                return isset($actual['isActive']) && $actual['isActive'] === $filters['isActive'];
            }))
            ->willReturn("csv,data");

        $request = Request::create('/api/government/import/export/csv?isActive=1', 'GET');
        $response = $this->controller->exportCsv($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
        $this->assertSame("csv,data", $response->getContent());
    }

}
