<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\AdministrativeProcedure;
use App\Entity\Php\ProcedureApplication;
use App\Service\Php\ProcedureApplicationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProcedureApplicationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private ProcedureApplicationService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ProcedureApplicationService($this->em, $this->logger);
    }

    public function testSubmitApplicationSuccess(): void
    {
        $citizenId = 'citizen_123';
        $procedureId = 'proc_123';
        $documents = ['id_card', 'proof_address'];
        $wardId = 'ward_01';
        $metadata = ['reason' => 'new business'];

        $procedure = new AdministrativeProcedure($procedureId, 'Business License', 'Business', 'Commerce', 15);
        $procedure->setStatus('ACTIVE');

        $procRepo = $this->createMock(ObjectRepository::class);
        $procRepo->expects($this->once())
            ->method('find')
            ->with($procedureId)
            ->willReturn($procedure);

        $appRepo = $this->createMock(ObjectRepository::class);
        $appRepo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(AdministrativeProcedure::class)
            ->willReturn($procRepo);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ProcedureApplication::class)
            ->willReturn($appRepo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ProcedureApplication $a) use (
                $citizenId, $procedureId, $documents, $wardId, $metadata
            ) {
                return $a->getCitizenId() === $citizenId
                    && $a->getProcedureId() === $procedureId
                    && $a->getDocuments() === $documents
                    && $a->getWardId() === $wardId
                    && $a->getMetadata() === $metadata
                    && $a->getStatus() === 'PENDING';
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Procedure application submitted', $this->anything());

        $result = $this->service->submitApplication($citizenId, $procedureId, $documents, $wardId, $metadata);
        $this->assertInstanceOf(ProcedureApplication::class, $result);
        $this->assertSame($citizenId, $result->getCitizenId());
        $this->assertSame($procedureId, $result->getProcedureId());
        $this->assertSame('PENDING', $result->getStatus());
    }

    public function testSubmitApplicationProcedureNotFound(): void
    {
        $procRepo = $this->createMock(ObjectRepository::class);
        $procRepo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(AdministrativeProcedure::class)
            ->willReturn($procRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Procedure not found');
        $this->service->submitApplication('citizen', 'proc', []);
    }

    public function testSubmitApplicationInactiveProcedure(): void
    {
        $procedureId = 'proc_123';
        $procedure = new AdministrativeProcedure($procedureId, 'Name', 'D', 'Cat', 10);
        $procedure->setStatus('INACTIVE');

        $procRepo = $this->createMock(ObjectRepository::class);
        $procRepo->expects($this->once())
            ->method('find')
            ->willReturn($procedure);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(AdministrativeProcedure::class)
            ->willReturn($procRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Procedure is not active');
        $this->service->submitApplication('citizen', $procedureId, []);
    }

    public function testGetApplication(): void
    {
        $id = 'app_123';
        $application = new ProcedureApplication($id, 'citizen', 'proc', ['doc']);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($application);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ProcedureApplication::class)
            ->willReturn($repo);

        $result = $this->service->getApplication($id);
        $this->assertSame($application, $result);
    }

    public function testGetApplicationsByCitizen(): void
    {
        $citizenId = 'citizen_123';
        $apps = [
            new ProcedureApplication('id1', $citizenId, 'proc1', ['doc1']),
            new ProcedureApplication('id2', $citizenId, 'proc2', ['doc2']),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['citizenId' => $citizenId], ['submittedAt' => 'DESC'])
            ->willReturn($apps);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ProcedureApplication::class)
            ->willReturn($repo);

        $result = $this->service->getApplicationsByCitizen($citizenId);
        $this->assertCount(2, $result);
    }

    public function testGetApplicationsByProcedure(): void
    {
        $procedureId = 'proc_123';
        $apps = [
            new ProcedureApplication('id1', 'citizen1', $procedureId, ['doc']),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['procedureId' => $procedureId], ['submittedAt' => 'DESC'])
            ->willReturn($apps);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getApplicationsByProcedure($procedureId);
        $this->assertCount(1, $result);
    }

    public function testGetApplicationsByStatus(): void
    {
        $status = 'APPROVED';
        $apps = [
            new ProcedureApplication('id1', 'citizen', 'proc', ['doc'], $status),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status], ['submittedAt' => 'DESC'])
            ->willReturn($apps);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getApplicationsByStatus($status);
        $this->assertCount(1, $result);
    }

    public function testGetApplicationsByWard(): void
    {
        $wardId = 'ward_01';
        $apps = [
            new ProcedureApplication('id1', 'citizen', 'proc', ['doc'], 'PENDING', $wardId),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['wardId' => $wardId], ['submittedAt' => 'DESC'])
            ->willReturn($apps);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getApplicationsByWard($wardId);
        $this->assertCount(1, $result);
    }

    public function testGetPendingApplications(): void
    {
        $apps = [
            new ProcedureApplication('id1', 'citizen', 'proc', ['doc'], 'PENDING'),
            new ProcedureApplication('id2', 'citizen', 'proc', ['doc'], 'PENDING'),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('a.status = :status')
            ->willReturnSelf();
        $qb->expects($this->never())
            ->method('andWhere');
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('status', 'PENDING')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($apps);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getPendingApplications();
        $this->assertCount(2, $result);
    }

    public function testGetPendingApplicationsWithWard(): void
    {
        $wardId = 'ward_01';
        $apps = [new ProcedureApplication('id1', 'citizen', 'proc', ['doc'], 'PENDING', $wardId)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('a.status = :status')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('a.wardId = :wardId')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($apps);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getPendingApplications($wardId);
        $this->assertCount(1, $result);
    }

    public function testReviewApplicationApprove(): void
    {
        $appId = 'app_123';
        $application = new ProcedureApplication($appId, 'citizen', 'proc', ['doc']);
        $application->setStatus('PENDING');
        $officerId = 'officer_456';
        $notes = 'All documents valid';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($appId)
            ->willReturn($application);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Application reviewed', $this->anything());

        $result = $this->service->reviewApplication($appId, 'APPROVED', $officerId, $notes);
        $this->assertSame($application, $result);
        $this->assertSame('APPROVED', $result->getStatus());
        $this->assertSame($officerId, $result->getReviewedBy());
        $this->assertSame($notes, $result->getReviewNotes());
        $this->assertNotNull($result->getReviewedAt());
    }

    public function testReviewApplicationReject(): void
    {
        $appId = 'app_123';
        $application = new ProcedureApplication($appId, 'citizen', 'proc', ['doc']);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn($application);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->reviewApplication($appId, 'REJECTED');
        $this->assertSame('REJECTED', $result->getStatus());
    }

    public function testReviewApplicationNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->reviewApplication('nonexistent', 'APPROVED');
        $this->assertNull($result);
    }

    public function testAssignToOfficer(): void
    {
        $appId = 'app_123';
        $application = new ProcedureApplication($appId, 'citizen', 'proc', ['doc']);
        $officerId = 'officer_456';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($appId)
            ->willReturn($application);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->assignToOfficer($appId, $officerId);
        $this->assertSame($application, $result);
        $this->assertSame($officerId, $application->getAssignedOfficerId());
    }

    public function testGetOfficerApplications(): void
    {
        $officerId = 'officer_456';
        $apps = [
            new ProcedureApplication('id1', 'citizen', 'proc', ['doc']),
            new ProcedureApplication('id2', 'citizen', 'proc', ['doc']),
        ];
        foreach ($apps as $a) {
            $a->setAssignedOfficerId($officerId);
        }

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['assignedOfficerId' => $officerId], ['submittedAt' => 'DESC'])
            ->willReturn($apps);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getOfficerApplications($officerId);
        $this->assertCount(2, $result);
    }

    public function testGetApplicationsByDateRange(): void
    {
        $start = new \DateTime('-1 week');
        $end = new \DateTime();
        $apps = [new ProcedureApplication('id1', 'citizen', 'proc', ['doc'])];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('a.submittedAt BETWEEN :start AND :end')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($apps);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getApplicationsByDateRange($start, $end);
        $this->assertCount(1, $result);
    }

    public function testGetTotalCount(): void
    {
        $count = 10;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(a.id)')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $count);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getTotalCount();
        $this->assertSame($count, $result);
    }

    public function testGetApprovalRate(): void
    {
        $approved = 8;
        $total = 10;
        $rate = 80.0;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('AVG(CASE WHEN a.status = \'APPROVED\' THEN 1 ELSE 0 END) * 100')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $rate);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getApprovalRate();
        $this->assertSame($rate, $result);
    }

    public function testGetAverageProcessingTime(): void
    {
        $avgSeconds = 86400; // 1 day

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('AVG(TIMESTAMPDIFF(SECOND, a.submittedAt, a.reviewedAt))')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $avgSeconds);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getAverageProcessingTime();
        $this->assertSame($avgSeconds, $result);
    }

    public function testGetHighValueApplications(): void
    {
        $minAmount = 100000;
        $apps = [
            new ProcedureApplication('id1', 'citizen', 'proc', ['doc'], 'PENDING', null, null, ['fee' => 500000]),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('a.metadata->\'$.fee\' >= :minAmount')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('minAmount', $minAmount)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($apps);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getHighValueApplications($minAmount);
        $this->assertCount(1, $result);
    }

    public function testGetApplicationsByDocument(): void
    {
        $docName = 'passport';
        $apps = [
            new ProcedureApplication('id1', 'citizen', 'proc', ['passport', 'id']),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('JSON_CONTAINS(a.documents, :docName)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('docName', sprintf('"%s"', $docName))
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($apps);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getApplicationsByDocument($docName);
        $this->assertCount(1, $result);
    }

    public function testUpdateMetadata(): void
    {
        $appId = 'app_123';
        $application = new ProcedureApplication($appId, 'citizen', 'proc', ['doc']);
        $newMetadata = ['priority' => 'urgent'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($appId)
            ->willReturn($application);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateMetadata($appId, $newMetadata);
        $this->assertSame($application, $result);
        $this->assertSame($newMetadata, $result->getMetadata());
    }

    public function testAddComment(): void
    {
        $appId = 'app_123';
        $application = new ProcedureApplication($appId, 'citizen', 'proc', ['doc']);
        $comment = ['author' => 'officer', 'text' => 'Needs more info'];
        $timestamp = new \DateTime();

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($appId)
            ->willReturn($application);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->addComment($appId, $comment, $timestamp);
        $this->assertSame($application, $result);
        $this->assertContains($comment, $result->getComments());
    }

    public function testGetComments(): void
    {
        $appId = 'app_123';
        $comments = [
            ['author' => 'officer', 'text' => 'Note 1'],
            ['author' => 'citizen', 'text' => 'Note 2'],
        ];
        $application = new ProcedureApplication($appId, 'citizen', 'proc', ['doc']);
        $ref = new \ReflectionProperty(ProcedureApplication::class, 'comments');
        $ref->setAccessible(true);
        $ref->setValue($application, $comments);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($appId)
            ->willReturn($application);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getComments($appId);
        $this->assertSame($comments, $result);
    }

    public function testGetAuditTrail(): void
    {
        $appId = 'app_123';
        $trail = [
            ['action' => 'created', 'by' => 'citizen', 'at' => '2025-01-01'],
            ['action' => 'assigned', 'by' => 'admin', 'at' => '2025-01-02'],
        ];
        $application = new ProcedureApplication($appId, 'citizen', 'proc', ['doc']);
        $ref = new \ReflectionProperty(ProcedureApplication::class, 'auditTrail');
        $ref->setAccessible(true);
        $ref->setValue($application, $trail);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($appId)
            ->willReturn($application);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getAuditTrail($appId);
        $this->assertSame($trail, $result);
    }
}
