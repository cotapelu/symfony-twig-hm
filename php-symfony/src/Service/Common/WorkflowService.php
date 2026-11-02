<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\Workflow;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;

/**
 * Service for managing workflows
 */
class WorkflowService
{
    private const ALLOWED_STATUS = ['ACTIVE', 'INACTIVE', 'DRAFT', 'ARCHIVED'];

    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

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

    private function validateWorkflow(string $name, string $type, array $definition)
    {
        if (empty(trim($name))) throw new \InvalidArgumentException('Workflow name is required');
        if (empty(trim($type))) throw new \InvalidArgumentException('Workflow type is required');
        if (empty($definition)) throw new \InvalidArgumentException('Workflow definition cannot be empty');
    }

    public function create(
        string $name,
        string $type,
        array $definition,
        string $status = 'ACTIVE',
        ?string $version = null
    ) {
        $this->validateWorkflow($name, $type, $definition);
        $workflow = new Workflow($this->generateId(), $name, $type);
        $workflow->setDefinition($definition);
        $workflow->setStatus($status);
        $workflow->setVersion($version);
        try {
            $this->em->persist($workflow);
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to create workflow', ['name' => $name, 'type' => $type, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to create workflow', 0, $e);
        }
        $this->logger->info('Workflow created', ['id' => $workflow->getId()]);
        return $workflow;
    }

    public function get(string $id)
    {
        return $this->em->getRepository(Workflow::class)->find($id);
    }

    public function getByType(string $type, string $status = 'ACTIVE')
    {
        return $this->em->getRepository(Workflow::class)->findBy(['type' => $type, 'status' => $status]);
    }

    public function getActive()
    {
        return $this->em->getRepository(Workflow::class)->findBy(['status' => 'ACTIVE']);
    }

    public function updateDefinition(string $id, array $definition)
    {
        if (empty($definition)) throw new \InvalidArgumentException('Workflow definition cannot be empty');
        $workflow = $this->get($id);
        if (!$workflow) throw new \RuntimeException("Workflow not found: $id");
        $workflow->setDefinition($definition);
        try {
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to update workflow definition', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to update workflow definition', 0, $e);
        }
        $this->logger->info('Workflow definition updated', ['id' => $id]);
        return $workflow;
    }

    public function changeStatus(string $id, string $status)
    {
        if (!in_array($status, self::ALLOWED_STATUS, true)) {
            throw new \InvalidArgumentException("Invalid status. Allowed: " . implode(', ', self::ALLOWED_STATUS));
        }
        $workflow = $this->get($id);
        if (!$workflow) throw new \RuntimeException("Workflow not found: $id");
        $this->setWorkflowStatus($workflow, $status);
        try {
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to change workflow status', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to change workflow status', 0, $e);
        }
        $this->logger->info('Workflow status changed', ['id' => $id, 'status' => $status]);
        return $workflow;
    }

    public function setVersion(string $id, string $version)
    {
        $workflow = $this->get($id);
        if (!$workflow) throw new \RuntimeException("Workflow not found: $id");
        $workflow->setVersion($version);
        try {
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to set workflow version', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to set workflow version', 0, $e);
        }
        $this->logger->info('Workflow version set', ['id' => $id, 'version' => $version]);
        return $workflow;
    }

    public function delete(string $id)
    {
        $workflow = $this->get($id);
        if (!$workflow) return false;
        try {
            $this->em->remove($workflow);
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to delete workflow', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to delete workflow', 0, $e);
        }
        $this->logger->info('Workflow deleted', ['id' => $id]);
        return true;
    }

    private function setWorkflowStatus(Workflow $workflow, string $status): void
    {
        $workflow->setStatus($status);
    }
}
