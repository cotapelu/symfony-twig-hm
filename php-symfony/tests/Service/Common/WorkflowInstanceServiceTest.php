<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\Workflow;
use App\Entity\Common\WorkflowInstance;
use App\Repository\Common\WorkflowInstanceRepository;
use App\Repository\Common\WorkflowRepository;
use App\Service\Common\WorkflowInstanceService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class WorkflowInstanceServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private WorkflowRepository $workflowRepo;
    private WorkflowInstanceRepository $instanceRepo;
    private LoggerInterface $logger;
    private WorkflowInstanceService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->workflowRepo = $this->createMock(WorkflowRepository::class);
        $this->instanceRepo = $this->createMock(WorkflowInstanceRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->willReturnMap([
                [Workflow::class, $this->workflowRepo],
                [WorkflowInstance::class, $this->instanceRepo],
            ]);
    }

    public function testStartWorkflowSuccess(): void
    {
        $workflowId = 'wf-1';
        $userId = 'user-1';
        $context = ['key' => 'value'];

        $workflow = $this->createMock(Workflow::class);
        $this->workflowRepo->expects($this->once())
            ->method('find')
            ->with($workflowId)
            ->willReturn($workflow);

        // Capture persisted instance
        $persisted = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (WorkflowInstance $wi) use (&$persisted, $workflowId, $userId, $context) {
                $persisted = $wi;
                return $persisted !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Workflow instance started', $this->arrayHasKey('instance_id'));

        $this->service = new WorkflowInstanceService($this->em, $this->logger);
        $result = $this->service->startWorkflow($workflowId, $userId, $context);

        $this->assertInstanceOf(WorkflowInstance::class, $result);
        $this->assertSame($workflowId, $result->getWorkflowId());
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($context, $result->getContext());
        $this->assertSame([], $result->getState());
        $this->assertSame('RUNNING', $result->getStatus());
    }

    public function testStartWorkflowNotFound(): void
    {
        $workflowId = 'missing';
        $this->workflowRepo->expects($this->once())
            ->method('find')
            ->with($workflowId)
            ->willReturn(null);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $this->service = new WorkflowInstanceService($this->em, $this->logger);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Workflow not found: ' . $workflowId);
        $this->service->startWorkflow($workflowId);
    }

    public function testGetInstance(): void
    {
        $id = 'instance-id';
        $instance = $this->createMock(WorkflowInstance::class);
        $this->instanceRepo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($instance);

        $this->service = new WorkflowInstanceService($this->em, $this->logger);
        $result = $this->service->getInstance($id);
        $this->assertSame($instance, $result);
    }

    public function testGetInstancesByWorkflow(): void
    {
        $workflowId = 'wf-2';
        $instances = [$this->createMock(WorkflowInstance::class), $this->createMock(WorkflowInstance::class)];
        $this->instanceRepo->expects($this->once())
            ->method('findBy')
            ->with(['workflowId' => $workflowId], ['startedAt' => 'DESC'])
            ->willReturn($instances);

        $this->service = new WorkflowInstanceService($this->em, $this->logger);
        $result = $this->service->getInstancesByWorkflow($workflowId);
        $this->assertSame($instances, $result);
    }

    public function testGetUserInstances(): void
    {
        $userId = 'user-3';
        $instances = [$this->createMock(WorkflowInstance::class)];
        $this->instanceRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId], ['startedAt' => 'DESC'])
            ->willReturn($instances);

        $this->service = new WorkflowInstanceService($this->em, $this->logger);
        $result = $this->service->getUserInstances($userId);
        $this->assertSame($instances, $result);
    }

}
