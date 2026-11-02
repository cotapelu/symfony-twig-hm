<?php

declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\Workflow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<Workflow>
 */
class WorkflowRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workflow::class);
    }




    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type]);
    }

    public function findActiveWorkflows(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function findInactiveWorkflows(): array
    {
        return $this->findBy(['status' => 'INACTIVE']);
    }

    public function findDraftWorkflows(): array
    {
        return $this->findBy(['status' => 'DRAFT']);
    }

    public function countActive(): int
    {
        return $this->count(['status' => 'ACTIVE']);
    }

    public function countInactive(): int
    {
        return $this->count(['status' => 'INACTIVE']);
    }

    public function countDraft(): int
    {
        return $this->count(['status' => 'DRAFT']);
    }
}
