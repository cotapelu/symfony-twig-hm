<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Api\Controller;

use App\Government\Api\Controller\AdministrativeProcedureController;
use App\Government\Application\Service\AdministrativeProcedureService;
use App\Government\Contracts\DTO\CreateProcedureRequest;
use App\Government\Contracts\DTO\CreateProcedureApplicationRequest;
use App\Government\Contracts\DTO\ProcedureResponse;
use App\Government\Contracts\DTO\ProcedureApplicationResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AdministrativeProcedureControllerTest extends TestCase
{
    private AdministrativeProcedureService&MockObject $serviceMock;
    private AdministrativeProcedureController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(AdministrativeProcedureService::class);
        $this->controller = new AdministrativeProcedureController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createRequest(array $json = [], string $method = 'POST', array $query = []): Request
    {
        $content = json_encode($json);
        $server = ['CONTENT_TYPE' => 'application/json'];
        return Request::create('/api/government/procedures', $method, $query, [], [], $server, $content);
    }

    private function createProcedureResponse(array $overrides = []): ProcedureResponse
    {
        $data = array_merge([
            'id' => 'proc-123',
            'name' => 'Test Procedure',
            'code' => 'TP001',
            'category' => 'registration',
            'description' => 'A test procedure',
            'requiredDocuments' => ['id_card'],
            'processingTimeDays' => 5,
            'fee' => 10.0,
            'status' => 'active',
            'createdAt' => '2025-01-01 00:00:00',
        ], $overrides);
        return new ProcedureResponse(
            id: $data['id'],
            name: $data['name'],
            code: $data['code'],
            category: $data['category'],
            description: $data['description'],
            requiredDocuments: $data['requiredDocuments'],
            processingTimeDays: $data['processingTimeDays'],
            fee: $data['fee'],
            status: $data['status'],
            createdAt: $data['createdAt']
        );
    }

    private function createApplicationResponse(array $overrides = []): ProcedureApplicationResponse
    {
        $data = array_merge([
            'id' => 'app-123',
            'citizenId' => 'citizen-1',
            'procedureId' => 'proc-1',
            'status' => 'pending',
            'documents' => [],
            'notes' => null,
            'submittedBy' => 'user1',
            'processedBy' => null,
            'processingStartedAt' => null,
            'completedAt' => null,
            'result' => null,
            'createdAt' => '2025-01-01 00:00:00',
        ], $overrides);
        return new ProcedureApplicationResponse(
            id: $data['id'],
            citizenId: $data['citizenId'],
            procedureId: $data['procedureId'],
            status: $data['status'],
            documents: $data['documents'],
            notes: $data['notes'],
            submittedBy: $data['submittedBy'],
            processedBy: $data['processedBy'],
            processingStartedAt: $data['processingStartedAt'],
            completedAt: $data['completedAt'],
            result: $data['result'],
            createdAt: $data['createdAt']
        );
    }

    // ==================== create ====================

    public function testCreate_Success(): void
    {
        $data = [
            'name' => 'New Procedure',
            'code' => 'NP001',
            'category' => 'permit',
            'description' => 'A new procedure',
            'requiredDocuments' => ['id_card'],
            'processingTimeDays' => 10,
            'fee' => 25.5
        ];
        $request = $this->createRequest($data);

        $responseDto = $this->createProcedureResponse([
            'id' => 'generated-id',
            'name' => $data['name'],
            'code' => $data['code'],
            'category' => $data['category'],
            'description' => $data['description'],
            'requiredDocuments' => $data['requiredDocuments'],
            'processingTimeDays' => $data['processingTimeDays'],
            'fee' => $data['fee'],
        ]);

        $this->serviceMock->expects($this->once())
            ->method('createProcedure')
            ->with($this->callback(function($arg) use ($data) {
                return $arg instanceof CreateProcedureRequest &&
                       $arg->name === $data['name'] &&
                       $arg->code === $data['code'] &&
                       $arg->category === $data['category'] &&
                       $arg->description === $data['description'] &&
                       $arg->requiredDocuments === $data['requiredDocuments'] &&
                       $arg->processingTimeDays === $data['processingTimeDays'] &&
                       $arg->fee === $data['fee'];
            }))
            ->willReturn($responseDto);

        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertSame($responseDto->id, $result['id']);
    }

    public function testCreate_InvalidJson(): void
    {
        $request = Request::create('/api/government/procedures', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{invalid json');
        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Invalid JSON'], json_decode($response->getContent(), true));
    }

    public function testCreate_MissingRequiredFields(): void
    {
        $data = ['name' => 'Proc', 'code' => 'PC001']; // missing category
        $request = $this->createRequest($data);
        $this->expectException(\TypeError::class);
        $this->controller->create($request);
    }

    // ==================== list ====================

    public function testList_Success(): void
    {
        $request = $this->createRequest([], 'GET', ['page' => '2', 'perPage' => '5', 'category' => 'license']);

        $proc1 = $this->createProcedureResponse(['id' => 'p1', 'name' => 'Proc1']);
        $proc2 = $this->createProcedureResponse(['id' => 'p2', 'name' => 'Proc2']);
        $procedures = [$proc1, $proc2];

        $this->serviceMock->expects($this->once())
            ->method('listProcedures')
            ->with(['category' => 'license'], 2, 5)
            ->willReturn($procedures);

        $response = $this->controller->list($request);
        $this->assertSame(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
        $this->assertSame(2, $result['page']);
        $this->assertSame(5, $result['perPage']);
    }

    public function testList_WithSearchAndStatus(): void
    {
        $request = $this->createRequest([], 'GET', ['search' => 'test', 'status' => 'active']);
        $this->serviceMock->expects($this->once())
            ->method('listProcedures')
            ->with(['search' => 'test', 'status' => 'active'], 1, 20)
            ->willReturn([]);
        $response = $this->controller->list($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== get ====================

    public function testGet_Success(): void
    {
        $id = 'proc123';
        $request = $this->createRequest([], 'GET');
        $procedure = $this->createProcedureResponse(['id' => $id, 'name' => 'Test']);
        $this->serviceMock->expects($this->once())
            ->method('getProcedureById')
            ->with($id)
            ->willReturn($procedure);
        $response = $this->controller->get($id, $request);
        $this->assertSame(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertSame($id, $result['id']);
    }

    public function testGet_NotFound(): void
    {
        $id = 'nonexistent';
        $request = $this->createRequest([], 'GET');
        $this->serviceMock->expects($this->once())
            ->method('getProcedureById')
            ->with($id)
            ->willReturn(null);
        $response = $this->controller->get($id, $request);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Procedure not found'], json_decode($response->getContent(), true));
    }

    // ==================== activate ====================

    public function testActivate_Success(): void
    {
        $id = 'proc123';
        $request = $this->createRequest([], 'POST');
        $this->serviceMock->expects($this->once())
            ->method('activateProcedure')
            ->with($id)
            ->willReturn(true);
        $response = $this->controller->activate($id, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Procedure activated successfully'], json_decode($response->getContent(), true));
    }

    public function testActivate_NotFound(): void
    {
        $id = 'proc999';
        $request = $this->createRequest([], 'POST');
        $this->serviceMock->expects($this->once())
            ->method('activateProcedure')
            ->with($id)
            ->willReturn(false);
        $response = $this->controller->activate($id, $request);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Procedure not found'], json_decode($response->getContent(), true));
    }

    // ==================== deactivate ====================

    public function testDeactivate_Success(): void
    {
        $id = 'proc123';
        $request = $this->createRequest([], 'POST');
        $this->serviceMock->expects($this->once())
            ->method('deactivateProcedure')
            ->with($id)
            ->willReturn(true);
        $response = $this->controller->deactivate($id, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Procedure deactivated successfully'], json_decode($response->getContent(), true));
    }

    public function testDeactivate_NotFound(): void
    {
        $id = 'proc999';
        $request = $this->createRequest([], 'POST');
        $this->serviceMock->expects($this->once())
            ->method('deactivateProcedure')
            ->with($id)
            ->willReturn(false);
        $response = $this->controller->deactivate($id, $request);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Procedure not found'], json_decode($response->getContent(), true));
    }

    // ==================== submitApplication ====================

    public function testSubmitApplication_Success(): void
    {
        $data = [
            'citizenId' => 'citizen1',
            'procedureId' => 'proc1',
            'documents' => ['doc1', 'doc2'],
            'submittedBy' => 'user1',
            'notes' => 'Please process'
        ];
        $request = $this->createRequest($data);

        $applicationDto = $this->createApplicationResponse(array_merge($data, ['id' => 'app123']));

        $this->serviceMock->expects($this->once())
            ->method('submitApplication')
            ->with($this->callback(function($arg) use ($data) {
                return $arg instanceof CreateProcedureApplicationRequest &&
                       $arg->citizenId === $data['citizenId'] &&
                       $arg->procedureId === $data['procedureId'] &&
                       $arg->documents === $data['documents'] &&
                       $arg->submittedBy === $data['submittedBy'] &&
                       $arg->notes === $data['notes'];
            }))
            ->willReturn($applicationDto);

        $response = $this->controller->submitApplication($request);
        $this->assertSame(201, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertSame($applicationDto->id, $result['id']);
    }

    public function testSubmitApplication_InvalidJson(): void
    {
        $request = Request::create('/api/government/procedures/applications', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{bad}');
        $response = $this->controller->submitApplication($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Invalid JSON'], json_decode($response->getContent(), true));
    }

    public function testSubmitApplication_MissingRequiredFields(): void
    {
        $data = ['citizenId' => 'c1']; // missing procedureId, submittedBy
        $request = $this->createRequest($data);
        $this->expectException(\TypeError::class);
        $this->controller->submitApplication($request);
    }

    // ==================== listApplications ====================

    public function testListApplications_Success(): void
    {
        $request = $this->createRequest([], 'GET', ['citizenId' => 'c1', 'status' => 'pending']);

        $app1 = $this->createApplicationResponse(['id' => 'a1']);
        $app2 = $this->createApplicationResponse(['id' => 'a2']);
        $this->serviceMock->expects($this->once())
            ->method('listApplications')
            ->with(['citizenId' => 'c1', 'status' => 'pending'], 1, 20)
            ->willReturn([$app1, $app2]);

        $response = $this->controller->listApplications($request);
        $this->assertSame(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(2, $result['data']);
    }

    public function testListApplications_WithPagination(): void
    {
        $request = $this->createRequest([], 'GET', ['page' => '3', 'perPage' => '10']);
        $this->serviceMock->expects($this->once())
            ->method('listApplications')
            ->with([], 3, 10)
            ->willReturn([]);
        $response = $this->controller->listApplications($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getApplication ====================

    public function testGetApplication_Success(): void
    {
        $id = 'app123';
        $request = $this->createRequest([], 'GET');
        $application = $this->createApplicationResponse(['id' => $id]);
        $this->serviceMock->expects($this->once())
            ->method('getApplicationById')
            ->with($id)
            ->willReturn($application);
        $response = $this->controller->getApplication($id, $request);
        $this->assertSame(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertSame($id, $result['id']);
    }

    public function testGetApplication_NotFound(): void
    {
        $id = 'app999';
        $request = $this->createRequest([], 'GET');
        $this->serviceMock->expects($this->once())
            ->method('getApplicationById')
            ->with($id)
            ->willReturn(null);
        $response = $this->controller->getApplication($id, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    // ==================== startProcessing ====================

    public function testStartProcessing_Success(): void
    {
        $id = 'app123';
        $data = ['processedBy' => 'admin1'];
        $request = $this->createRequest($data);
        $this->serviceMock->expects($this->once())
            ->method('startProcessing')
            ->with($id, 'admin1')
            ->willReturn(true);
        $response = $this->controller->startProcessing($id, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Processing started'], json_decode($response->getContent(), true));
    }

    public function testStartProcessing_NotFound(): void
    {
        $id = 'app999';
        $request = $this->createRequest([]);
        $this->serviceMock->expects($this->once())
            ->method('startProcessing')
            ->with($id, 'system')
            ->willReturn(false);
        $response = $this->controller->startProcessing($id, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    // ==================== approve ====================

    public function testApprove_Success(): void
    {
        $id = 'app123';
        $data = ['result' => 'Approved with conditions'];
        $request = $this->createRequest($data);
        $this->serviceMock->expects($this->once())
            ->method('approve')
            ->with($id, 'Approved with conditions')
            ->willReturn(true);
        $response = $this->controller->approve($id, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Application approved'], json_decode($response->getContent(), true));
    }

    public function testApprove_NotFound(): void
    {
        $id = 'app999';
        $request = $this->createRequest([]);
        $this->serviceMock->expects($this->once())
            ->method('approve')
            ->with($id, 'Approved')
            ->willReturn(false);
        $response = $this->controller->approve($id, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    // ==================== reject ====================

    public function testReject_Success(): void
    {
        $id = 'app123';
        $data = ['result' => 'Rejected: incomplete'];
        $request = $this->createRequest($data);
        $this->serviceMock->expects($this->once())
            ->method('reject')
            ->with($id, 'Rejected: incomplete')
            ->willReturn(true);
        $response = $this->controller->reject($id, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Application rejected'], json_decode($response->getContent(), true));
    }

    public function testReject_NotFound(): void
    {
        $id = 'app999';
        $request = $this->createRequest([]);
        $this->serviceMock->expects($this->once())
            ->method('reject')
            ->with($id, 'Rejected')
            ->willReturn(false);
        $response = $this->controller->reject($id, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    // ==================== complete ====================

    public function testComplete_Success(): void
    {
        $id = 'app123';
        $request = $this->createRequest([], 'POST');
        $this->serviceMock->expects($this->once())
            ->method('complete')
            ->with($id)
            ->willReturn(true);
        $response = $this->controller->complete($id, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Application completed'], json_decode($response->getContent(), true));
    }

    public function testComplete_NotFound(): void
    {
        $id = 'app999';
        $request = $this->createRequest([], 'POST');
        $this->serviceMock->expects($this->once())
            ->method('complete')
            ->with($id)
            ->willReturn(false);
        $response = $this->controller->complete($id, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    // ==================== statistics ====================

    public function testStatistics_Success(): void
    {
        $request = $this->createRequest([], 'GET');
        $stats = [
            'total_procedures' => 12,
            'active_procedures' => 8,
            'total_applications' => 150,
            'pending_applications' => 20,
            'approved_applications' => 100,
            'rejected_applications' => 30
        ];
        $this->serviceMock->expects($this->once())
            ->method('getStatistics')
            ->willReturn($stats);
        $response = $this->controller->statistics($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($stats, json_decode($response->getContent(), true));
    }
}
