<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\WorkflowInstance;
use App\Entity\Common\Workflow;
use App\Service\Common\WorkflowInstanceService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkflowInstanceServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private WorkflowInstanceService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new WorkflowInstanceService($this->em, $this->logger);
    }

    public function testStartWorkflowSuccess(): void
    {
        $workflowId = 'workflow_123';
        $userId = 'user_456';
        $context = ['key' => 'value'];

        $workflow = new Workflow($workflowId, 'Test Workflow', 'test');

        $workflowRepo = $this->createMock(ObjectRepository::class);
        $workflowRepo->expects($this->once())
            ->method('find')
            ->with($workflowId)
            ->willReturn($workflow);

        $instanceRepo = $this->createMock(ObjectRepository::class);
        $instanceRepo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Workflow::class)
            ->willReturn($workflowRepo);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(WorkflowInstance::class)
            ->willReturn($instanceRepo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (WorkflowInstance $i) use ($workflowId, $userId, $context) {
                return $i->getWorkflowId() === $workflowId
                    && $i->getUserId() === $userId
                    && $i->getContext() === $context
                    && $i->getStatus() === 'RUNNING';
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Workflow instance started', $this->anything());

        $result = $this->service->startWorkflow($workflowId, $userId, $context);
        $this->assertInstanceOf(WorkflowInstance::class, $result);
        $this->assertSame($workflowId, $result->getWorkflowId());
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame('RUNNING', $result->getStatus());
    }

    public function testStartWorkflowNotFound(): void
    {
        $workflowId = 'nonexistent';

        $workflowRepo = $this->createMock(ObjectRepository::class);
        $workflowRepo->expects($this->once())
            ->method('find')
            ->with($workflowId)
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Workflow::class)
            ->willReturn($workflowRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Workflow not found');
        $this->service->startWorkflow($workflowId);
    }

    public function testGetInstance(): void
    {
        $id = 'instance_123';
        $instance = new WorkflowInstance($id, 'workflow_123');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(WorkflowInstance::class)
            ->willReturn($repo);

        $result = $this->service->getInstance($id);
        $this->assertSame($instance, $result);
    }

    public function testGetInstancesByWorkflow(): void
    {
        $workflowId = 'workflow_123';
        $instances = [
            new WorkflowInstance('id1', $workflowId),
            new WorkflowInstance('id2', $workflowId),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['workflowId' => $workflowId], ['startedAt' => 'DESC'])
            ->willReturn($instances);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(WorkflowInstance::class)
            ->willReturn($repo);

        $result = $this->service->getInstancesByWorkflow($workflowId);
        $this->assertSame($instances, $result);
    }

    public function testGetUserInstances(): void
    {
        $userId = 'user_123';
        $instances = [
            new WorkflowInstance('id1', 'wf1', $userId),
            new WorkflowInstance('id2', 'wf2', $userId),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId], ['startedAt' => 'DESC'])
            ->willReturn($instances);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(WorkflowInstance::class)
            ->willReturn($repo);

        $result = $this->service->getUserInstances($userId);
        $this->assertCount(2, $result);
    }

    public function testGetInstancesByStatus(): void
    {
        $status = 'RUNNING';
        $instances = [
            new WorkflowInstance('id1', 'wf1', null, $status),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status], ['startedAt' => 'DESC'])
            ->willReturn($instances);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(WorkflowInstance::class)
            ->willReturn($repo);

        $result = $this->service->getInstancesByStatus($status);
        $this->assertCount(1, $result);
    }

    public function testGetRunningInstances(): void
    {
        $instances = [new WorkflowInstance('id', 'wf')];
        // Delegate to getInstancesByStatus
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'RUNNING'], ['startedAt' => 'DESC'])
            ->willReturn($instances);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(WorkflowInstance::class)
            ->willReturn($repo);

        $result = $this->service->getRunningInstances();
        $this->assertSame($instances, $result);
    }

    public function testUpdateState(): void
    {
        $instanceId = 'instance_123';
        $instance = new WorkflowInstance($instanceId, 'workflow_123');
        $state = ['step' => 'approve', 'approved' => true];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($instanceId)
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Workflow state updated', $this->anything());

        $result = $this->service->updateState($instanceId, $state);
        $this->assertSame($instance, $result);
        $this->assertSame($state, $result->getState());
    }

    public function testUpdateStateNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->updateState('nonexistent', []);
        $this->assertNull($result);
    }

    public function testCompleteWorkflow(): void
    {
        $instanceId = 'instance_123';
        $instance = new WorkflowInstance($instanceId, 'wf');
        $instance->setStatus('RUNNING');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($instanceId)
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->completeWorkflow($instanceId);
        $this->assertSame($instance, $result);
        $this->assertSame('COMPLETED', $result->getStatus());
    }

    public function testFailWorkflow(): void
    {
        $instanceId = 'instance_123';
        $instance = new WorkflowInstance($instanceId, 'wf');
        $instance->setStatus('RUNNING');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($instanceId)
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->failWorkflow($instanceId);
        $this->assertSame($instance, $result);
        $this->assertSame('FAILED', $result->getStatus());
    }

    public function testCancelWorkflow(): void
    {
        $instanceId = 'instance_123';
        $instance = new WorkflowInstance($instanceId, 'wf');
        $instance->setStatus('RUNNING');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($instanceId)
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->cancelWorkflow($instanceId);
        $this->assertSame($instance, $result);
        $this->assertSame('CANCELLED', $result->getStatus());
    }

    public function testRecordEvent(): void
    {
        $instanceId = 'instance_123';
        $instance = new WorkflowInstance($instanceId, 'wf');
        $event = ['action' => 'approve'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($instanceId)
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->recordEvent($instanceId, $event);
        $this->assertSame($instance, $result);
        $this->assertContains($event, $instance->getEventLog());
    }

    public function testGetHistory(): void
    {
        $instanceId = 'instance_123';
        $log = [['event' => 'start'], ['event' => 'step1']];
        $instance = new WorkflowInstance($instanceId, 'wf');
        // Use reflection to set private eventLog
        $ref = new \ReflectionProperty(WorkflowInstance::class, 'eventLog');
        $ref->setAccessible(true);
        $ref->setValue($instance, $log);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($instanceId)
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getHistory($instanceId);
        $this->assertSame($log, $result);
    }

    public function testGetCurrentState(): void
    {
        $instanceId = 'instance_123';
        $state = ['step' => 'approve'];
        $instance = new WorkflowInstance($instanceId, 'wf');
        $ref = new \ReflectionProperty(WorkflowInstance::class, 'state');
        $ref->setAccessible(true);
        $ref->setValue($instance, $state);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($instanceId)
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getCurrentState($instanceId);
        $this->assertSame($state, $result);
    }

    public function testGetDuration(): void
    {
        $instanceId = 'instance_123';
        $startedAt = new \DateTime('-1 hour');
        $endedAt = new \DateTime();
        $instance = new WorkflowInstance($instanceId, 'wf');
        $instance->setStartedAt($startedAt);
        $instance->setEndedAt($endedAt);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($instanceId)
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getDuration($instanceId);
        $this->assertInstanceOf(\DateInterval::class, $result);
    }

    public function testIsRunning(): void
    {
        $instance = new WorkflowInstance('id', 'wf');
        $instance->setStatus('RUNNING');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->isRunning('id');
        $this->assertTrue($result);
    }

    public function testIsCompleted(): void
    {
        $instance = new WorkflowInstance('id', 'wf');
        $instance->setStatus('COMPLETED');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn($instance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->isCompleted('id');
        $this->assertTrue($result);
    }

    public function testCountByWorkflow(): void
    {
        $workflowId = 'wf_123';
        $count = 5;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(i.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('i.workflowId = :workflowId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('workflowId', $workflowId)
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
            ->with('i')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(WorkflowInstance::class)
            ->willReturn($repo);

        $result = $this->service->countByWorkflow($workflowId);
        $this->assertSame($count, $result);
    }

    public function testCountByUser(): void
    {
        $userId = 'user_123';
        $count = 3;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(i.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('i.userId = :userId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
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
            ->with('i')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(WorkflowInstance::class)
            ->willReturn($repo);

        $result = $this->service->countByUser($userId);
        $this->assertSame($count, $result);
    }
}
