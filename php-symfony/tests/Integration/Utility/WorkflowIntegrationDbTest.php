<?php

declare(strict_types=1);

namespace App\Tests\Integration\Utility;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Utility\Entity\Workflow;

#[AllowMockObjectsWithoutExpectations]
class WorkflowIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrieveWorkflow(): void
    {
        $workflow = new Workflow();
        $workflow->setName('Order Process')
            ->setDefinition(['steps' => ['create', 'approve', 'ship']])
            ->setVersion('1.0')
            ->setStatus(Workflow::STATUS_ACTIVE);

        $em = self::getEntityManager();
        $em->persist($workflow);
        $em->flush();

        $this->assertNotNull($workflow->getId());

        $found = $em->getRepository(Workflow::class)->find($workflow->getId());
        $this->assertNotNull($found);
        $this->assertSame('Order Process', $found->getName());
        $this->assertSame(['steps' => ['create', 'approve', 'ship']], $found->getDefinition());
        $this->assertSame('1.0', $found->getVersion());
        $this->assertSame(Workflow::STATUS_ACTIVE, $found->getStatus());
    }

    public function testUpdateWorkflowStatus(): void
    {
        $workflow = new Workflow();
        $workflow->setName('Invoice Process')
            ->setDefinition([])
            ->setStatus(Workflow::STATUS_ACTIVE);

        $em = self::getEntityManager();
        $em->persist($workflow);
        $em->flush();

        $workflow->setStatus(Workflow::STATUS_INACTIVE);
        $em->flush();

        $found = $em->getRepository(Workflow::class)->find($workflow->getId());
        $this->assertSame(Workflow::STATUS_INACTIVE, $found->getStatus());
    }

    public function testDeleteWorkflow(): void
    {
        $workflow = new Workflow();
        $workflow->setName('Temp Workflow')
            ->setDefinition(['step1']);

        $em = self::getEntityManager();
        $em->persist($workflow);
        $em->flush();

        $id = $workflow->getId();
        $em->remove($workflow);
        $em->flush();

        $found = $em->getRepository(Workflow::class)->find($id);
        $this->assertNull($found);
    }

    public function testWorkflowIdIsUuid(): void
    {
        $workflow = new Workflow();
        $workflow->setName('Test')
            ->setDefinition([]);

        $em = self::getEntityManager();
        $em->persist($workflow);
        $em->flush();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $workflow->getId());
    }
}
