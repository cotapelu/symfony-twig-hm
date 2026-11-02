<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Government\Application\Service\StatisticsService;
use App\Government\Domain\Repository\CitizenRepositoryInterface;
use App\Government\Domain\Repository\DocumentRepositoryInterface;
use App\Government\Domain\Repository\AdministrativeProcedureRepositoryInterface;
use App\Government\Domain\Repository\OfficerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatisticsServiceTest extends TestCase
{
    private CitizenRepositoryInterface&MockObject $citizenRepository;
    private DocumentRepositoryInterface&MockObject $documentRepository;
    private AdministrativeProcedureRepositoryInterface&MockObject $procedureRepository;
    private OfficerRepositoryInterface&MockObject $officerRepository;
    private StatisticsService $service;

    protected function setUp(): void
    {
        $this->citizenRepository = $this->createMock(CitizenRepositoryInterface::class);
        $this->documentRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->procedureRepository = $this->createMock(AdministrativeProcedureRepositoryInterface::class);
        $this->officerRepository = $this->createMock(OfficerRepositoryInterface::class);
        $this->service = new StatisticsService(
            $this->citizenRepository,
            $this->documentRepository,
            $this->procedureRepository,
            $this->officerRepository
        );
    }

    public function testGetCitizenStatistics(): void
    {
        $this->citizenRepository
            ->expects($this->exactly(3))
            ->method('count')
            ->willReturnOnConsecutiveCalls(100, 60, 40);

        $stats = $this->service->getCitizenStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertEquals(100, $stats['total']);
        $this->assertArrayHasKey('active', $stats);
        $this->assertEquals(60, $stats['active']);
        $this->assertArrayHasKey('inactive', $stats);
        $this->assertEquals(40, $stats['inactive']);
        $this->assertArrayHasKey('byProvince', $stats);
        $this->assertIsArray($stats['byProvince']);
        $this->assertArrayHasKey('byAgeRange', $stats);
        $this->assertIsArray($stats['byAgeRange']);
    }

    public function testGetDocumentStatistics(): void
    {
        $this->documentRepository
            ->expects($this->exactly(6))
            ->method('count')
            ->willReturnOnConsecutiveCalls(
                50, // total
                10, // draft
                5,  // pending
                20, // approved
                5,  // rejected
                10  // published
            );

        $stats = $this->service->getDocumentStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertEquals(50, $stats['total']);
        $this->assertArrayHasKey('draft', $stats);
        $this->assertEquals(10, $stats['draft']);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertEquals(5, $stats['pending']);
        $this->assertArrayHasKey('approved', $stats);
        $this->assertEquals(20, $stats['approved']);
        $this->assertArrayHasKey('rejected', $stats);
        $this->assertEquals(5, $stats['rejected']);
        $this->assertArrayHasKey('published', $stats);
        $this->assertEquals(10, $stats['published']);
        $this->assertArrayHasKey('byType', $stats);
        $this->assertIsArray($stats['byType']);
    }

    public function testGetProcedureStatistics(): void
    {
        $this->procedureRepository
            ->expects($this->exactly(3))
            ->method('count')
            ->willReturnOnConsecutiveCalls(
                30, // total
                20, // active
                10  // inactive
            );

        $stats = $this->service->getProcedureStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertEquals(30, $stats['total']);
        $this->assertArrayHasKey('active', $stats);
        $this->assertEquals(20, $stats['active']);
        $this->assertArrayHasKey('inactive', $stats);
        $this->assertEquals(10, $stats['inactive']);
        $this->assertArrayHasKey('byCategory', $stats);
        $this->assertIsArray($stats['byCategory']);
    }

    public function testGetOfficerStatistics(): void
    {
        $this->officerRepository
            ->expects($this->exactly(3))
            ->method('count')
            ->willReturnOnConsecutiveCalls(
                20, // total
                15, // active
                5   // inactive
            );

        $stats = $this->service->getOfficerStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertEquals(20, $stats['total']);
        $this->assertArrayHasKey('active', $stats);
        $this->assertEquals(15, $stats['active']);
        $this->assertArrayHasKey('inactive', $stats);
        $this->assertEquals(5, $stats['inactive']);
        $this->assertArrayHasKey('byWard', $stats);
        $this->assertIsArray($stats['byWard']);
        $this->assertArrayHasKey('performance', $stats);
        $this->assertIsArray($stats['performance']);
    }

    public function testGetDashboardStatistics(): void
    {
        $this->citizenRepository
            ->expects($this->exactly(3))
            ->method('count')
            ->willReturnOnConsecutiveCalls(100, 60, 40);
        $this->documentRepository
            ->expects($this->exactly(6))
            ->method('count')
            ->willReturnOnConsecutiveCalls(50, 10, 5, 20, 5, 10);
        $this->procedureRepository
            ->expects($this->exactly(3))
            ->method('count')
            ->willReturnOnConsecutiveCalls(30, 20, 10);
        $this->officerRepository
            ->expects($this->exactly(3))
            ->method('count')
            ->willReturnOnConsecutiveCalls(20, 15, 5);

        $stats = $this->service->getDashboardStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('citizens', $stats);
        $this->assertArrayHasKey('documents', $stats);
        $this->assertArrayHasKey('procedures', $stats);
        $this->assertArrayHasKey('officers', $stats);
        $this->assertArrayHasKey('generatedAt', $stats);
        $this->assertIsString($stats['generatedAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $stats['generatedAt']);
    }
}