<?php

namespace App\Tests\Unit\Government\Application\Service;

use App\Government\Application\Service\AdministrativeProcedureService;
use App\Government\Contracts\DTO\CreateProcedureRequest;
use App\Government\Contracts\DTO\CreateProcedureApplicationRequest;
use App\Government\Contracts\DTO\ProcedureResponse;
use App\Government\Contracts\DTO\ProcedureApplicationResponse;
use App\Government\Domain\Repository\AdministrativeProcedureRepositoryInterface;
use App\Government\Domain\Repository\ProcedureApplicationRepositoryInterface;
use App\Government\Domain\Entity\AdministrativeProcedure;
use App\Government\Domain\Entity\ProcedureApplication;
use PHPUnit\Framework\TestCase;

class AdministrativeProcedureServiceTest extends TestCase
{
    private AdministrativeProcedureRepositoryInterface $procedureRepo;
    private ProcedureApplicationRepositoryInterface $applicationRepo;
    private AdministrativeProcedureService $service;

    protected function setUp(): void
    {
        $this->procedureRepo = $this->createMock(AdministrativeProcedureRepositoryInterface::class);
        $this->applicationRepo = $this->createMock(ProcedureApplicationRepositoryInterface::class);
        $this->service = new AdministrativeProcedureService($this->procedureRepo, $this->applicationRepo);
    }

    public function testCreateProcedure(): void
    {
        $request = new CreateProcedureRequest(
            name: 'Proc A',
            code: 'PA-1',
            category: 'license',
            description: 'Desc',
            requiredDocuments: ['doc1'],
            processingTimeDays: 5,
            fee: 100.0
        );

        $this->procedureRepo->expects($this->once())->method('save')
            ->with($this->callback(function (AdministrativeProcedure $proc) use ($request) {
                return $proc->getName() === $request->name
                    && $proc->getCode() === $request->code
                    && $proc->getCategory() === $request->category
                    && $proc->getDescription() === $request->description
                    && $proc->getRequiredDocuments() === $request->requiredDocuments
                    && $proc->getProcessingTimeDays() === $request->processingTimeDays
                    && $proc->getFee() === $request->fee;
            }));

        $response = $this->service->createProcedure($request);

        $this->assertInstanceOf(ProcedureResponse::class, $response);
        $this->assertSame($request->name, $response->name);
        $this->assertSame($request->code, $response->code);
    }

    public function testGetProcedureByIdNotFound(): void
    {
        $this->procedureRepo->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $this->assertNull($this->service->getProcedureById('missing'));
    }

    public function testGetProcedureByIdFound(): void
    {
        $procedure = new AdministrativeProcedure('Proc B', 'PB-2', 'permit', 'Desc', [], 3, 50.0);
        $this->procedureRepo->expects($this->once())->method('findById')->with($procedure->getId())->willReturn($procedure);
        $response = $this->service->getProcedureById($procedure->getId());
        $this->assertInstanceOf(ProcedureResponse::class, $response);
        $this->assertSame($procedure->getId(), $response->id);
    }

    public function testListProcedures(): void
    {
        $proc1 = new AdministrativeProcedure('P1','C1','cat','d',[],1,0);
        $proc2 = new AdministrativeProcedure('P2','C2','cat','d',[],2,0);
        $this->procedureRepo->expects($this->once())->method('findAll')->with(['status'=>'active'], 1, 20)->willReturn([$proc1,$proc2]);
        $result = $this->service->listProcedures(['status'=>'active'],1,20);
        $this->assertCount(2, $result);
        $this->assertSame($proc1->getId(), $result[0]->id);
    }

    public function testActivateProcedureNotFound(): void
    {
        $this->procedureRepo->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $this->assertFalse($this->service->activateProcedure('missing'));
    }

    public function testActivateProcedure(): void
    {
        $procedure = new AdministrativeProcedure('P','C','cat','desc',[],1,0);
        // deactivate first via reflection
        $ref = new \ReflectionMethod($procedure, 'deactivate');
        $ref->setAccessible(true);
        $ref->invoke($procedure);
        $this->assertSame(AdministrativeProcedure::STATUS_INACTIVE, $procedure->getStatus());

        $this->procedureRepo->expects($this->once())->method('findById')->with($procedure->getId())->willReturn($procedure);
        $this->procedureRepo->expects($this->once())->method('save')->with($this->callback(fn($p)=>$p->getStatus()===AdministrativeProcedure::STATUS_ACTIVE));

        $result = $this->service->activateProcedure($procedure->getId());
        $this->assertTrue($result);
        $this->assertSame(AdministrativeProcedure::STATUS_ACTIVE, $procedure->getStatus());
    }

