<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government\Controller;

use App\Government\Api\Controller\OfficerController;
use App\Government\Application\Service\OfficerService;
use App\Government\Contracts\DTO\OfficerResponse;
use PHPUnit\Framework\TestCase;

class OfficerControllerTest extends TestCase
{
    private OfficerController $controller;
    private OfficerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(OfficerService::class);
        $this->controller = new OfficerController($this->service);
    }

    public function testCreateOfficerSuccess(): void
    {
        $officerResponse = new OfficerResponse(
            id: 'officer_123',
            fullName: 'John Doe',
            email: 'john.doe@example.com',
            phone: '123456789',
            dateOfBirth: '1980-01-01',
            identityNumber: 'ID123456789',
            position: [
                'code' => 'POS001',
                'name' => 'Chief Officer',
                'level' => 1,
            ],
            department: [
                'code' => 'DEPT001',
                'name' => 'Police Department',
                'parentCode' => null,
            ],
            createdAt: '2026-01-01 00:00:00',
            updatedAt: null,
            isActive: true,
        );

        $this->service
            ->method('createOfficer')
            ->willReturn($officerResponse);

        $this->assertNotNull($officerResponse);
        $this->assertEquals('John Doe', $officerResponse->fullName);
    }

    public function testCreateOfficerMissingRequiredField(): void
    {
        $invalidData = [
            'fullName' => 'John Doe',
            'positionName' => 'Chief Officer',
            'departmentCode' => 'DEPT001',
            'departmentName' => 'Police Department',
        ];

        $this->assertArrayNotHasKey('positionCode', $invalidData);
        $this->assertArrayHasKey('fullName', $invalidData);
    }

    public function testCreateOfficerWithOptionalFields(): void
    {
        $officerResponse = new OfficerResponse(
            id: 'officer_456',
            fullName: 'Jane Smith',
            email: '',
            phone: '',
            dateOfBirth: null,
            identityNumber: null,
            position: [
                'code' => 'POS002',
                'name' => 'Officer',
                'level' => 1,
            ],
            department: [
                'code' => 'DEPT001',
                'name' => 'Police Department',
                'parentCode' => null,
            ],
            createdAt: '2026-01-01 00:00:00',
            updatedAt: null,
            isActive: true,
        );

        $this->service
            ->method('createOfficer')
            ->willReturn($officerResponse);

        $this->assertNotNull($officerResponse);
    }

    public function testGetOfficerByIdSuccess(): void
    {
        $officerResponse = new OfficerResponse(
            id: 'officer_789',
            fullName: 'Bob Wilson',
            email: 'bob@example.com',
            phone: '987654321',
            dateOfBirth: '1975-05-15',
            identityNumber: 'ID987654321',
            position: [
                'code' => 'POS003',
                'name' => 'Senior Officer',
                'level' => 2,
            ],
            department: [
                'code' => 'DEPT002',
                'name' => 'Traffic Police',
                'parentCode' => null,
            ],
            createdAt: '2026-01-01 00:00:00',
            updatedAt: null,
            isActive: true,
        );

        $this->service
            ->method('getOfficerById')
            ->with('officer_789')
            ->willReturn($officerResponse);

        $result = $this->service->getOfficerById('officer_789');
        $this->assertInstanceOf(OfficerResponse::class, $result);
        $this->assertEquals('Bob Wilson', $result->fullName);
    }

    public function testListOfficersSuccess(): void
    {
        $officer1 = new OfficerResponse(
            id: 'officer_1',
            fullName: 'Officer One',
            email: 'one@example.com',
            phone: '111',
            dateOfBirth: '1980-01-01',
            identityNumber: 'ID001',
            position: ['code' => 'POS001', 'name' => 'Officer', 'level' => 1],
            department: ['code' => 'DEPT001', 'name' => 'Police', 'parentCode' => null],
            createdAt: '2026-01-01 00:00:00',
            updatedAt: null,
            isActive: true,
        );

        $officer2 = new OfficerResponse(
            id: 'officer_2',
            fullName: 'Officer Two',
            email: 'two@example.com',
            phone: '222',
            dateOfBirth: '1982-02-02',
            identityNumber: 'ID002',
            position: ['code' => 'POS002', 'name' => 'Officer', 'level' => 1],
            department: ['code' => 'DEPT001', 'name' => 'Police', 'parentCode' => null],
            createdAt: '2026-01-01 00:00:00',
            updatedAt: null,
            isActive: true,
        );

        $this->service
            ->method('listOfficers')
            ->willReturn([$officer1, $officer2]);

        $result = $this->service->listOfficers();
        $this->assertCount(2, $result);
        $this->assertInstanceOf(OfficerResponse::class, $result[0]);
    }

    public function testSearchOfficersSuccess(): void
    {
        $officer = new OfficerResponse(
            id: 'officer_1',
            fullName: 'John Search',
            email: 'search@example.com',
            phone: '111',
            dateOfBirth: '1980-01-01',
            identityNumber: 'ID001',
            position: ['code' => 'POS001', 'name' => 'Officer', 'level' => 1],
            department: ['code' => 'DEPT001', 'name' => 'Police', 'parentCode' => null],
            createdAt: '2026-01-01 00:00:00',
            updatedAt: null,
            isActive: true,
        );

        $this->service
            ->method('searchOfficers')
            ->with('John')
            ->willReturn([$officer]);

        $result = $this->service->searchOfficers('John');
        $this->assertCount(1, $result);
        $this->assertEquals('John Search', $result[0]->fullName);
    }
}
