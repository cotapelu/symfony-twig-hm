<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\Workflow;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WorkflowTest extends TestCase
{
    private function createWorkflow(
        string $id = 'wf-id',
        string $name = 'Workflow',
        string $type = 'default'
    ): Workflow {
        return new Workflow($id, $name, $type);
    }

    public function testConstructor(): void
    {
        $wf = $this->createWorkflow();
        $this->assertSame('wf-id', $wf->getId());
        $this->assertSame('Workflow', $wf->getName());
        $this->assertSame('default', $wf->getType());
        $this->assertNull($wf->getDefinition());
        $this->assertSame('ACTIVE', $wf->getStatus());
        $this->assertNull($wf->getVersion());
        $this->assertInstanceOf(\DateTime::class, $wf->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $wf->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $wf = $this->createWorkflow();
        $originalUpdatedAt = $wf->getUpdatedAt();
        usleep(1000);
        $wf->setName('New Name');
        $this->assertSame('New Name', $wf->getName());
        $this->assertNotEquals($originalUpdatedAt, $wf->getUpdatedAt());
    }

    public function testSetType(): void
    {
        $wf = $this->createWorkflow();
        $originalUpdatedAt = $wf->getUpdatedAt();
        usleep(1000);
        $wf->setType('newtype');
        $this->assertSame('newtype', $wf->getType());
        $this->assertNotEquals($originalUpdatedAt, $wf->getUpdatedAt());
    }

    public function testSetDefinition(): void
    {
        $wf = $this->createWorkflow();
        $originalUpdatedAt = $wf->getUpdatedAt();
        usleep(1000);
        $definition = ['steps' => ['start', 'end']];
        $wf->setDefinition($definition);
        $this->assertSame($definition, $wf->getDefinition());
        $this->assertNotEquals($originalUpdatedAt, $wf->getUpdatedAt());

        $wf->setDefinition(null);
        $this->assertNull($wf->getDefinition());
    }

    public function testSetStatus(): void
    {
        $wf = $this->createWorkflow();
        $originalUpdatedAt = $wf->getUpdatedAt();
        usleep(1000);
        $wf->setStatus('INACTIVE');
        $this->assertSame('INACTIVE', $wf->getStatus());
        $this->assertNotEquals($originalUpdatedAt, $wf->getUpdatedAt());
    }

    public function testSetVersion(): void
    {
        $wf = $this->createWorkflow();
        $originalUpdatedAt = $wf->getUpdatedAt();
        usleep(1000);
        $wf->setVersion('2.0');
        $this->assertSame('2.0', $wf->getVersion());
        $this->assertNotEquals($originalUpdatedAt, $wf->getUpdatedAt());

        $wf->setVersion(null);
        $this->assertNull($wf->getVersion());
    }
}
