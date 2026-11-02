<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\Workflow;
use App\Service\Common\WorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkflowServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private WorkflowService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new WorkflowService($this->em, $this->logger);
    }

    public function testCreateSuccess(): void
    {
        $name = 'OrderProcess';
        $type = 'order';
        $definition = ['steps' => ['init', 'approve', 'ship']];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Workflow::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist');
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Workflow created', $this->anything());

        $result = $this->service->create($name, $type, $definition);
        $this->assertInstanceOf(Workflow::class, $result);
        $this->assertSame($name, $result->getName());
        $this->assertSame($type, $result->getType());
        $this->assertSame($definition, $result->getDefinition());
    }

    public function testCreateFailsValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create('', 'type', ['step']);
    }

    public function testGetSuccess(): void
    {
        $id = '123e4567-e89b-12d3-a456-426614174000';
        $workflow = new Workflow($id, 'Name', 'type');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($workflow);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Workflow::class)
            ->willReturn($repo);

        $result = $this->service->get($id);
        $this->assertSame($workflow, $result);
    }

    public function testGetByTypeSuccess(): void
    {
        $type = 'order';
        $status = 'ACTIVE';
        $workflow1 = new Workflow('id1', 'Name1', $type);
        $workflow1->setStatus($status);
        $workflow2 = new Workflow('id2', 'Name2', $type);
        $workflow2->setStatus($status);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type, 'status' => $status])
            ->willReturn([$workflow1, $workflow2]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Workflow::class)
            ->willReturn($repo);

        $result = $this->service->getByType($type);
        $this->assertCount(2, $result);
    }

    public function testUpdateDefinitionSuccess(): void
    {
        $id = 'id123';
        $workflow = new Workflow($id, 'Name', 'type');
        $newDefinition = ['steps' => ['new']];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($workflow);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateDefinition($id, $newDefinition);
        $this->assertSame($workflow, $result);
        $this->assertSame($newDefinition, $workflow->getDefinition());
    }

    public function testChangeStatusSuccess(): void
    {
        $id = 'id123';
        $workflow = new Workflow($id, 'Name', 'type');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($workflow);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->changeStatus($id, 'ARCHIVED');
        $this->assertSame($workflow, $result);
        $this->assertSame('ARCHIVED', $workflow->getStatus());
    }

    public function testChangeStatusInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->changeStatus('id', 'BAD');
    }

    public function testDeleteSuccess(): void
    {
        $id = 'id123';
        $workflow = new Workflow($id, 'Name', 'type');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($workflow);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($workflow);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->delete($id);
        $this->assertTrue($result);
    }
}
