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

class BulkImportControllerTest extends TestCase
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

    // ==================== Import ====================

    public function testImportCitizens_Success(): void
    {
        $citizens = [['fullName' => 'John', 'identityNumber' => '123']];
        $result = $this->createMock(BulkImportResponse::class);
        $result->method('toArray')->willReturn(['imported' => 1, 'failed' => 0]);
        $this->importService->method('importCitizens')->with($citizens)->willReturn($result);

        $request = $this->jsonRequest(['citizens' => $citizens]);
        $response = $this->controller->importCitizens($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['imported']);
    }

    public function testImportCitizens_EmptyData(): void
    {
        $request = $this->jsonRequest(['citizens' => []]);
        $response = $this->controller->importCitizens($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('No citizen data', $data['error']);
    }

    // ==================== Template ====================

    public function testDownloadTemplate_ReturnsCsv(): void
    {
        $this->importService->method('generateCSVTemplate')->willReturn("header1,header2\nval1,val2");
        $response = $this->controller->downloadTemplate();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('citizen_import_template.csv', $response->headers->get('Content-Disposition'));
        $this->assertSame("header1,header2\nval1,val2", $response->getContent());
    }

    // ==================== Statistics ====================

    public function testGetImportStatistics_ReturnsStats(): void
    {
        $this->importService->method('getImportStatistics')->willReturn(['total' => 100, 'success' => 95]);
        $response = $this->controller->getImportStatistics();
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(100, $data['total']);
    }

    // ==================== Validate ====================

    public function testValidateImportData_Success(): void
    {
        $data = ['citizens' => [['fullName' => 'John', 'identityNumber' => '123', 'province' => 'P', 'district' => 'D', 'ward' => 'W', 'street' => 'S']]];
        $request = $this->jsonRequest($data);
        $response = $this->controller->validateImportData($request);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['isValid']);
    }

    public function testValidateImportData_InvalidFormat(): void
    {
        $request = $this->jsonRequest(['wrong' => 'data']);
        $response = $this->controller->validateImportData($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testValidateImportData_MissingRequiredField(): void
    {
        $data = ['citizens' => [['fullName' => 'John']]]; // missing others
        $request = $this->jsonRequest($data);
        $response = $this->controller->validateImportData($request);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['isValid']);
        $this->assertGreaterThan(0, $data['invalidCount']);
    }

    // ==================== Export ====================

    public function testExportCsv_ReturnsCsv(): void
    {
        $this->exportService->method('exportCitizensToCSV')->with([])->willReturn("col1,col2\na,b");
        $request = Request::create('/api/government/import/export/csv', 'GET');
        $response = $this->controller->exportCsv($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
        $this->assertSame("col1,col2\na,b", $response->getContent());
    }

    public function testExportJson_ReturnsJson(): void
    {
        $json = json_encode([['id' => 1]]);
        $this->exportService->method('exportCitizensToJSON')->with([])->willReturn($json);
        $request = Request::create('/api/government/import/export/json', 'GET');
        $response = $this->controller->exportJson($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data[0]['id']);
    }

    public function testExportJson_InvalidJsonFromService(): void
    {
        $this->exportService->method('exportCitizensToJSON')->willReturn('invalid{json');
        $request = Request::create('/api/government/import/export/json', 'GET');
        $response = $this->controller->exportJson($request);
        $this->assertSame(400, $response->getStatusCode());
    }
}
