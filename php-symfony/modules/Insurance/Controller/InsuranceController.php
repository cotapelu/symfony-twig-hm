<?php

declare(strict_types=1);

namespace App\Insurance\Controller;

use App\Insurance\Service\InsuranceService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/insurance')]
class InsuranceController extends BaseController
{
    private const VALID_POLICY_TYPES = ['health','life','car','property','travel'];
    private const VALID_STATUSES = ['pending','active','cancelled','expired'];

    public function __construct(private InsuranceService $insuranceService) {}

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error'=>'Authentication required'], 401);
        return $user->getUserIdentifier();
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], Response::HTTP_BAD_REQUEST); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    // ========== Policy Validators ==========
    private function validatePolicyType(array $data, bool $isCreation): void
    {
        if (!$isCreation) return;
        if (empty($data['type'])) throw new InvalidArgumentException('Policy type is required');
        if (!in_array($data['type'], self::VALID_POLICY_TYPES, true)) throw new InvalidArgumentException('Invalid policy type');
    }
    private function validateProvider(array $data, bool $isCreation): void
    {
        if (!$isCreation) return;
        if (empty($data['provider'])) throw new InvalidArgumentException('Provider is required');
    }
    private function validatePolicyNumber(array $data, bool $isCreation): void
    {
        if (!$isCreation) return;
        if (empty($data['policy_number'])) throw new InvalidArgumentException('Policy number is required');
    }
    private function validateCoverageAmount(array $data): void
    {
        if (!isset($data['coverage_amount'])) return;
        $c = (float)$data['coverage_amount'];
        if ($c <= 0) throw new InvalidArgumentException('Coverage amount must be positive');
    }
    private function validatePremium(array $data): void
    {
        if (!isset($data['premium'])) return;
        $p = (float)$data['premium'];
        if ($p <= 0) throw new InvalidArgumentException('Premium must be positive');
    }
    private function validateBeneficiary(array $data): void
    {
        if (!isset($data['beneficiary'])) return;
        $b = trim($data['beneficiary']);
        if (strlen($b) > 255) throw new InvalidArgumentException('Beneficiary name cannot exceed 255 characters');
    }
    private function validateEndDate(array $data): void
    {
        if (!isset($data['end_date']) || empty($data['end_date'])) return;
        try {
            $end = new DateTimeImmutable($data['end_date']);
            if ($end < new DateTimeImmutable()) throw new InvalidArgumentException('End date cannot be in the past');
        } catch (\Exception) { throw new InvalidArgumentException('Invalid end date format'); }
    }
    private function validatePolicyOptionalFields(array $data): void
    {
        $this->validateCoverageAmount($data);
        $this->validatePremium($data);
        $this->validateBeneficiary($data);
        $this->validateEndDate($data);
    }
    private function validatePolicyCreationData(array $data): void
    {
        $this->validatePolicyType($data, true);
        $this->validateProvider($data, true);
        $this->validatePolicyNumber($data, true);
        $this->validatePolicyOptionalFields($data);
    }

    // ========== Claim Validators ==========
    private function validateClaimPolicyId(array $data): void { if (empty($data['policyId'])) throw new InvalidArgumentException('policyId is required'); }
    private function validateClaimNumber(array $data): void { if (empty($data['claimNumber'])) throw new InvalidArgumentException('claimNumber is required'); }
    private function validateClaimAmount(array $data): void
    {
        if ($data['claimAmount'] === null) throw new InvalidArgumentException('claimAmount is required');
        $a = (float)$data['claimAmount'];
        if ($a <= 0) throw new InvalidArgumentException('claimAmount must be positive');
    }
    private function validateClaimDescription(array $data): void { if (empty(trim($data['description'] ?? ''))) throw new InvalidArgumentException('Description is required'); }
    private function validateClaimStatus(array $data): void
    {
        if (!isset($data['status'])) return;
        $valid = ['pending','approved','denied','paid'];
        if (!in_array($data['status'], $valid, true)) throw new InvalidArgumentException('Invalid claim status');
    }
    private function validateClaimData(array $data): void
    {
        $this->validateClaimPolicyId($data);
        $this->validateClaimNumber($data);
        $this->validateClaimAmount($data);
        $this->validateClaimDescription($data);
        $this->validateClaimStatus($data);
    }

    // ========== Shared Helpers ==========
    private function validateMinInt(int $value, string $fieldName, int $min): void
    {
        if ($value < $min) throw new InvalidArgumentException("$fieldName must be at least $min");
    }
    private function validateMaxInt(?int $value, string $fieldName, ?int $max): void
    {
        if ($max !== null && $value !== null && $value > $max) throw new InvalidArgumentException("$fieldName must not exceed $max");
    }
    private function validatePositiveInt(int $value, string $fieldName, int $min = 1, ?int $max = null): int
    {
        $this->validateMinInt($value, $fieldName, $min);
        if ($max !== null) $this->validateMaxInt($value, $fieldName, $max);
        return $value;
    }

    private function applyCoverageAmount($policy, array $data): void
    {
        if (isset($data['coverage_amount'])) {
            $c = (float)$data['coverage_amount'];
            if ($c > 0) $policy->setCoverageAmount($c);
        }
    }
    private function applyPremium($policy, array $data): void
    {
        if (isset($data['premium'])) {
            $p = (float)$data['premium'];
            if ($p > 0) $policy->setPremium($p);
        }
    }
    private function applyBeneficiary($policy, array $data): void
    {
        if (isset($data['beneficiary']) && $data['beneficiary'] !== null) {
            $b = trim($data['beneficiary']);
            if (strlen($b) <= 255) $policy->setBeneficiary(htmlspecialchars($b, ENT_QUOTES, 'UTF-8'));
        }
    }
    private function applyEndDate($policy, array $data): void
    {
        if (isset($data['end_date']) && !empty($data['end_date'])) {
            try {
                $end = new DateTimeImmutable($data['end_date']);
                if ($end >= new DateTimeImmutable()) $policy->setEndDate(new \DateTime($data['end_date']));
            } catch (\Exception) {}
        }
    }
    private function applyPolicyOptionalFields($policy, array $data): void
    {
        $this->applyCoverageAmount($policy, $data);
        $this->applyPremium($policy, $data);
        $this->applyBeneficiary($policy, $data);
        $this->applyEndDate($policy, $data);
    }

    private function buildListResponse(array $items, int $total, int $limit, int $offset): JsonResponse
    {
        return $this->json(['data'=>array_values($items), 'meta'=>['total'=>$total,'limit'=>$limit,'offset'=>$offset,'count'=>count($items)]]);
    }

    // ==================== Endpoints ====================

    #[Route('/policies', name: 'insurance_create_policy', methods: ['POST'])]
    public function createPolicy(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createPolicyAction($request));
    }

    private function createPolicyAction(Request $request): JsonResponse
    {
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $d = $this->decodeJsonOr400($request);
        if ($d instanceof JsonResponse) return $d;
        $data = $d;
        $this->validatePolicyCreationData($data);
        $type = trim($data['type']); $provider = trim($data['provider']); $policyNumber = trim($data['policy_number']);
        $policy = $this->insuranceService->createPolicy($userId, $type, $provider, $policyNumber);
        $this->applyPolicyOptionalFields($policy, $data);
        $this->logAction('policy_created', ['policy_id'=>$policy->getId(),'user_id'=>$userId,'type'=>$type]); $this->incrementMetric('policies_created');
        return $this->json(['policy'=>$policy], Response::HTTP_CREATED);
    }

    #[Route('/policies', name: 'insurance_get_policies', methods: ['GET'])]
    public function getPolicies(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getPoliciesAction($request));
    }

    private function getPoliciesAction(Request $request): JsonResponse
    {
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $limit = max(1, min(100, (int)($request->query->get('limit', 50)))); $offset = max(0, (int)($request->query->get('offset', 0)));
        $total = $this->insuranceService->countUserPolicies($userId); $policies = $this->insuranceService->getUserPolicies($userId, $limit, $offset);
        $this->logAction('policies_fetched', ['user_id'=>$userId, 'count'=>count($policies), 'limit'=>$limit, 'offset'=>$offset]);
        return $this->json(['policies'=>array_map(fn($p)=>$p->toArray(), $policies), 'meta'=>['total'=>$total,'limit'=>$limit,'offset'=>$offset,'count'=>count($policies)]]);
    }

    #[Route('/policies/{id}', name: 'insurance_get_policy', methods: ['GET'])]
    public function getPolicy(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getPolicyAction($id));
    }

    private function getPolicyAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Policy ID must be positive');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $policy = $this->insuranceService->getPolicy($id);
        if (!$policy) return $this->json(['error'=>'Policy not found'], Response::HTTP_NOT_FOUND);
        if ($policy->getUserId() !== $userId) return $this->json(['error'=>'Access denied'], Response::HTTP_FORBIDDEN);
        return $this->json(['policy'=>$policy]);
    }

    #[Route('/policies/{id}', name: 'insurance_update_policy', methods: ['PUT','PATCH'])]
    public function updatePolicy(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updatePolicyAction($id, $request));
    }

    private function updatePolicyAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Policy ID must be positive');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $policy = $this->insuranceService->getPolicy($id);
        if (!$policy) return $this->json(['error'=>'Policy not found'], Response::HTTP_NOT_FOUND);
        if ($policy->getUserId() !== $userId) return $this->json(['error'=>'Access denied'], Response::HTTP_FORBIDDEN);
        $d = $this->decodeJsonOr400($request);
        if ($d instanceof JsonResponse) return $d;
        $data = $d;
        $updated = $this->insuranceService->updatePolicy($id, $data);
        if (!$updated) return $this->json(['error'=>'Failed to update policy'], Response::HTTP_BAD_REQUEST);
        $this->logAction('policy_updated', ['policy_id'=>$id, 'user_id'=>$userId]);
        return $this->json(['policy'=>$updated]);
    }

    #[Route('/policies/{id}', name: 'insurance_delete_policy', methods: ['DELETE'])]
    public function deletePolicy(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->deletePolicyAction($id));
    }

    private function deletePolicyAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Policy ID must be positive');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $policy = $this->insuranceService->getPolicy($id);
        if (!$policy) return $this->json(['error'=>'Policy not found'], Response::HTTP_NOT_FOUND);
        if ($policy->getUserId() !== $userId) return $this->json(['error'=>'Access denied'], Response::HTTP_FORBIDDEN);
        $deleted = $this->insuranceService->deletePolicy($id);
        if (!$deleted) return $this->json(['error'=>'Failed to delete policy'], Response::HTTP_BAD_REQUEST);
        $this->logAction('policy_deleted', ['policy_id'=>$id, 'user_id'=>$userId]); $this->incrementMetric('policies_deleted');
        return $this->json(['message'=>'Policy deleted successfully'], Response::HTTP_NO_CONTENT);
    }

    #[Route('/policies/user/{user_id}', name: 'insurance_get_user_policies', methods: ['GET'])]
    public function getUserPolicies(int $user_id): JsonResponse
    {
        return $this->handle(fn() => $this->getUserPoliciesAction($user_id));
    }

    private function getUserPoliciesAction(int $user_id): JsonResponse
    {
        if ($user_id <= 0) throw new InvalidArgumentException('User ID must be positive');
        if ($u = $this->requireAuthOr401()) $authUserId = (int)$u; else return $u;
        if ($user_id !== $authUserId) return $this->json(['error'=>'Access denied'], Response::HTTP_FORBIDDEN);
        $policies = $this->insuranceService->getUserPolicies($user_id);
        $this->logAction('user_policies_fetched', ['user_id'=>$authUserId, 'count'=>count($policies)]);
        return $this->json(['policies'=>array_values($policies), 'total'=>count($policies)]);
    }

    #[Route('/policies/type/{type}', name: 'insurance_get_policies_by_type', methods: ['GET'])]
    public function getPoliciesByType(string $type, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getPoliciesByTypeAction($type, $request));
    }

    private function getPoliciesByTypeAction(string $type, Request $request): JsonResponse
    {
        if (empty($type)) throw new InvalidArgumentException('Policy type is required');
        if (!in_array($type, self::VALID_POLICY_TYPES, true)) throw new InvalidArgumentException('Invalid policy type');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $limit = max(1, min(100, (int)($request->query->get('limit', 50)))); $offset = max(0, (int)($request->query->get('offset', 0)));
        $total = $this->insuranceService->countPoliciesByType($userId, $type); $policies = $this->insuranceService->getPoliciesByType($userId, $type, $limit, $offset);
        return $this->buildListResponse($policies, $total, $limit, $offset);
    }

    #[Route('/policies/search', name: 'insurance_search_policies', methods: ['GET'])]
    public function searchPolicies(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchPoliciesAction($request));
    }

    private function searchPoliciesAction(Request $request): JsonResponse
    {
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $filters = [];
        if ($request->query->has('type')) {
            $type = $request->query->get('type');
            if (!in_array($type, self::VALID_POLICY_TYPES, true)) throw new InvalidArgumentException('Invalid policy type filter');
            $filters['type'] = $type;
        }
        if ($request->query->has('provider')) $filters['provider'] = $request->query->get('provider');
        if ($request->query->has('status')) {
            $status = $request->query->get('status');
            if (!in_array($status, self::VALID_STATUSES, true)) throw new InvalidArgumentException('Invalid status filter');
            $filters['status'] = $status;
        }
        $policies = $this->insuranceService->searchPolicies($userId, $filters);
        $this->logAction('policies_searched', ['user_id'=>$userId, 'filters'=>$filters, 'count'=>count($policies)]);
        return $this->json(['policies'=>array_map(fn($p)=>$p->toArray(), $policies), 'total'=>count($policies)]);
    }

    #[Route('/policies/expiring', name: 'insurance_get_expiring_policies', methods: ['GET'])]
    public function getExpiringPolicies(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getExpiringPoliciesAction($request));
    }

    private function getExpiringPoliciesAction(Request $request): JsonResponse
    {
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $days = $this->validatePositiveInt((int)$request->query->get('days', 30), 'days', 1, 365);
        $policies = $this->insuranceService->getExpiringPolicies($userId, $days);
        $this->logAction('expiring_policies_fetched', ['user_id'=>$userId, 'days'=>$days, 'count'=>count($policies)]);
        return $this->json(['policies'=>array_values($policies), 'total'=>count($policies)]);
    }

    #[Route('/policies/{id}/activate', name: 'insurance_activate_policy', methods: ['POST'])]
    public function activatePolicy(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->activatePolicyAction($id));
    }

    private function activatePolicyAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Policy ID must be positive');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $policy = $this->insuranceService->getPolicy($id);
        if (!$policy || $policy->getUserId() !== $userId) return $this->json(['error'=> $policy? 'Access denied':'Policy not found'], $policy? Response::HTTP_FORBIDDEN: Response::HTTP_NOT_FOUND);
        $activated = $this->insuranceService->activatePolicy($id);
        if (!$activated) return $this->json(['error'=>'Failed to activate policy'], Response::HTTP_BAD_REQUEST);
        $this->logAction('policy_activated', ['policy_id'=>$id, 'user_id'=>$userId]); $this->incrementMetric('policies_activated');
        return $this->json(['policy'=>$activated]);
    }

    #[Route('/policies/{id}/cancel', name: 'insurance_cancel_policy', methods: ['POST'])]
    public function cancelPolicy(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->cancelPolicyAction($id));
    }

    private function cancelPolicyAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Policy ID must be positive');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $policy = $this->insuranceService->getPolicy($id);
        if (!$policy || $policy->getUserId() !== $userId) return $this->json(['error'=> $policy? 'Access denied':'Policy not found'], $policy? Response::HTTP_FORBIDDEN: Response::HTTP_NOT_FOUND);
        $cancelled = $this->insuranceService->cancelPolicy($id);
        if (!$cancelled) return $this->json(['error'=>'Failed to cancel policy'], Response::HTTP_BAD_REQUEST);
        $this->logAction('policy_cancelled', ['policy_id'=>$id, 'user_id'=>$userId]); $this->incrementMetric('policies_cancelled');
        return $this->json(['policy'=>$cancelled]);
    }

    #[Route('/policies/{id}/renew', name: 'insurance_renew_policy', methods: ['POST'])]
    public function renewPolicy(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->renewPolicyAction($id, $request));
    }

    private function renewPolicyAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Policy ID must be positive');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $policy = $this->insuranceService->getPolicy($id);
        if (!$policy || $policy->getUserId() !== $userId) return $this->json(['error'=> $policy? 'Access denied':'Policy not found'], $policy? Response::HTTP_FORBIDDEN: Response::HTTP_NOT_FOUND);
        $d = $this->decodeJsonOr400($request);
        if ($d instanceof JsonResponse) return $d;
        $data = $d;
        $months = isset($data['months']) ? (int)$data['months'] : 12;
        if ($months <= 0 || $months > 60) throw new InvalidArgumentException('Renewal months must be between 1 and 60');
        $renewed = $this->insuranceService->renewPolicy($id, $months);
        if (!$renewed) return $this->json(['error'=>'Failed to renew policy'], Response::HTTP_BAD_REQUEST);
        $this->logAction('policy_renewed', ['policy_id'=>$id, 'user_id'=>$userId, 'months'=>$months]); $this->incrementMetric('policies_renewed');
        return $this->json(['policy'=>$renewed]);
    }

    #[Route('/claims', name: 'insurance_create_claim', methods: ['POST'])]
    public function createClaim(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createClaimAction($request));
    }

    private function createClaimAction(Request $request): JsonResponse
    {
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $d = $this->decodeJsonOr400($request);
        if ($d instanceof JsonResponse) return $d;
        $data = $d;
        $this->validateClaimData($data);
        $policyId = $data['policyId']; $claimNumber = $data['claimNumber']; $description = trim($data['description']);
        $claimAmount = (float)$data['claimAmount']; $status = $data['status'] ?? 'pending';
        $policy = $this->insuranceService->getPolicy($policyId);
        if (!$policy || $policy->getUserId() !== $userId) return $this->json(['error'=>'Policy not found or access denied'], Response::HTTP_FORBIDDEN);
        $claim = $this->insuranceService->createClaim((string)$policyId, (string)$claimNumber, (string)$description, (float)$claimAmount, (string)$status);
        $this->logAction('claim_created', ['claim_id'=>$claim->getId(), 'policy_id'=>$policyId, 'user_id'=>$userId]); $this->incrementMetric('claims_created');
        return $this->json([
            'claim'=>[
                'id'=>$claim->getId(), 'claimNumber'=>$claim->getClaimNumber(), 'policyId'=>$claim->getPolicy()->getId(),
                'amount'=>$claim->getClaimAmount(), 'status'=>$claim->getStatus(), 'description'=>$claim->getDescription(),
                'createdAt'=>$claim->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/claims/{id}', name: 'insurance_get_claim', methods: ['GET'])]
    public function getClaim(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getClaimAction($id, $request));
    }

    private function getClaimAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Claim ID must be positive');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $claim = $this->insuranceService->getClaim($id);
        if (!$claim) return $this->json(['error'=>'Claim not found'], Response::HTTP_NOT_FOUND);
        $policy = $this->insuranceService->getPolicy($claim->getPolicy()->getId());
        if (!$policy || $policy->getUserId() !== $userId) return $this->json(['error'=>'Access denied'], Response::HTTP_FORBIDDEN);
        return $this->json([
            'claim'=>[
                'id'=>$claim->getId(), 'claimNumber'=>$claim->getClaimNumber(), 'policyId'=>$claim->getPolicy()->getId(),
                'amount'=>$claim->getClaimAmount(), 'status'=>$claim->getStatus(), 'description'=>$claim->getDescription(),
                'createdAt'=>$claim->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    #[Route('/policies/{policyId}/claims', name: 'insurance_get_policy_claims', methods: ['GET'])]
    public function getPolicyClaims(int $policyId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getPolicyClaimsAction($policyId, $request));
    }

    private function getPolicyClaimsAction(int $policyId, Request $request): JsonResponse
    {
        if ($policyId <= 0) throw new InvalidArgumentException('Policy ID must be positive');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $policy = $this->insuranceService->getPolicy($policyId);
        if (!$policy || $policy->getUserId() !== $userId) return $this->json(['error'=>'Access denied'], Response::HTTP_FORBIDDEN);
        $limit = max(1, min(100, (int)($request->query->get('limit', 50)))); $offset = max(0, (int)($request->query->get('offset', 0)));
        $claims = $this->insuranceService->getPolicyClaims((string)$policyId, $limit, $offset);
        $total = $this->insuranceService->countPolicyClaims((string)$policyId);
        return $this->json([
            'claims'=>array_map(fn($c)=>['id'=>$c->getId(),'claimNumber'=>$c->getClaimNumber(),'amount'=>$c->getClaimAmount(),'status'=>$c->getStatus(),'description'=>$c->getDescription(),'createdAt'=>$c->getCreatedAt()->format('Y-m-d H:i:s')], $claims),
            'meta'=>['total'=>$total,'limit'=>$limit,'offset'=>$offset,'count'=>count($claims)]
        ]);
    }

    #[Route('/claims/{id}/status', name: 'insurance_update_claim_status', methods: ['POST'])]
    public function updateClaimStatus(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateClaimStatusAction($id, $request));
    }

    private function updateClaimStatusAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Claim ID must be positive');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $claim = $this->insuranceService->getClaim($id);
        if (!$claim) return $this->json(['error'=>'Claim not found'], Response::HTTP_NOT_FOUND);
        $policy = $this->insuranceService->getPolicy($claim->getPolicy()->getId());
        if (!$policy || $policy->getUserId() !== $userId) return $this->json(['error'=>'Access denied'], Response::HTTP_FORBIDDEN);
        $d = $this->decodeJsonOr400($request);
        if ($d instanceof JsonResponse) return $d;
        $data = $d;
        if (!isset($data['status'])) throw new InvalidArgumentException('Status is required');
        $newStatus = $data['status']; $valid = ['pending','approved','denied','paid'];
        if (!in_array($newStatus, $valid, true)) throw new InvalidArgumentException('Invalid claim status');
        $updated = $this->insuranceService->updateClaimStatus($id, $newStatus);
        if (!$updated) return $this->json(['error'=>'Failed to update claim status'], Response::HTTP_BAD_REQUEST);
        $this->logAction('claim_status_updated', ['claim_id'=>$id, 'user_id'=>$userId, 'status'=>$newStatus]); $this->incrementMetric('claim_status_updated');
        return $this->json(['claim'=>['id'=>$updated->getId(), 'status'=>$updated->getStatus()]]);
    }

    #[Route('/claims/{id}', name: 'insurance_delete_claim', methods: ['DELETE'])]
    public function deleteClaim(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->deleteClaimAction($id));
    }

    private function deleteClaimAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Claim ID must be positive');
        if ($u = $this->requireAuthOr401()) $userId = (int)$u; else return $u;
        $claim = $this->insuranceService->getClaim($id);
        if (!$claim) return $this->json(['error'=>'Claim not found'], Response::HTTP_NOT_FOUND);
        $policy = $this->insuranceService->getPolicy($claim->getPolicy()->getId());
        if (!$policy || $policy->getUserId() !== $userId) return $this->json(['error'=>'Access denied'], Response::HTTP_FORBIDDEN);
        $deleted = $this->insuranceService->deleteClaim($id);
        if (!$deleted) return $this->json(['error'=>'Failed to delete claim'], Response::HTTP_BAD_REQUEST);
        $this->logAction('claim_deleted', ['claim_id'=>$id, 'user_id'=>$userId]); $this->incrementMetric('claims_deleted');
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    // ========== Utilities ==========
    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("InsuranceController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$context}_errors");
        return $this->json(['error'=>'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function logAction(string $action, array $context = []): void
    {
        $logEntry = ['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context];
        error_log(json_encode($logEntry));
    }

    private function incrementMetric(string $metricName): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metricName, 'value'=>1]));
    }
}
