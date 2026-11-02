<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\Workflow;
use App\Repository\Common\WorkflowRepository;
use App\Service\Common\WorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class WorkflowServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private WorkflowRepository $workflowRepo;
    private LoggerInterface $logger;
    private WorkflowService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->workflowRepo = $this->createMock(WorkflowRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->willReturnMap([
                [Workflow::class, $this->workflowRepo],
            ]);
    }

    public function testCreateWorkflow(): void
    {
        $name = 'Order Workflow';
        $type = 'order';
        $definition = ['steps' => ['start', 'end']];
        $status = 'ACTIVE';
        $version = '1.0';

        // Capture persisted workflow
        $persisted = null;
        $this->workflowRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Workflow $w) use (&$persisted, $name, $type, $definition, $status, $version) {
                $persisted = $w;
                return $persisted !== null
                    && $persisted->getName() === $name
                    && $persisted->getType() === $type
                    && $persisted->getDefinition() === $definition
                    && $persisted->getStatus() === $status
                    && $persisted->getVersion() === $version;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Workflow created', $this->arrayHasKey('id'));

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->create($name, $type, $definition, $status, $version);

        $this->assertInstanceOf(Workflow::class, $result);
        $this->assertSame($name, $result->getName());
        $this->assertNotNull($result->getId());
    }

    public function testCreateWorkflowMinimal(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())->method('info');

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->create('Simple', 'simple', ['steps' => []]);
        $this->assertInstanceOf(Workflow::class, $result);
    }

    public function testCreateWorkflowInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Workflow name is required');

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->service->create('', 'type', []);
    }

    public function testCreateWorkflowInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Workflow type is required');

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->service->create('Name', '', []);
    }

    public function testCreateWorkflowInvalidDefinition(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Workflow definition cannot be empty');

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->service->create('Name', 'type', []);
    }

    public function testCreateWorkflowPersistenceException(): void
    {
        $this->workflowRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())->method('persist');
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to create workflow', $this->arrayHasKey('error'));

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to create workflow');
        $this->service->create('Name', 'type', ['x' => 1]);
    }

    public function testGetWorkflow(): void
    {
        $id = 'workflow-1';
        $workflow = $this->createMock(Workflow::class);
        $this->workflowRepo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($workflow);

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->get($id);
        $this->assertSame($workflow, $result);
    }

    public function testGetByType(): void
    {
        $type = 'order';
        $status = 'ACTIVE';
        $workflows = [$this->createMock(Workflow::class)];
        $this->workflowRepo->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type, 'status' => $status])
            ->willReturn($workflows);

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->getByType($type, $status);
        $this->assertSame($workflows, $result);
    }

    public function testGetByTypeDefaultStatus(): void
    {
        $type = 'invoice';
        $this->workflowRepo->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type, 'status' => 'ACTIVE'])
            ->willReturn([]);

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->getByType($type);
        $this->assertSame([], $result);
    }

    public function testGetActive(): void
    {
        $workflows = [$this->createMock(Workflow::class), $this->createMock(Workflow::class)];
        $this->workflowRepo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($workflows);

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->getActive();
        $this->assertSame($workflows, $result);
    }

    public function testUpdateDefinition(): void
    {
        $id = 'wf-1';
        $newDefinition = ['new' => 'def'];
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())->method('setDefinition')->with($newDefinition);

        $this->workflowRepo->expects($this->once())->method('find')->with($id)->willReturn($workflow);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Workflow definition updated', ['id' => $id]);

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->updateDefinition($id, $newDefinition);
        $this->assertSame($workflow, $result);
    }

    public function testUpdateDefinitionEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Workflow definition cannot be empty');

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->service->updateDefinition('id', []);
    }

    public function testUpdateDefinitionNotFound(): void
    {
        $this->workflowRepo->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Workflow not found: missing');
        $this->service->updateDefinition('missing', ['x' => 1]);
    }

    public function testUpdateDefinitionPersistenceException(): void
    {
        $id = 'wf-2';
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())->method('setDefinition');
        $this->workflowRepo->expects($this->once())->method('find')->with($id)->willReturn($workflow);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to update workflow definition', $this->arrayHasKey('id'));

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to update workflow definition');
        $this->service->updateDefinition($id, ['x' => 1]);
    }

    public function testChangeStatus(): void
    {
        $id = 'wf-3';
        $newStatus = 'INACTIVE';
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())->method('setStatus')->with($newStatus);

        $this->workflowRepo->expects($this->once())->method('find')->with($id)->willReturn($workflow);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Workflow status changed', $this->arrayHasKey('status'));

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->changeStatus($id, $newStatus);
        $this->assertSame($workflow, $result);
    }

    public function testChangeStatusInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status. Allowed: ACTIVE, INACTIVE, DRAFT, ARCHIVED');

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->service->changeStatus('id', 'INVALID');
    }

    public function testChangeStatusNotFound(): void
    {
        $this->workflowRepo->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Workflow not found: missing');
        $this->service->changeStatus('missing', 'ACTIVE');
    }

    public function testChangeStatusPersistenceException(): void
    {
        $id = 'wf-4';
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())->method('setStatus')->with('ARCHIVED');
        $this->workflowRepo->expects($this->once())->method('find')->with($id)->willReturn($workflow);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to change workflow status', $this->arrayHasKey('id'));

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to change workflow status');
        $this->service->changeStatus($id, 'ARCHIVED');
    }

    public function testSetVersion(): void
    {
        $id = 'wf-5';
        $version = '2.0';
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())->method('setVersion')->with($version);

        $this->workflowRepo->expects($this->once())->method('find')->with($id)->willReturn($workflow);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Workflow version set', ['id' => $id, 'version' => $version]);

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->setVersion($id, $version);
        $this->assertSame($workflow, $result);
    }

    public function testSetVersionNotFound(): void
    {
        $this->workflowRepo->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Workflow not found: missing');
        $this->service->setVersion('missing', '1.0');
    }

    public function testSetVersionPersistenceException(): void
    {
        $id = 'wf-6';
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())->method('setVersion')->with('3.0');
        $this->workflowRepo->expects($this->once())->method('find')->with($id)->willReturn($workflow);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to set workflow version', $this->arrayHasKey('id'));

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to set workflow version');
        $this->service->setVersion($id, '3.0');
    }

    public function testDeleteWorkflow(): void
    {
        $id = 'wf-7';
        $workflow = $this->createMock(Workflow::class);

        $this->workflowRepo->expects($this->once())->method('find')->with($id)->willReturn($workflow);
        $this->em->expects($this->once())->method('remove')->with($workflow);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Workflow deleted', ['id' => $id]);

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->delete($id);
        $this->assertTrue($result);
    }

    public function testDeleteWorkflowNotFound(): void
    {
        $this->workflowRepo->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->em->expects($this->never())->method('remove');

        $this->service = new WorkflowService($this->em, $this->logger);
        $result = $this->service->delete('missing');
        $this->assertFalse($result);
    }

    public function testDeleteWorkflowPersistenceException(): void
    {
        $id = 'wf-8';
        $workflow = $this->createMock(Workflow::class);
        $this->workflowRepo->expects($this->once())->method('find')->with($id)->willReturn($workflow);
        $this->em->expects($this->once())->method('remove')->with($workflow);
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to delete workflow', $this->arrayHasKey('id'));

        $this->service = new WorkflowService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to delete workflow');
        $this->service->delete($id);
    }
}
