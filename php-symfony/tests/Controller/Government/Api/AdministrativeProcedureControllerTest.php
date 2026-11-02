<?php

declare(strict_types=1);

namespace App\Tests\Controller\Government\Api;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Api\Controller\AdministrativeProcedureController;
use App\Government\Application\Service\AdministrativeProcedureService;
use App\Government\Contracts\DTO\CreateProcedureRequest;
use App\Government\Contracts\DTO\CreateProcedureApplicationRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class AdministrativeProcedureControllerTest extends TestCase
{
    private AdministrativeProcedureController $controller;
    private AdministrativeProcedureService|MockObject $procedureService;

    protected function setUp(): void
    {
        $this->procedureService = $this->createMock(AdministrativeProcedureService::class);
        $this->controller = new AdministrativeProcedureController($this->procedureService);
    }

    public function testCreateProcedureSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Birth Certificate',
                'code' => 'BC-001',
                'category' => 'Civil Registration',
                'description' => 'Birth certificate application',
                'requiredDocuments' => ['Birth certification form', 'Parent ID'],
                'processingTimeDays' => 5,
                'fee' => 50000,
            ])
        );

        $procedure = $this->createMock(\App\Government\Entity\Procedure::class);
        $procedure->method('toArray')->willReturn([
            'id' => 'proc123',
            'name' => 'Birth Certificate',
            'code' => 'BC-001',
            'status' => 'active',
        ]);

        $this->procedureService
            ->expects($this->once())
            ->method('createProcedure')
            ->with($this->callback(function ($req) {
                return $req instanceof CreateProcedureRequest 
                    && $req->name === 'Birth Certificate';
            }))
            ->willReturn($procedure);

        $result = $this->controller->create($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('proc123', $data['id']);
        $this->assertEquals('Birth Certificate', $data['name']);
    }

    public function testListProceduresWithFilters(): void
    {
        $request = new Request([
            'category' => 'Civil Registration',
            'status' => 'active',
            'search' => 'certificate',
            'page' => '1',
            'perPage' => '10',
        ]);

        $procedures = [
            $this->createMockProcedure('proc1', 'Birth Certificate'),
            $this->createMockProcedure('proc2', 'Death Certificate'),
        ];

        $this->procedureService
            ->expects($this->once())
            ->method('listProcedures')
            ->with(
                [
                    'category' => 'Civil Registration',
                    'status' => 'active',
                    'search' => 'certificate',
                ],
                1,
                10
            )
            ->willReturn($procedures);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data['data']);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(10, $data['perPage']);
    }

    public function testListProceduresWithoutFilters(): void
    {
        $request = new Request();

        $procedures = [];

        $this->procedureService
            ->expects($this->once())
            ->method('listProcedures')
            ->with([], 1, 20)
            ->willReturn($procedures);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(20, $data['perPage']);
    }

    public function testGetProcedureSuccess(): void
    {
        $procedure = $this->createMockProcedure('proc123', 'Birth Certificate');

        $this->procedureService
            ->expects($this->once())
            ->method('getProcedureById')
            ->with('proc123')
            ->willReturn($procedure);

        $result = $this->controller->get('proc123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('proc123', $data['id']);
        $this->assertEquals('Birth Certificate', $data['name']);
    }

    public function testGetProcedureNotFound(): void
    {
        $this->procedureService
            ->expects($this->once())
            ->method('getProcedureById')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->controller->get('notfound');

        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Procedure not found', $data['error']);
    }

    public function testActivateProcedureSuccess(): void
    {
        $this->procedureService
            ->expects($this->once())
            ->method('activateProcedure')
            ->with('proc123')
            ->willReturn(true);

        $result = $this->controller->activate('proc123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Procedure activated successfully', $data['message']);
    }

    public function testActivateProcedureNotFound(): void
    {
        $this->procedureService
            ->expects($this->once())
            ->method('activateProcedure')
            ->with('notfound')
            ->willReturn(false);

        $result = $this->controller->activate('notfound');

        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Procedure not found', $data['error']);
    }

    public function testDeactivateProcedureSuccess(): void
    {
        $this->procedureService
            ->expects($this->once())
            ->method('deactivateProcedure')
            ->with('proc123')
            ->willReturn(true);

        $result = $this->controller->deactivate('proc123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Procedure deactivated successfully', $data['message']);
    }

    public function testDeactivateProcedureNotFound(): void
    {
        $this->procedureService
            ->expects($this->once())
            ->method('deactivateProcedure')
            ->with('notfound')
            ->willReturn(false);

        $result = $this->controller->deactivate('notfound');

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testSubmitApplicationSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'citizenId' => 'citizen123',
                'procedureId' => 'proc123',
                'documents' => ['doc1', 'doc2'],
                'submittedBy' => 'user456',
                'notes' => 'Application notes',
            ])
        );

        $application = $this->createMock(\App\Government\Entity\ProcedureApplication::class);
        $application->method('toArray')->willReturn([
            'id' => 'app123',
            'citizenId' => 'citizen123',
            'procedureId' => 'proc123',
            'status' => 'submitted',
        ]);

        $this->procedureService
            ->expects($this->once())
            ->method('submitApplication')
            ->with($this->callback(function ($req) {
                return $req instanceof CreateProcedureApplicationRequest;
            }))
            ->willReturn($application);

        $result = $this->controller->submitApplication($request);

        $this->assertEquals(201, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('app123', $data['id']);
    }

    public function testListApplicationsWithFilters(): void
    {
        $request = new Request([
            'citizenId' => 'citizen123',
            'procedureId' => 'proc123',
            'status' => 'approved',
            'page' => '1',
            'perPage' => '10',
        ]);

        $applications = [$this->createMockApplication('app1')];

        $this->procedureService
            ->expects($this->once())
            ->method('listApplications')
            ->with(
                [
                    'citizenId' => 'citizen123',
                    'procedureId' => 'proc123',
                    'status' => 'approved',
                ],
                1,
                10
            )
            ->willReturn($applications);

        $result = $this->controller->listApplications($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetApplicationSuccess(): void
    {
        $application = $this->createMockApplication('app123');

        $this->procedureService
            ->expects($this->once())
            ->method('getApplicationById')
            ->with('app123')
            ->willReturn($application);

        $result = $this->controller->getApplication('app123');

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetApplicationNotFound(): void
    {
        $this->procedureService
            ->expects($this->once())
            ->method('getApplicationById')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->controller->getApplication('notfound');

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testStartProcessingSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['processedBy' => 'officer123'])
        );

        $this->procedureService
            ->expects($this->once())
            ->method('startProcessing')
            ->with('app123', 'officer123')
            ->willReturn(true);

        $result = $this->controller->startProcessing('app123', $request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Processing started', $data['message']);
    }

    public function testStartProcessingNotFound(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['processedBy' => 'user123'])
        );

        $this->procedureService
            ->expects($this->once())
            ->method('startProcessing')
            ->willReturn(false);

        $result = $this->controller->startProcessing('notfound', $request);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testApproveApplicationSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['result' => 'Approved with conditions'])
        );

        $this->procedureService
            ->expects($this->once())
            ->method('approve')
            ->with('app123', 'Approved with conditions')
            ->willReturn(true);

        $result = $this->controller->approve('app123', $request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testApproveApplicationNotFound(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->procedureService
            ->expects($this->once())
            ->method('approve')
            ->willReturn(false);

        $result = $this->controller->approve('notfound', $request);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testRejectApplicationSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['result' => 'Incomplete documents'])
        );

        $this->procedureService
            ->expects($this->once())
            ->method('reject')
            ->with('app123', 'Incomplete documents')
            ->willReturn(true);

        $result = $this->controller->reject('app123', $request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testCompleteApplicationSuccess(): void
    {
        $this->procedureService
            ->expects($this->once())
            ->method('complete')
            ->with('app123')
            ->willReturn(true);

        $result = $this->controller->complete('app123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Application completed', $data['message']);
    }

    public function testStatistics(): void
    {
        $stats = [
            'totalProcedures' => 100,
            'activeProcedures' => 80,
            'totalApplications' => 1000,
            'pendingApplications' => 200,
            'approvedApplications' => 700,
            'rejectedApplications' => 100,
        ];

        $this->procedureService
            ->expects($this->once())
            ->method('getStatistics')
            ->willReturn($stats);

        $result = $this->controller->statistics();

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals(100, $data['totalProcedures']);
        $this->assertEquals(80, $data['activeProcedures']);
    }

    private function createMockProcedure(string $id, string $name): MockObject
    {
        $procedure = $this->createMock(\App\Government\Entity\Procedure::class);
        $procedure->method('toArray')->willReturn([
            'id' => $id,
            'name' => $name,
        ]);
        return $procedure;
    }

    private function createMockApplication(string $id): MockObject
    {
        $application = $this->createMock(\App\Government\Entity\ProcedureApplication::class);
        $application->method('toArray')->willReturn([
            'id' => $id,
            'status' => 'submitted',
        ]);
        return $application;
    }
}
