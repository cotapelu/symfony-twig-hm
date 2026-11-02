<?php declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\WorkflowInstance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<WorkflowInstance>
 */
class WorkflowInstanceRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowInstance::class);
    }




    public function findByWorkflowId(string $workflowId): array
    {
        return $this->findBy(['workflowId' => $workflowId], ['startedAt' => 'DESC']);
    }

    public function findByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId], ['startedAt' => 'DESC']);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['startedAt' => 'DESC']);
    }

    public function findRunning(): array
    {
        return $this->findBy(['status' => 'RUNNING']);
    }

    public function findByWorkflowAndStatus(string $workflowId, string $status): array
    {
        return $this->findBy(['workflowId' => $workflowId, 'status' => $status]);
    }
}