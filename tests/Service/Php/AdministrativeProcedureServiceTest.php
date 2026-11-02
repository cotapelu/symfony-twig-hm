<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\AdministrativeProcedure;
use App\Service\Php\AdministrativeProcedureService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AdministrativeProcedureServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private AdministrativeProcedureService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new AdministrativeProcedureService($this->em, $this->logger);
    }

    public function testCreateProcedure(): void
    {
        $name = 'Building Permit';
        $description = 'Apply for building permit';
        $category = 'Construction';
        $processingDays = 30;
        $requiredDocuments = ['ID', 'Land deed'];
        $fees = ['base' => 100000, 'expedited' => 200000];
        $metadata = ['department' => 'Urban Planning'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(AdministrativeProcedure::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AdministrativeProcedure $p) use (
                $name, $description, $category, $processingDays, $requiredDocuments, $fees, $metadata
            ) {
                return $p->getName() === $name
                    && $p->getDescription() === $description
                    && $p->getCategory() === $category
                    && $p->getProcessingDays() === $processingDays
                    && $p->getRequiredDocuments() === $requiredDocuments
                    && $p->getFees() === $fees
                    && $p->getMetadata() === $metadata
                    && $p->getStatus() === 'ACTIVE';
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Administrative procedure created', $this->anything());

        $result = $this->service->createProcedure($name, $description, $category, $processingDays, $requiredDocuments, $fees, $metadata);
        $this->assertInstanceOf(AdministrativeProcedure::class, $result);
        $this->assertSame($name, $result->getName());
        $this->assertSame($category, $result->getCategory());
        $this->assertSame('ACTIVE', $result->getStatus());
    }

    public function testCreateProcedureMinimal(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createProcedure('Name', 'Desc', 'Cat', 15);
        $this->assertNull($result->getRequiredDocuments());
        $this->assertNull($result->getFees());
        $this->assertNull($result->getMetadata());
    }

    public function testGetProcedure(): void
    {
        $id = 'proc_123';
        $procedure = new AdministrativeProcedure($id, 'Name', 'Desc', 'Cat', 10);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($procedure);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(AdministrativeProcedure::class)
            ->willReturn($repo);

        $result = $this->service->getProcedure($id);
        $this->assertSame($procedure, $result);
    }

    public function testGetActiveProcedures(): void
    {
        $procedures = [
            new AdministrativeProcedure('id1', 'Name1', 'D', 'Cat1', 10),
            new AdministrativeProcedure('id2', 'Name2', 'D', 'Cat2', 20),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($procedures);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getActiveProcedures();
        $this->assertCount(2, $result);
    }

    public function testGetProceduresByCategory(): void
    {
        $category = 'Construction';
        $procedures = [
            new AdministrativeProcedure('id1', 'Name1', 'D', $category, 10),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category, 'status' => 'ACTIVE'])
            ->willReturn($procedures);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProceduresByCategory($category);
        $this->assertCount(1, $result);
    }

    public function testSearchByName(): void
    {
        $query = 'building';
        $procedures = [new AdministrativeProcedure('id', 'Building Permit', 'D', 'Cat', 10)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('p.name LIKE :query')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $queryObj = $this->createMock(\Doctrine\ORM\Query::class);
        $queryObj->expects($this->once())
            ->method('getResult')
            ->willReturn($procedures);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($queryObj);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->searchByName($query);
        $this->assertCount(1, $result);
    }

    public function testUpdateProcedure(): void
    {
        $procedureId = 'proc_123';
        $procedure = new AdministrativeProcedure($procedureId, 'Old Name', 'Old Desc', 'Old Cat', 10);
        $newName = 'New Name';
        $newDays = 20;

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($procedureId)
            ->willReturn($procedure);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateProcedure($procedureId, $newName, null, null, $newDays);
        $this->assertSame($procedure, $result);
        $this->assertSame($newName, $result->getName());
        $this->assertSame($newDays, $result->getProcessingDays());
    }

    public function testUpdateProcedureNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->updateProcedure('nonexistent', 'Name');
        $this->assertNull($result);
    }

    public function testArchiveProcedure(): void
    {
        $procedureId = 'proc_123';
        $procedure = new AdministrativeProcedure($procedureId, 'Name', 'Desc', 'Cat', 10);
        $procedure->setStatus('ACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($procedureId)
            ->willReturn($procedure);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->archiveProcedure($procedureId);
        $this->assertSame($procedure, $result);
        $this->assertSame('ARCHIVED', $result->getStatus());
    }

    public function testArchiveProcedureNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->archiveProcedure('nonexistent');
        $this->assertNull($result);
    }

    public function testDeactivateProcedure(): void
    {
        $procedureId = 'proc_123';
        $procedure = new AdministrativeProcedure($procedureId, 'Name', 'Desc', 'Cat', 10);
        $procedure->setStatus('ACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($procedureId)
            ->willReturn($procedure);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deactivateProcedure($procedureId);
        $this->assertSame($procedure, $result);
        $this->assertSame('INACTIVE', $result->getStatus());
    }

    public function testGetProceduresByFeeRange(): void
    {
        $minFee = 50000;
        $maxFee = 200000;
        $procedures = [
            new AdministrativeProcedure('id1', 'Name1', 'D', 'Cat1', 10, null, ['base' => 100000]),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('p.status = :status')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.fees.base >= :minFee')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.fees.base <= :maxFee')
            ->willReturnSelf();
        $qb->expects($this->exactly(4))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($procedures);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProceduresByFeeRange($minFee, $maxFee);
        $this->assertCount(1, $result);
    }

    public function testCountActiveByCategory(): void
    {
        $category = 'Construction';
        $count = 5;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(p.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('p.category = :category')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
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
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(AdministrativeProcedure::class)
            ->willReturn($repo);

        $result = $this->service->countActiveByCategory($category);
        $this->assertSame($count, $result);
    }

    public function testGetProceduresWithShortProcessing(): void
    {
        $maxDays = 15;
        $procedures = [
            new AdministrativeProcedure('id1', 'Name1', 'D', 'Cat1', 10),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($this->createQueryBuilderWithDays($maxDays, true, $procedures));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProceduresWithShortProcessing($maxDays);
        $this->assertCount(1, $result);
    }

    public function testGetProceduresWithLongProcessing(): void
    {
        $minDays = 30;
        $procedures = [
            new AdministrativeProcedure('id1', 'Name1', 'D', 'Cat1', 60),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($this->createQueryBuilderWithDays($minDays, false, $procedures));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProceduresWithLongProcessing($minDays);
        $this->assertCount(1, $result);
    }

    private function createQueryBuilderWithDays(int $threshold, bool $lessThan, array $result): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('p.status = :status')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with($lessThan ? 'p.processingDays <= :threshold' : 'p.processingDays >= :threshold')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        return $qb;
    }
}
