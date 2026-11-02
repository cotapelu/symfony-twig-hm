<?php declare(strict_types=1);

namespace App\Tests\Unit\ANTT\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ANTT\Application\Service\GovernmentIntegrationService;
use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Domain\Entity\Patrol;
use App\ANTT\Domain\Entity\SecurityCase;
use App\ANTT\Domain\Repository\IncidentRepositoryInterface;
use App\ANTT\Domain\Repository\PatrolRepositoryInterface;
use App\ANTT\Domain\Repository\SecurityCaseRepositoryInterface;
use App\Government\Domain\Entity\Officer;
use App\Government\Domain\Entity\Ward;
use App\Government\Domain\ValueObject\Department;
use App\Government\Domain\Repository\OfficerRepositoryInterface;
use App\Government\Domain\Repository\WardRepositoryInterface;
use App\Government\Domain\ValueObject\OfficerId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GovernmentIntegrationServiceTest extends TestCase
{
    private IncidentRepositoryInterface&MockObject $incidentRepository;
    private PatrolRepositoryInterface&MockObject $patrolRepository;
    private SecurityCaseRepositoryInterface&MockObject $securityCaseRepository;
    private OfficerRepositoryInterface&MockObject $officerRepository;
    private WardRepositoryInterface&MockObject $wardRepository;
    private GovernmentIntegrationService $service;

    protected function setUp(): void
    {
        $this->incidentRepository = $this->createMock(IncidentRepositoryInterface::class);
        $this->patrolRepository = $this->createMock(PatrolRepositoryInterface::class);
        $this->securityCaseRepository = $this->createMock(SecurityCaseRepositoryInterface::class);
        $this->officerRepository = $this->createMock(OfficerRepositoryInterface::class);
        $this->wardRepository = $this->createMock(WardRepositoryInterface::class);

        $this->service = new GovernmentIntegrationService(
            $this->incidentRepository,
            $this->patrolRepository,
            $this->securityCaseRepository,
            $this->officerRepository,
            $this->wardRepository
        );
    }

    public function testGetIncidentsForOfficerWhenOfficerNotFoundReturnsEmpty(): void
    {
        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn(OfficerId $id) => $id->getValue() === 'off-1'))
            ->willReturn(null);

        $result = $this->service->getIncidentsForOfficer('off-1');

        $this->assertEmpty($result);
    }

    public function testGetIncidentsForOfficerWhenNoWardsReturnsEmpty(): void
    {
        $officer = $this->createMock(Officer::class);
        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->willReturn($officer);
        $this->wardRepository->expects($this->once())
            ->method('findByAssignedOfficer')
            ->with('off-1')
            ->willReturn([]);

        $result = $this->service->getIncidentsForOfficer('off-1');

        $this->assertEmpty($result);
    }

    public function testGetIncidentsForOfficerAggregatesFromMultipleWards(): void
    {
        $officer = $this->createMock(Officer::class);
        $wardA = $this->createMock(Ward::class);
        $wardB = $this->createMock(Ward::class);
        $wards = [$wardA, $wardB];
        $incident1 = $this->createMock(Incident::class);
        $incident2 = $this->createMock(Incident::class);
        $incident3 = $this->createMock(Incident::class);

        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->willReturn($officer);
        $this->wardRepository->expects($this->once())
            ->method('findByAssignedOfficer')
            ->with('off-1')
            ->willReturn($wards);
        $this->incidentRepository->expects($this->exactly(2))
            ->method('findByWard')
            ->willReturnOnConsecutiveCalls([$incident1, $incident2], [$incident3]);

        $result = $this->service->getIncidentsForOfficer('off-1');

        $this->assertCount(3, $result);
        $this->assertContains($incident1, $result);
        $this->assertContains($incident2, $result);
        $this->assertContains($incident3, $result);
    }

    public function testGetPatrolsForOfficer(): void
    {
        $patrols = [$this->createMock(Patrol::class), $this->createMock(Patrol::class)];
        $this->patrolRepository->expects($this->once())
            ->method('findByOfficer')
            ->with('off-1')
            ->willReturn($patrols);

        $result = $this->service->getPatrolsForOfficer('off-1');

        $this->assertSame($patrols, $result);
    }

    public function testGetSecurityCasesForOfficer(): void
    {
        $cases = [$this->createMock(SecurityCase::class)];
        $this->securityCaseRepository->expects($this->once())
            ->method('findByInvestigationOfficer')
            ->with('off-1')
            ->willReturn($cases);

        $result = $this->service->getSecurityCasesForOfficer('off-1');

        $this->assertSame($cases, $result);
    }

    public function testGetWardStatistics(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('countByWardCode')
            ->with('W001')
            ->willReturn(['pending' => 5, 'assigned' => 3, 'resolved' => 2]);

        $result = $this->service->getWardStatistics('W001');

        $this->assertSame([
            'incidents' => ['pending' => 5, 'assigned' => 3, 'resolved' => 2],
            'total_incidents' => 10,
            'open_incidents' => 8,
        ], $result);
    }

    public function testLinkIncidentToWardIncidentNotFound(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with('inc-1')
            ->willReturn(null);
        $this->wardRepository->expects($this->never())
            ->method('findByCode');

        $result = $this->service->linkIncidentToWard('inc-1', 'W001');

        $this->assertFalse($result);
    }

    public function testLinkIncidentToWardWardNotFound(): void
    {
        $incident = $this->createMock(Incident::class);
        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with('inc-1')
            ->willReturn($incident);
        $this->wardRepository->expects($this->once())
            ->method('findByCode')
            ->with('W001')
            ->willReturn(null);
        $incident->expects($this->never())
            ->method('setWardCode');

        $result = $this->service->linkIncidentToWard('inc-1', 'W001');

        $this->assertFalse($result);
    }

    public function testLinkIncidentToWardSuccess(): void
    {
        $incident = $this->createMock(Incident::class);
        $ward = $this->createMock(Ward::class);
        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with('inc-1')
            ->willReturn($incident);
        $this->wardRepository->expects($this->once())
            ->method('findByCode')
            ->with('W001')
            ->willReturn($ward);
        $incident->expects($this->once())
            ->method('setWardCode')
            ->with('W001');
        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($incident);

        $result = $this->service->linkIncidentToWard('inc-1', 'W001');

        $this->assertTrue($result);
    }

    public function testLinkCaseToWardCaseNotFound(): void
    {
        $this->securityCaseRepository->expects($this->once())
            ->method('findById')
            ->with('case-1')
            ->willReturn(null);
        $this->wardRepository->expects($this->never())
            ->method('findByCode');

        $result = $this->service->linkCaseToWard('case-1', 'W001');

        $this->assertFalse($result);
    }

    public function testLinkCaseToWardWardNotFound(): void
    {
        $case = $this->createMock(SecurityCase::class);
        $this->securityCaseRepository->expects($this->once())
            ->method('findById')
            ->with('case-1')
            ->willReturn($case);
        $this->wardRepository->expects($this->once())
            ->method('findByCode')
            ->with('W001')
            ->willReturn(null);
        $case->expects($this->never())
            ->method('setLocation');

        $result = $this->service->linkCaseToWard('case-1', 'W001');

        $this->assertFalse($result);
    }

    public function testLinkCaseToWardSuccess(): void
    {
        $case = $this->createMock(SecurityCase::class);
        $ward = $this->createMock(Ward::class);
        $this->securityCaseRepository->expects($this->once())
            ->method('findById')
            ->with('case-1')
            ->willReturn($case);
        $this->wardRepository->expects($this->once())
            ->method('findByCode')
            ->with('W001')
            ->willReturn($ward);
        $case->expects($this->once())
            ->method('setLocation')
            ->with($this->anything(), 'W001');
        $this->securityCaseRepository->expects($this->once())
            ->method('save')
            ->with($case);

        $result = $this->service->linkCaseToWard('case-1', 'W001');

        $this->assertTrue($result);
    }

    public function testAssignIncidentToOfficerIncidentNotFound(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with('inc-1')
            ->willReturn(null);
        $this->officerRepository->expects($this->never())
            ->method('findById');

        $result = $this->service->assignIncidentToOfficer('inc-1', 'off-1');

        $this->assertFalse($result);
    }

    public function testAssignIncidentToOfficerOfficerNotFound(): void
    {
        $incident = $this->createMock(Incident::class);
        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with('inc-1')
            ->willReturn($incident);
        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn(OfficerId $id) => $id->getValue() === 'off-1'))
            ->willReturn(null);
        $incident->expects($this->never())
            ->method('assignTo');

        $result = $this->service->assignIncidentToOfficer('inc-1', 'off-1');

        $this->assertFalse($result);
    }

    public function testAssignIncidentToOfficerSuccess(): void
    {
        $incident = $this->createMock(Incident::class);
        $officer = $this->createMock(Officer::class);
        $department = new Department('DPT', 'DeptX');
        $officer->method('getDepartment')->willReturn($department);

        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with('inc-1')
            ->willReturn($incident);
        $this->officerRepository->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn(OfficerId $id) => $id->getValue() === 'off-1'))
            ->willReturn($officer);
        $incident->expects($this->once())
            ->method('assignTo')
            ->with('off-1', 'DeptX')
            ->willReturn($incident);
        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($incident);

        $result = $this->service->assignIncidentToOfficer('inc-1', 'off-1');

        $this->assertTrue($result);
    }


}
