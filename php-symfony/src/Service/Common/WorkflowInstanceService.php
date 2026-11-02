<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\WorkflowInstance;
use App\Entity\Common\Workflow;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing workflow instances
 */
class WorkflowInstanceService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    /**
     * Start a new workflow instance
     */
    public function startWorkflow(
        string $workflowId,
        ?string $userId = null,
        ?array $context = null
    ) {
        $workflow = $this->verifyWorkflowExists($workflowId);
        $instance = $this->buildInstanceEntity($workflowId, $userId, $context);
        $this->em->persist($instance);
        $this->em->flush();
        $this->logger->info('Workflow instance started', [
            'instance_id' => $instance->getId(),
            'workflow_id' => $workflowId,
            'user_id' => $userId,
        ]);
        return $instance;
    }

    /**
     * Get workflow instance by ID
     */
    public function getInstance(string $instanceId)
    {
        return $this->em->getRepository(WorkflowInstance::class)->find($instanceId);
    }

    /**
     * Get all instances for a workflow
     */
    public function getInstancesByWorkflow(string $workflowId)
    {
        return $this->em->getRepository(WorkflowInstance::class)
            ->findBy(['workflowId' => $workflowId], ['startedAt' => 'DESC']);
    }

    /**
     * Get all instances for a user
     */
    public function getUserInstances(string $userId)
    {
        return $this->em->getRepository(WorkflowInstance::class)
            ->findBy(['userId' => $userId], ['startedAt' => 'DESC']);
    }

    /**
     * Complete a workflow instance
     */
    public function completeInstance(string $instanceId, array $output = null)
    {
        $instance = $this->getInstance($instanceId);
        if ($instance === null) {
            throw new \InvalidArgumentException('Workflow instance not found: ' . $instanceId);
        }

        $instance->setStatus('COMPLETED');
        $instance->setCompletedAt(new \DateTime());
        $instance->setOutput($output);

        $this->em->flush();

        $this->logger->info('Workflow instance completed', [
            'instance_id' => $instanceId,
        ]);

        return $instance;
    }

    /**
     * Fail a workflow instance
     */
    public function failInstance(string $instanceId, string $error)
    {
        $instance = $this->getInstance($instanceId);
        if ($instance === null) {
            throw new \InvalidArgumentException('Workflow instance not found: ' . $instanceId);
        }

        $instance->setStatus('FAILED');
        $instance->setFailedAt(new \DateTime());
        $instance->setError($error);

        $this->em->flush();

        $this->logger->error('Workflow instance failed', [
            'instance_id' => $instanceId,
            'error' => $error,
        ]);

        return $instance;
    }

    public function complete(string $instanceId, array $output = null)
    {
        return $this->completeInstance($instanceId, $output);
    }

    public function fail(string $instanceId, string $error)
    {
        return $this->failInstance($instanceId, $error);
    }

    /**
     * Generate unique ID
     */
    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /** Verify workflow exists or throw */
    private function verifyWorkflowExists(string $workflowId): Workflow
    {
        $workflow = $this->em->getRepository(Workflow::class)->find($workflowId);
        if ($workflow === null) {
            throw new \InvalidArgumentException('Workflow not found: ' . $workflowId);
        }
        return $workflow;
    }

    /** Build workflow instance entity */
    private function buildInstanceEntity(
        string $workflowId,
        ?string $userId,
        ?array $context
    ): WorkflowInstance {
        $instance = new WorkflowInstance(
            $this->generateId(),
            $workflowId
        );
        $instance->setUserId($userId);
        $instance->setStatus('RUNNING');
        $instance->setContext($context);
        $instance->setState([]);
        return $instance;
    }
}
