<?php

declare(strict_types=1);

namespace App\Tests\Controller\Government\Api;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Api\Controller\OfficerController;
use App\Government\Application\Service\OfficerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class OfficerControllerTest extends TestCase
{
    private OfficerController $controller;
    private OfficerService|MockObject $officerService;

    protected function setUp(): void
    {
        $this->officerService = $this->createMock(OfficerService::class);
        $this->controller = new OfficerController($this->officerService);
    }

    public function testCreateOfficerSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fullName' => 'John Doe',
                'positionCode' => 'POS-001',
                'positionName' => 'Director',
                'departmentCode' => 'DEPT-001',
                'departmentName' => 'Department A',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'dateOfBirth' => '1980-01-01',
                'identityNumber' => 'ID123456',
                'positionLevel' => 5,
            ])
        );

        $expectedOfficer = [
            'id' => 'officer123',
            'fullName' => 'John Doe',
            'positionCode' => 'POS-001',
        ];

        $this->officerService
            ->expects($this->once())
            ->method('createOfficer')
            ->willReturn($expectedOfficer);

        $result = $this->controller->create($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('officer123', $data['id']);
    }

    public function testCreateOfficerWithMinimalFields(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fullName' => 'Jane Doe',
                'positionCode' => 'POS-002',
                'positionName' => 'Manager',
                'departmentCode' => 'DEPT-002',
                'departmentName' => 'Department B',
            ])
        );

        $expectedOfficer = ['id' => 'officer456'];

        $this->officerService
            ->expects($this->once())
            ->method('createOfficer')
            ->willReturn($expectedOfficer);

        $result = $this->controller->create($request);

        $this->assertEquals(201, $result->getStatusCode());
    }

    public function testGetOfficerSuccess(): void
    {
        $officer = [
            'id' => 'officer123',
            'fullName' => 'John Doe',
            'positionCode' => 'POS-001',
            'positionName' => 'Director',
            'departmentCode' => 'DEPT-001',
            'departmentName' => 'Department A',
            'isActive' => true,
        ];

        $this->officerService
            ->expects($this->once())
            ->method('getOfficerById')
            ->with('officer123')
            ->willReturn($officer);

        $result = $this->controller->get('officer123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('officer123', $data['id']);
        $this->assertEquals('John Doe', $data['fullName']);
    }

    public function testGetOfficerNotFound(): void
    {
        $this->officerService
            ->expects($this->once())
            ->method('getOfficerById')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->controller->get('notfound');

        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Officer not found', $data['error']);
    }

    public function testListOfficersWithAllFilters(): void
    {
        $request = new Request([
            'search' => 'John',
            'department' => 'DEPT-001',
            'active' => 'true',
            'page' => '1',
            'perPage' => '10',
        ]);

        $officers = [
            ['id' => 'officer1', 'fullName' => 'John Doe'],
            ['id' => 'officer2', 'fullName' => 'John Smith'],
        ];

        $this->officerService
            ->expects($this->once())
            ->method('listOfficers')
            ->with(
                [
                    'search' => 'John',
                    'departmentCode' => 'DEPT-001',
                    'isActive' => true,
                ],
                1,
                10
            )
            ->willReturn($officers);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data['data']);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(10, $data['perPage']);
    }

    public function testListOfficersWithSearchFilter(): void
    {
        $request = new Request(['search' => 'Doe']);

        $officers = [];

        $this->officerService
            ->expects($this->once())
            ->method('listOfficers')
            ->with(['search' => 'Doe'], 1, 20)
            ->willReturn($officers);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testListOfficersWithDepartmentFilter(): void
    {
        $request = new Request(['department' => 'DEPT-001']);

        $officers = [];

        $this->officerService
            ->expects($this->once())
            ->method('listOfficers')
            ->with(['departmentCode' => 'DEPT-001'], 1, 20)
            ->willReturn($officers);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testListOfficersWithActiveFilter(): void
    {
        $request = new Request(['active' => 'false']);

        $officers = [];

        $this->officerService
            ->expects($this->once())
            ->method('listOfficers')
            ->with(['isActive' => false], 1, 20)
            ->willReturn($officers);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testListOfficersWithoutFilters(): void
    {
        $request = new Request();

        $officers = [];

        $this->officerService
            ->expects($this->once())
            ->method('listOfficers')
            ->with([], 1, 20)
            ->willReturn($officers);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(20, $data['perPage']);
    }

    public function testListOfficersWithPagination(): void
    {
        $request = new Request([
            'page' => '2',
            'perPage' => '50',
        ]);

        $officers = [];

        $this->officerService
            ->expects($this->once())
            ->method('listOfficers')
            ->with([], 2, 50)
            ->willReturn($officers);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals(2, $data['page']);
        $this->assertEquals(50, $data['perPage']);
    }

    public function testSearchOfficersSuccess(): void
    {
        $request = new Request(['q' => 'John Doe']);

        $officers = [
            ['id' => 'officer1', 'fullName' => 'John Doe'],
            ['id' => 'officer2', 'fullName' => 'John Doe Jr'],
        ];

        $this->officerService
            ->expects($this->once())
            ->method('searchOfficers')
            ->with('John Doe')
            ->willReturn($officers);

        $result = $this->controller->search($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data['data']);
    }

    public function testSearchOfficersEmptyQuery(): void
    {
        $request = new Request(['q' => '']);

        $result = $this->controller->search($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Search query is required', $data['error']);
    }

    public function testSearchOfficersNoQuery(): void
    {
        $request = new Request();

        $result = $this->controller->search($request);

        $this->assertEquals(400, $result->StatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Search query is required', $data['error']);
    }

    public function testDeactivateOfficerSuccess(): void
    {
        $this->officerService
            ->expects($this->once())
            ->method('deactivateOfficer')
            ->with('officer123');

        $result = $this->controller->deactivate('officer123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Officer deactivated successfully', $data['message']);
    }

    public function testActivateOfficerSuccess(): void
    {
        $this->officerService
            ->expects($this->once())
            ->method('activateOfficer')
            ->with('officer123');

        $result = $this->controller->activate('officer123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Officer activated successfully', $data['message']);
    }

    public function testListOfficersWithBooleanActiveFilter(): void
    {
        $request = new Request(['active' => '1']);

        $officers = [];

        $this->officerService
            ->expects($this->once())
            ->method('listOfficers')
            ->with(['isActive' => true], 1, 20)
            ->willReturn($officers);

        $result = $this->controller->list($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testListOfficersReturnsCorrectStructure(): void
    {
        $request = new Request();

        $officers = [['id' => 'officer1', 'fullName' => 'Test Officer']];

        $this->officerService
            ->expects($this->once())
            ->method('listOfficers')
            ->willReturn($officers);

        $result = $this->controller->list($request);

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('page', $data);
        $this->assertArrayHasKey('perPage', $data);
    }

    public function testSearchOfficersReturnsCorrectStructure(): void
    {
        $request = new Request(['q' => 'test']);

        $officers = [['id' => 'officer1', 'fullName' => 'Test']];

        $this->officerService
            ->expects($this->once())
            ->method('searchOfficers')
            ->willReturn($officers);

        $result = $this->controller->search($request);

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }
}