    public function testDeactivateProcedureNotFound(): void
    {
        $this->procedureRepo->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $this->assertFalse($this->service->deactivateProcedure('missing'));
    }

    public function testDeactivateProcedure(): void
    {
        $procedure = new AdministrativeProcedure('P','C','cat','desc',[],1,0); // active default
        $this->procedureRepo->expects($this->once())->method('findById')->with($procedure->getId())->willReturn($procedure);
        $this->procedureRepo->expects($this->once())->method('save')->with($this->callback(fn($p)=>$p->getStatus()===AdministrativeProcedure::STATUS_INACTIVE));

        $result = $this->service->deactivateProcedure($procedure->getId());
        $this->assertTrue($result);
        $this->assertSame(AdministrativeProcedure::STATUS_INACTIVE, $procedure->getStatus());
    }

    public function testSubmitApplication(): void
    {
        $request = new CreateProcedureApplicationRequest(
            citizenId: 'user-1',
            procedureId: 'proc-1',
            documents: ['doc1','doc2'],
            submittedBy: 'user-1',
            notes: 'Please'
        );
        $this->applicationRepo->expects($this->once())->method('save')
            ->with($this->callback(function (ProcedureApplication $app) use ($request) {
                return $app->getProcedureId() === $request->procedureId
                    && $app->getCitizenId() === $request->citizenId
                    && $app->getDocuments() === $request->documents
                    && $app->getNotes() === $request->notes
                    && $app->getSubmittedBy() === $request->submittedBy;
            }))
            ->willReturnArgument(0);

        $response = $this->service->submitApplication($request);
        $this->assertInstanceOf(ProcedureApplicationResponse::class, $response);
    }

    public function testGetApplicationByIdNotFound(): void
    {
        $this->applicationRepo->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $this->assertNull($this->service->getApplicationById('missing'));
    }

    public function testGetApplicationByIdFound(): void
    {
        $app = new ProcedureApplication('user-1','proc-1',[],'user-1');
        $this->applicationRepo->expects($this->once())->method('findById')->with($app->getId())->willReturn($app);
        $response = $this->service->getApplicationById($app->getId());
        $this->assertInstanceOf(ProcedureApplicationResponse::class, $response);
        $this->assertSame($app->getId(), $response->id);
    }

    public function testListApplications(): void
    {
        $app1 = new ProcedureApplication('u1','p1',[],'u1');
        $app2 = new ProcedureApplication('u2','p2',[],'u2');
        $this->applicationRepo->expects($this->once())->method('findAll')->with([],1,20)->willReturn([$app1,$app2]);
        $result = $this->service->listApplications([],1,20);
        $this->assertCount(2, $result);
    }

    public function testStartProcessingNotFound(): void
    {
        $this->applicationRepo->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $this->assertFalse($this->service->startProcessing('missing','agent-1'));
    }

    public function testStartProcessing(): void
    {
        $app = new ProcedureApplication('user-1','proc-1',[],'user-1');
        $this->applicationRepo->expects($this->once())->method('findById')->with($app->getId())->willReturn($app);
        $this->applicationRepo->expects($this->once())->method('save')->with($this->callback(fn($a)=>$a->getStatus()===ProcedureApplication::STATUS_PROCESSING && $a->getProcessedBy()==='agent-1'));
        $result = $this->service->startProcessing($app->getId(),'agent-1');
        $this->assertTrue($result);
        $this->assertSame(ProcedureApplication::STATUS_PROCESSING, $app->getStatus());
    }

    public function testApproveNotFound(): void
    {
        $this->applicationRepo->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $this->assertFalse($this->service->approve('missing','Result'));
    }

