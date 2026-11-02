<?php

declare(strict_types=1);

namespace App\Insurance\Service;

use App\Insurance\Entity\Policy;
use App\Insurance\Entity\Claim;
use App\Insurance\Repository\PolicyRepository;
use App\Insurance\Repository\ClaimRepository;
use Doctrine\ORM\EntityManagerInterface;

class InsuranceService
{
    private PolicyRepository $policyRepository;
    private ClaimRepository $claimRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(PolicyRepository $policyRepository, ClaimRepository $claimRepository, EntityManagerInterface $entityManager)
    {
        $this->policyRepository = $policyRepository;
        $this->claimRepository = $claimRepository;
        $this->entityManager = $entityManager;
    }

    public function createPolicy(
        int $userId,
        string $type,
        string $provider,
        string $policyNumber,
        ?float $coverageAmount = null,
        ?float $premium = null,
        ?string $beneficiary = null,
        ?string $startDate = null,
        ?string $endDate = null,
        string $status = 'active'
    ) {
        $this->validateAllFields($userId, $type, $provider, $policyNumber);
        $policy = new Policy();
        $this->setBasicFields($policy, $userId, $type, $provider, $policyNumber);
        $this->setOptionalFields($policy, $coverageAmount, $premium, $beneficiary, $startDate, $endDate);
        $policy->setStatus($status);
        $this->policyRepository->save($policy);
        return $policy;
    }

    public function getPolicy(int $id, ?int $userId = null)
    {
        $policy = $this->policyRepository->find($id);
        if ($policy === null) {
            return null;
        }
        if ($userId !== null && (int)$policy->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Access denied');
        }
        return $policy;
    }

    public function getAllPolicies(int $limit = 50, int $offset = 0)
    {
        return $this->policyRepository->findAll($limit, $offset);
    }

    public function countAllPolicies()
    {
        return $this->policyRepository->countAll();
    }

    public function getUserPolicies(int $userId, int $limit = 50, int $offset = 0)
    {
        return $this->policyRepository->findByUser((string)$userId, $limit, $offset);
    }

    public function countUserPolicies(int $userId)
    {
        return $this->policyRepository->countByUser((string)$userId);
    }

    public function getPoliciesByType(int $userId, string $type, int $limit = 50, int $offset = 0)
    {
        return $this->policyRepository->findByUserAndType((string)$userId, $type, $limit, $offset);
    }

    public function countPoliciesByType(int $userId, string $type)
    {
        return $this->policyRepository->countByUserAndType((string)$userId, $type);
    }

    public function getUserClaims(int $userId, int $limit = 50, int $offset = 0)
    {
        return $this->claimRepository->findByUserId((string)$userId, $limit, $offset);
    }

    public function countUserClaims(int $userId)
    {
        return $this->claimRepository->countByUserId((string)$userId);
    }

