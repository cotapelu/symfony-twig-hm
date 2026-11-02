<?php declare(strict_types=1);

namespace App\Tests\Unit\Utility\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Utility\Entity\Workflow;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WorkflowTest extends TestCase
{
    public function testConstructorGeneratesUuidAndTimestamps(): void
    {
        $workflow = new Workflow();

        $this->assertNotNull($workflow->getId());
        $this->assertEquals(36, strlen($workflow->getId())); // UUID format
        $this->assertInstanceOf(\DateTimeInterface::class, $workflow->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $workflow->getUpdatedAt());
    }

    public function testGetNameAndSetName(): void
    {
        $workflow = new Workflow();
        $workflow->setName('User Registration');
        $this->assertSame('User Registration', $workflow->getName());
    }

    public function testGetDefinitionAndSetDefinition(): void
    {
        $workflow = new Workflow();
        $definition = ['steps' => ['start', 'approve', 'end']];
        $workflow->setDefinition($definition);
        $this->assertSame($definition, $workflow->getDefinition());
    }

    public function testGetVersionAndSetVersion(): void
    {
        $workflow = new Workflow();
        $workflow->setVersion('1.0.0');
        $this->assertSame('1.0.0', $workflow->getVersion());
    }

    public function testGetStatusAndSetStatus(): void
    {
        $workflow = new Workflow();
        $workflow->setStatus(Workflow::STATUS_INACTIVE);
        $this->assertSame(Workflow::STATUS_INACTIVE, $workflow->getStatus());
    }

    public function testDefaultStatusIsActive(): void
    {
        $workflow = new Workflow();
        $this->assertSame(Workflow::STATUS_ACTIVE, $workflow->getStatus());
    }

    public function testStatusConstantsExist(): void
    {
        $this->assertSame('active', Workflow::STATUS_ACTIVE);
        $this->assertSame('inactive', Workflow::STATUS_INACTIVE);
        $this->assertSame('archived', Workflow::STATUS_ARCHIVED);
    }

    public function testGetCreatedAtAndGetUpdatedAt(): void
    {
        $workflow = new Workflow();
        $created = $workflow->getCreatedAt();
        $updated = $workflow->getUpdatedAt();

        $this->assertInstanceOf(\DateTimeInterface::class, $created);
        $this->assertInstanceOf(\DateTimeInterface::class, $updated);
        // They are set to the same time initially
        $this->assertSame($created->format('Y-m-d H:i:s'), $updated->format('Y-m-d H:i:s'));
    }
}
