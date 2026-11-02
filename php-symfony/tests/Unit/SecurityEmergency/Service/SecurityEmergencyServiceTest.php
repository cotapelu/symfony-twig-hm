<?php declare(strict_types=1);

namespace App\Tests\Unit\SecurityEmergency\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\SecurityEmergency\Service\SecurityEmergencyService;
use App\SecurityEmergency\Entity\SecurityIncident;
use App\SecurityEmergency\Repository\SecurityIncidentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SecurityEmergencyServiceTest extends TestCase
{
    private SecurityIncidentRepository&MockObject $incidentRepo;
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private SecurityEmergencyService $service;

    protected function setUp(): void
    {
        $this->incidentRepo = $this->createMock(SecurityIncidentRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new SecurityEmergencyService($this->incidentRepo, $this->em, $this->logger);
    }

    private function createIncident(array $overrides = []): SecurityIncident
    {
        $type = $overrides['type'] ?? 'Trộm cắp';
        $status = $overrides['status'] ?? 'Đang điều tra';
        $reporterId = $overrides['reporterId'] ?? 'user-1';
        $location = $overrides['location'] ?? null;
        $description = $overrides['description'] ?? null;
        $id = $overrides['id'] ?? null;

        $incident = new SecurityIncident($type, $status, $reporterId, $location, $description);
        if ($id !== null) {
            $ref = new \ReflectionProperty(SecurityIncident::class, 'id');
            $ref->setAccessible(true);
            $ref->setValue($incident, $id);
        }
        return $incident;
    }

    public function testCreateIncidentValid(): void
    {
        $this->incidentRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (SecurityIncident $i) {
                return $i->getType() === 'Trộm cắp'
                    && $i->getStatus() === 'Đang điều tra'
                    && $i->getReporterId() === 'user-1';
            }))
            ->willReturnCallback(function (SecurityIncident $i) {
                if ($i->getId() === null) {
                    $ref = new \ReflectionProperty(SecurityIncident::class, 'id');
                    $ref->setAccessible(true);
                    $ref->setValue($i, 'inc-123');
                }
                return $i;
            });

        $result = $this->service->createIncident(
            'Trộm cắp',
            'user-1',
            '123 Street',
            'Theft description'
        );

        $this->assertInstanceOf(SecurityIncident::class, $result);
        $this->assertNotNull($result->getId());
        $this->assertSame('Trộm cắp', $result->getType());
        $this->assertSame('Đang điều tra', $result->getStatus());
        $this->assertSame('user-1', $result->getReporterId());
        $this->assertSame('123 Street', $result->getLocation());
        $this->assertSame('Theft description', $result->getDescription());
    }

    public function testCreateIncidentInvalidTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid incident type');
        $this->service->createIncident('InvalidType', 'user-1');
    }

    public function testCreateIncidentInvalidStatusThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid incident status');
        $this->service->createIncident('Trộm cắp', 'user-1', null, null, 'InvalidStatus');
    }

    public function testGetIncidentFound(): void
    {
        $incident = $this->createIncident(['id' => 'inc-123']);
        $this->incidentRepo->expects($this->once())
            ->method('findById')
            ->with('inc-123')
            ->willReturn($incident);

        $result = $this->service->getIncident('inc-123');

        $this->assertSame($incident, $result);
    }

    public function testGetIncidentNotFound(): void
    {
        $this->incidentRepo->expects($this->once())
            ->method('findById')
            ->with('inc-999')
            ->willReturn(null);

        $result = $this->service->getIncident('inc-999');

        $this->assertNull($result);
    }

    public function testUpdateIncidentStatusSuccess(): void
    {
        $incident = $this->createIncident(['id' => 'inc-123', 'status' => 'Đang điều tra']);
        $this->incidentRepo->expects($this->once())
            ->method('findById')
            ->with('inc-123')
            ->willReturn($incident);
        $this->incidentRepo->expects($this->once())
            ->method('save')
            ->with($incident);

        $result = $this->service->updateIncidentStatus('inc-123', 'Đã xử lý');

        $this->assertSame($incident, $result);
        $this->assertSame('Đã xử lý', $incident->getStatus());
    }

    public function testUpdateIncidentStatusNotFound(): void
    {
        $this->incidentRepo->expects($this->once())
            ->method('findById')
            ->with('inc-999')
            ->willReturn(null);

        $result = $this->service->updateIncidentStatus('inc-999', 'Đã xử lý');

        $this->assertNull($result);
    }

    public function testDeleteIncidentSuccess(): void
    {
        $incident = $this->createIncident(['id' => 'inc-123']);
        $this->incidentRepo->expects($this->once())
            ->method('findById')
            ->with('inc-123')
            ->willReturn($incident);
        $this->incidentRepo->expects($this->once())
            ->method('delete')
            ->with($incident);

        $result = $this->service->deleteIncident('inc-123');

        $this->assertTrue($result);
    }

    public function testDeleteIncidentNotFound(): void
    {
        $this->incidentRepo->expects($this->once())
            ->method('findById')
            ->with('inc-999')
            ->willReturn(null);

        $result = $this->service->deleteIncident('inc-999');

        $this->assertFalse($result);
    }

    public function testGetIncidentsByStatus(): void
    {
        $incidents = [$this->createIncident(), $this->createIncident()];
        $this->incidentRepo->expects($this->once())
            ->method('findAllByStatus')
            ->with('Đang điều tra', 50, 0)
            ->willReturn($incidents);

        $result = $this->service->getIncidentsByStatus('Đang điều tra');

        $this->assertSame($incidents, $result);
    }

    public function testGetIncidentsByType(): void
    {
        $incidents = [$this->createIncident(['type' => 'Hỏa hoạn'])];
        $this->incidentRepo->expects($this->once())
            ->method('findAllByType')
            ->with('Hỏa hoạn', 50, 0)
            ->willReturn($incidents);

        $result = $this->service->getIncidentsByType('Hỏa hoạn');

        $this->assertSame($incidents, $result);
    }

    public function testGetIncidentsByReporter(): void
    {
        $incidents = [$this->createIncident(['reporterId' => 'user-2'])];
        $this->incidentRepo->expects($this->once())
            ->method('findByReporter')
            ->with('user-2', 50, 0)
            ->willReturn($incidents);

        $result = $this->service->getIncidentsByReporter('user-2');

        $this->assertSame($incidents, $result);
    }

    public function testGetAllIncidents(): void
    {
        $incidents = [$this->createIncident()];
        $this->incidentRepo->expects($this->once())
            ->method('findBy')
            ->with([], ['createdAt' => 'DESC'], 50, 0)
            ->willReturn($incidents);

        $result = $this->service->getAllIncidents();

        $this->assertSame($incidents, $result);
    }
}
