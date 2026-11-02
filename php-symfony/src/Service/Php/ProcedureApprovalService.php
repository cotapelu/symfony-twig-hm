<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\ProcedureApproval;
use App\Repository\Php\ProcedureApprovalRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProcedureApprovalService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProcedureApprovalRepository $repository
    ) {}

    public function createApproval(
        string $applicationId,
        string $decision,
        ?string $officerId = null,
        ?string $notes = null
    ) {
        if (empty(trim($applicationId))) throw new \InvalidArgumentException('Application ID is required');
        if (empty(trim($decision))) throw new \InvalidArgumentException('Decision is required');

        $id = bin2hex(random_bytes(16));
        $approval = new ProcedureApproval($id, $applicationId, $decision);
        $approval->setOfficerId($officerId);
        $approval->setNotes($notes);
        $this->em->persist($approval);
        $this->em->flush();
        return $approval;
    }

    public function getApproval(string $id)
    {
        $approval = $this->repository->find($id);
        if (!$approval) throw new \RuntimeException('ProcedureApproval not found');
        return $approval;
    }

    public function updateApproval(string $id, array $data)
    {
        $approval = $this->getApproval($id);
        isset($data['applicationId']) && $this->applyApplicationIdUpdate($approval, $data['applicationId']);
        isset($data['decision']) && $this->applyDecisionUpdate($approval, $data['decision']);
        array_key_exists('officerId', $data) && $approval->setOfficerId($data['officerId']);
        array_key_exists('notes', $data) && $approval->setNotes($data['notes']);
        $this->em->flush();
        return $approval;
    }

    public function deleteApproval(string $id)
    {
        $approval = $this->getApproval($id);
        $this->em->remove($approval);
        $this->em->flush();
    }

    public function findByApplicationId(string $applicationId)
    {
        return $this->repository->findBy(['applicationId' => $applicationId]);
    }

    public function findByOfficerId(string $officerId)
    {
        return $this->repository->findBy(['officerId' => $officerId]);
    }

    private function applyApplicationIdUpdate(ProcedureApproval $approval, mixed $appId): void
    {
        $appId = trim($appId);
        if ($appId === '') throw new \InvalidArgumentException('Application ID cannot be empty');
        $approval->setApplicationId($appId);
    }

    private function applyDecisionUpdate(ProcedureApproval $approval, mixed $decision): void
    {
        $decision = trim($decision);
        if ($decision === '') throw new \InvalidArgumentException('Decision cannot be empty');
        $approval->setDecision($decision);
    }
}