    public function getExpiringPolicies(int $userId, int $days = 30): array
    {
        $date = new \DateTime();
        $date->modify("+$days days");

        return $this->policyRepository->createQueryBuilder('p')
            ->where('p.userId = :uid')
            ->andWhere('p.status = :status')
            ->andWhere('p.endDate BETWEEN CURRENT_TIMESTAMP() AND :date')
            ->setParameter('uid', $userId)
            ->setParameter('status', 'active')
            ->setParameter('date', $date)
            ->orderBy('p.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(int $userId): array
    {
        // Aggregate statistics for user's policies
        $policies = $this->getUserPolicies($userId, 1000); // fetch up to 1000
        $total = count($policies);
        $statuses = ['active', 'pending', 'cancelled', 'expired'];
        $counts = array_fill_keys($statuses, 0);
        foreach ($policies as $p) {
            $s = $p->getStatus();
            if (isset($counts[$s])) {
                $counts[$s]++;
            }
        }
        return array_merge(['total' => $total], $counts);
    }

    public function searchPolicies(int $userId, array $filters)
    {
        $qb = $this->policyRepository->createQueryBuilder('p');
        $qb->andWhere('p.userId = :uid')->setParameter('uid', $userId);

        if (isset($filters['type'])) {
            $type = $filters['type'];
            if (!in_array($type, ['health', 'life', 'car', 'property', 'travel'], true)) {
                throw new \InvalidArgumentException('Invalid policy type filter');
            }
            $qb->andWhere('p.type = :type')->setParameter('type', $type);
        }
        if (isset($filters['provider'])) {
            $qb->andWhere('p.provider = :provider')->setParameter('provider', $filters['provider']);
        }
        if (isset($filters['status'])) {
            $validStatuses = ['pending', 'active', 'cancelled', 'expired'];
            if (!in_array($filters['status'], $validStatuses, true)) {
                throw new \InvalidArgumentException('Invalid status filter');
            }
            $qb->andWhere('p.status = :status')->setParameter('status', $filters['status']);
        }

        return $qb->orderBy('p.createdAt', 'DESC')->getQuery()->getResult();
    }

    public function updatePolicy(int $id, array $data, ?int $userId = null)
    {
        $policy = $this->getPolicy($id, $userId);
        if (!$policy) {
            return null;
        }

        $this->applyCoverageAmount($policy, $data);
        $this->applyPremium($policy, $data);
        $this->applyBeneficiary($policy, $data);
        $this->applyEndDate($policy, $data);
        $this->applyStatus($policy, $data);

        $this->policyRepository->save($policy);
        return $policy;
    }

    public function deletePolicy(int $id, ?int $userId = null)
    {
        $policy = $this->getPolicy($id, $userId);
        if (!$policy) {
            return false;
        }
        $em = $this->entityManager;
        $em->remove($policy);
        $em->flush();
        return true;
    }

    public function activatePolicy(int $id, ?int $userId = null)
    {
        $policy = $this->getPolicy($id, $userId);
        if (!$policy) {
            return null;
        }
        $policy->setStatus('active');
        $this->policyRepository->save($policy);
        return $policy;
    }

    public function cancelPolicy(int $id, ?int $userId = null)
    {
        $policy = $this->getPolicy($id, $userId);
        if (!$policy) {
            return null;
        }
        $policy->setStatus('cancelled');
        $this->policyRepository->save($policy);
        return $policy;
    }

    public function renewPolicy(int $id, int $months, ?int $userId = null)
    {
        $policy = $this->getPolicy($id, $userId);
        if (!$policy) {
            return null;
        }

        $endDate = $policy->getEndDate();
        if (!$endDate) {
            throw new \InvalidArgumentException('Policy has no end date to renew');
        }

        $newEndDate = clone $endDate;
        $newEndDate->modify("+$months months");
        $policy->setEndDate($newEndDate);
        $policy->setStatus('active');

        $this->policyRepository->save($policy);
        return $policy;
    }

    // ========== CLAIMS ==========

    public function createClaim(
        string $policyId,
        string $claimNumber,
        string $description,
        float $claimAmount,
        string $status = 'pending'
    ) {
        $this->validateClaimAmount($claimAmount);
        $this->ensureClaimNumberUnique($claimNumber);
        $policy = $this->getPolicyOrFail((int)$policyId);

        $claim = new Claim();
        $claim->setPolicy($policy);
        $claim->setClaimNumber($claimNumber);
        $claim->setDescription($description);
        $claim->setClaimAmount($claimAmount);
        $claim->setStatus($status);

        $this->claimRepository->save($claim);
        return $claim;
    }

    public function getClaim(int $id, ?int $userId = null)
    {
        $claim = $this->claimRepository->find($id);
        if ($claim === null) {
            return null;
        }
        if ($userId !== null && $claim->getPolicy() !== null && (int)$claim->getPolicy()->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Access denied');
        }
        return $claim;
    }

    public function getPolicyClaims(string $policyId, int $limit = 50, int $offset = 0)
    {
        return $this->claimRepository->findByPolicy($policyId, $limit, $offset);
    }

    public function countPolicyClaims(string $policyId)
    {
        return $this->claimRepository->countByPolicy($policyId);
    }

    public function updateClaimStatus(int $id, string $status, ?int $userId = null)
    {
        $claim = $this->getClaim($id, $userId);
        if (!$claim) {
            return null;
        }

        $validStatuses = ['pending', 'approved', 'denied', 'paid'];
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException('Invalid claim status');
        }

        $claim->setStatus($status);
        $this->claimRepository->save($claim);
        return $claim;
    }

    public function deleteClaim(int $id, ?int $userId = null)
    {
        $claim = $this->getClaim($id, $userId);
        if (!$claim) {
            return false;
        }
        $em = $this->entityManager;
        $em->remove($claim);
        $em->flush();
        return true;
    }

    private function validateClaimAmount(float $claimAmount): void
    {
        if ($claimAmount <= 0) {
            throw new \InvalidArgumentException('Claim amount must be positive');
        }
    }

    private function ensureClaimNumberUnique(string $claimNumber): void
    {
        $existing = $this->claimRepository->findByClaimNumber($claimNumber);
        if ($existing) {
            throw new \InvalidArgumentException('Claim number must be unique');
        }
    }

    private function getPolicyOrFail(int $policyId): Policy
    {
        $policy = $this->getPolicy($policyId);
        if (!$policy) {
            throw new \InvalidArgumentException('Policy not found');
        }
        return $policy;
    }

    private function applyCoverageAmount(Policy $policy, array $data): void
    {
        if (isset($data['coverage_amount'])) {
            $coverage = (float)$data['coverage_amount'];
            if ($coverage <= 0) {
                throw new \InvalidArgumentException('Coverage amount must be positive');
            }
            $policy->setCoverageAmount($coverage);
        }
    }

    private function applyPremium(Policy $policy, array $data): void
    {
        if (isset($data['premium'])) {
            $premium = (float)$data['premium'];
            if ($premium <= 0) {
                throw new \InvalidArgumentException('Premium must be positive');
            }
            $policy->setPremium($premium);
        }
    }

    private function applyBeneficiary(Policy $policy, array $data): void
    {
        if (isset($data['beneficiary'])) {
            $beneficiary = trim($data['beneficiary']);
            if ($beneficiary !== '') {
                if (strlen($beneficiary) > 255) {
                    throw new \InvalidArgumentException('Beneficiary name cannot exceed 255 characters');
                }
                $policy->setBeneficiary(htmlspecialchars($beneficiary, ENT_QUOTES, 'UTF-8'));
            } else {
                $policy->setBeneficiary(null);
            }
        }
    }

    private function applyEndDate(Policy $policy, array $data): void
    {
        if (isset($data['end_date']) && !empty($data['end_date'])) {
            try {
                $endDate = new \DateTime($data['end_date']);
                $policy->setEndDate($endDate);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid end date format');
            }
        }
    }

    private function applyStatus(Policy $policy, array $data): void
    {
        if (isset($data['status'])) {
            $validStatuses = ['active', 'expired', 'cancelled'];
            if (!in_array($data['status'], $validStatuses, true)) {
                throw new \InvalidArgumentException('Invalid status');
            }
            $policy->setStatus($data['status']);
        }
    }

    private function validateUserId(int $userId): void
    {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }
    }

