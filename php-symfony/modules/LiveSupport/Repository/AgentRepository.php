<?php

declare(strict_types=1);

namespace App\LiveSupport\Repository;

use App\LiveSupport\Entity\Agent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agent::class);
    }

    public function findByAgentUserId(string $agentUserId): ?Agent
    {
        return $this->createQueryBuilder('a')
            ->where('a.agentUserId = :agentUserId')
            ->setParameter('agentUserId', $agentUserId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOnline(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', 'online')
            ->orderBy('a.currentLoad', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Agent $agent): void
    {
        $this->getEntityManager()->persist($agent);
        $this->getEntityManager()->flush();
    }

    public function delete(Agent $agent): void
    {
        $this->getEntityManager()->remove($agent);
        $this->getEntityManager()->flush();
    }
}