    public function testApprove(): void
    {
        $app = new ProcedureApplication('user-1','proc-1',[],'user-1');
        $resultText = 'Approved for quality';
        $this->applicationRepo->expects($this->once())->method('findById')->with($app->getId())->willReturn($app);
        $this->applicationRepo->expects($this->once())->method('save')->with($this->callback(fn($a)=>$a->getStatus()===ProcedureApplication::STATUS_APPROVED && $a->getResult()===$resultText));
        $result = $this->service->approve($app->getId(),$resultText);
        $this->assertTrue($result);
        $this->assertSame(ProcedureApplication::STATUS_APPROVED, $app->getStatus());
    }

    public function testRejectNotFound(): void
    {
        $this->applicationRepo->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $this->assertFalse($this->service->reject('missing','Reason'));
    }

    public function testReject(): void
    {
        $app = new ProcedureApplication('user-1','proc-1',[],'user-1');
        $reason = 'Missing docs';
        $this->applicationRepo->expects($this->once())->method('findById')->with($app->getId())->willReturn($app);
        $this->applicationRepo->expects($this->once())->method('save')->with($this->callback(fn($a)=>$a->getStatus()===ProcedureApplication::STATUS_REJECTED && $a->getResult()===$reason));
        $result = $this->service->reject($app->getId(),$reason);
        $this->assertTrue($result);
        $this->assertSame(ProcedureApplication::STATUS_REJECTED, $app->getStatus());
    }

    public function testCompleteNotFound(): void
    {
        $this->applicationRepo->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $this->assertFalse($this->service->complete('missing'));
    }

    public function testComplete(): void
    {
        $app = new ProcedureApplication('user-1','proc-1',[],'user-1');
        $this->applicationRepo->expects($this->once())->method('findById')->with($app->getId())->willReturn($app);
        $this->applicationRepo->expects($this->once())->method('save')->with($this->callback(fn($a)=>$a->getStatus()===ProcedureApplication::STATUS_COMPLETED && $a->getCompletedAt()!==null));
        $result = $this->service->complete($app->getId());
        $this->assertTrue($result);
        $this->assertSame(ProcedureApplication::STATUS_COMPLETED, $app->getStatus());
    }

    public function testGetStatistics(): void
    {
        $totalProcedures = 10;
        $totalApplications = 100;
        $pending = 20;
        $processing = 30;
        $approved = 40;
        $rejected = 10;

        $this->procedureRepo->expects($this->once())
            ->method('count')
            ->with(['status' => 'active'])
            ->willReturn($totalProcedures);

        $appMap = [
            [[], $totalApplications],
            [['status' => ProcedureApplication::STATUS_SUBMITTED], $pending],
            [['status' => ProcedureApplication::STATUS_PROCESSING], $processing],
            [['status' => ProcedureApplication::STATUS_APPROVED], $approved],
            [['status' => ProcedureApplication::STATUS_REJECTED], $rejected],
        ];
        $this->applicationRepo->method('count')
            ->willReturnMap($appMap);

        $result = $this->service->getStatistics();

        $this->assertSame([
            'totalProcedures' => $totalProcedures,
            'totalApplications' => $totalApplications,
            'pending' => $pending,
            'processing' => $processing,
            'approved' => $approved,
            'rejected' => $rejected,
            'completionRate' => round(($approved / $totalApplications) * 100, 2),
        ], $result);
    }

    public function testGetStatisticsZeroApplications(): void
    {
        $totalProcedures = 5;
        $totalApplications = 0;
        $pending = $processing = $approved = $rejected = 0;

        $this->procedureRepo->expects($this->once())
            ->method('count')
            ->with(['status' => 'active'])
            ->willReturn($totalProcedures);

        $appMap = [
            [[], $totalApplications],
            [['status' => ProcedureApplication::STATUS_SUBMITTED], $pending],
            [['status' => ProcedureApplication::STATUS_PROCESSING], $processing],
            [['status' => ProcedureApplication::STATUS_APPROVED], $approved],
            [['status' => ProcedureApplication::STATUS_REJECTED], $rejected],
        ];
        $this->applicationRepo->method('count')
            ->willReturnMap($appMap);

        $result = $this->service->getStatistics();

        $this->assertSame([
            'totalProcedures' => $totalProcedures,
            'totalApplications' => 0,
            'pending' => 0,
            'processing' => 0,
            'approved' => 0,
            'rejected' => 0,
            'completionRate' => 0,
        ], $result);
    }
}