    private function validatePolicyType(string $type): void
    {
        if (empty(trim($type))) {
            throw new \InvalidArgumentException('Policy type is required');
        }
        $validTypes = ['health', 'life', 'car', 'property', 'travel'];
        if (!in_array($type, $validTypes, true)) {
            throw new \InvalidArgumentException('Invalid policy type. Valid types: ' . implode(', ', $validTypes));
        }
    }

    private function validateProvider(string $provider): void
    {
        if (empty(trim($provider))) {
            throw new \InvalidArgumentException('Provider is required');
        }
    }

    private function validatePolicyNumber(string $policyNumber): void
    {
        if (empty(trim($policyNumber))) {
            throw new \InvalidArgumentException('Policy number is required');
        }
    }

    private function ensurePolicyNumberUnique(string $policyNumber): void
    {
        $existing = $this->policyRepository->findByPolicyNumber($policyNumber);
        if ($existing) {
            throw new \InvalidArgumentException('Policy number must be unique');
        }
    }

    private function validateAllFields(int $userId, string $type, string $provider, string $policyNumber): void
    {
        $this->validateUserId($userId);
        $this->validatePolicyType($type);
        $this->validateProvider($provider);
        $this->validatePolicyNumber($policyNumber);
        $this->ensurePolicyNumberUnique($policyNumber);
    }

    private function setBasicFields(Policy $policy, int $userId, string $type, string $provider, string $policyNumber): void
    {
        $policy->setUserId((string)$userId);
        $policy->setType($type);
        $policy->setProvider($provider);
        $policy->setPolicyNumber($policyNumber);
    }

    private function setOptionalFields(
        Policy $policy,
        ?float $coverageAmount,
        ?float $premium,
        ?string $beneficiary,
        ?string $startDate,
        ?string $endDate
    ): void {
        $this->applyCoverageAmount($policy, ['coverage_amount' => $coverageAmount]);
        $this->applyPremium($policy, ['premium' => $premium]);
        $this->applyBeneficiary($policy, ['beneficiary' => $beneficiary]);
        $this->applyStartDate($policy, $startDate);
        $this->applyEndDate($policy, ['end_date' => $endDate]);
    }

    private function applyStartDate(Policy $policy, ?string $startDate): void
    {
        if ($startDate !== null) {
            try {
                $policy->setStartDate(new \DateTime($startDate));
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid start date format');
            }
        }
    }
}
