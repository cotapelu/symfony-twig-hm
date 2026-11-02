<?php

declare(strict_types=1);

namespace App\Tests\Integration\Insurance;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Insurance\Service\InsuranceService;
use App\Insurance\Entity\Policy;
use App\Insurance\Entity\Claim;
use App\Insurance\Repository\PolicyRepository;
use App\Insurance\Repository\ClaimRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class InsuranceIntegrationTest extends DatabaseIntegrationTestCase
{
    private InsuranceService $service;
    private PolicyRepository $policyRepo;
    private ClaimRepository $claimRepo;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::getEntityManager();
        $this->policyRepo = $this->em->getRepository(Policy::class);
        $this->claimRepo = $this->em->getRepository(Claim::class);

        $this->service = new InsuranceService(
            $this->policyRepo,
            $this->claimRepo,
            $this->em
        );
    }

    private function createPolicyDirect(array $overrides = []): Policy
    {
        $policy = new Policy();
        $policy->setUserId($overrides['userId'] ?? 'user-' . bin2hex(random_bytes(8)));
        $policy->setType($overrides['type'] ?? 'health');
        $policy->setProvider($overrides['provider'] ?? 'TestProvider');
        $policy->setCoverageAmount($overrides['coverageAmount'] ?? 10000.0);
        $policy->setPremium($overrides['premium'] ?? 150.0);
        if (isset($overrides['policyNumber'])) {
            $policy->setPolicyNumber($overrides['policyNumber']);
        }
        if (isset($overrides['status'])) {
            $policy->setStatus($overrides['status']);
        }
        if (isset($overrides['startDate'])) {
            $policy->setStartDate($overrides['startDate']);
        }
        if (isset($overrides['endDate'])) {
            $policy->setEndDate($overrides['endDate']);
        }
        if (isset($overrides['beneficiary'])) {
            $policy->setBeneficiary($overrides['beneficiary']);
        }
        $this->em->persist($policy);
        $this->em->flush();
        return $policy;
    }

    private function createClaimDirect(Policy $policy, array $overrides = []): Claim
    {
        $claim = new Claim();
        $claim->setPolicy($policy);
        $claim->setClaimAmount($overrides['claimAmount'] ?? 500.0);
        if (isset($overrides['description'])) {
            $claim->setDescription($overrides['description']);
        }
        if (isset($overrides['status'])) {
            $claim->setStatus($overrides['status']);
        }
        if (isset($overrides['processedAt'])) {
            $claim->setProcessedAt($overrides['processedAt']);
        }
        $this->em->persist($claim);
        $this->em->flush();
        // Refresh to ensure generated identifier is populated on the entity
        $this->em->refresh($claim);
        return $claim;
    }

    public function testCreatePolicySuccess(): void
    {
        $policy = $this->service->createPolicy(
            userId: 1,
            type: 'car',
            provider: 'AutoSafe',
            policyNumber: 'POL-' . bin2hex(random_bytes(4)),
            coverageAmount: 20000.0,
            premium: 200.0,
            status: 'active'
        );

        $this->assertInstanceOf(Policy::class, $policy);
        $this->assertNotNull($policy->getId());
        $this->assertSame('1', $policy->getUserId());
        $this->assertSame('car', $policy->getType());
        $this->assertSame('AutoSafe', $policy->getProvider());
        $this->assertSame(20000.0, $policy->getCoverageAmount());
        $this->assertSame(200.0, $policy->getPremium());
        $this->assertSame('active', $policy->getStatus());
    }

    public function testCreatePolicyFailsWhenPolicyNumberDuplicate(): void
    {
        $policyNumber = 'POL-DUP-' . bin2hex(random_bytes(4));
        $this->service->createPolicy(
            userId: 1,
            type: 'health',
            provider: 'A',
            policyNumber: $policyNumber,
            coverageAmount: 1000,
            premium: 50
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Policy number must be unique');
        $this->service->createPolicy(
            userId: 2,
            type: 'life',
            provider: 'B',
            policyNumber: $policyNumber,
            coverageAmount: 2000,
            premium: 100
        );
    }

    public function testCreatePolicyFailsWhenInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid policy type');
        $this->service->createPolicy(
            userId: 1,
            type: 'invalid_type',
            provider: 'ProviderX',
            policyNumber: 'POL-INVALID',
            coverageAmount: 1000,
            premium: 50
        );
    }

    public function testGetPolicyReturnsNullWhenNotFound(): void
    {
        $result = $this->service->getPolicy(99999);
        $this->assertNull($result);
    }

    public function testGetAllPolicies(): void
    {
        $this->service->createPolicy(
            userId: 1,
            type: 'health',
            provider: 'P1',
            policyNumber: 'POL-A',
            coverageAmount: 1000,
            premium: 50
        );
        $this->service->createPolicy(
            userId: 2,
            type: 'car',
            provider: 'P2',
            policyNumber: 'POL-B',
            coverageAmount: 2000,
            premium: 100
        );
        $all = $this->service->getAllPolicies();
        $this->assertCount(2, $all);
    }

    public function testGetUserPolicies(): void
    {
        $userId = 10;
        $this->service->createPolicy(
            userId: $userId,
            type: 'health',
            provider: 'H1',
            policyNumber: 'POL-U1',
            coverageAmount: 1000,
            premium: 50
        );
        $this->service->createPolicy(
            userId: $userId,
            type: 'car',
            provider: 'C1',
            policyNumber: 'POL-U2',
            coverageAmount: 2000,
            premium: 100
        );
        // Another user
        $this->service->createPolicy(
            userId: 20,
            type: 'property',
            provider: 'P1',
            policyNumber: 'POL-OTHER',
            coverageAmount: 5000,
            premium: 200
        );

        $userPolicies = $this->service->getUserPolicies($userId);
        $this->assertCount(2, $userPolicies);
        foreach ($userPolicies as $p) {
            $this->assertSame((string)$userId, $p->getUserId());
        }
    }

    public function testGetPoliciesByType(): void
    {
        $userId = 20;
        $this->service->createPolicy(userId: $userId, type: 'health', provider: 'H', policyNumber: 'POL-H1', coverageAmount: 1000, premium: 50);
        $this->service->createPolicy(userId: $userId, type: 'car', provider: 'C', policyNumber: 'POL-C1', coverageAmount: 2000, premium: 100);
        $this->service->createPolicy(userId: $userId, type: 'health', provider: 'H2', policyNumber: 'POL-H2', coverageAmount: 3000, premium: 150);

        $health = $this->service->getPoliciesByType($userId, 'health');
        $this->assertCount(2, $health);
        foreach ($health as $p) {
            $this->assertSame('health', $p->getType());
        }
    }

    public function testSearchPoliciesAppliesTypeFilter(): void
    {
        $this->service->createPolicy(userId: 1, type: 'health', provider: 'H', policyNumber: 'POL-1', coverageAmount: 1000, premium: 50);
        $this->service->createPolicy(userId: 1, type: 'car', provider: 'C', policyNumber: 'POL-2', coverageAmount: 2000, premium: 100);

        $results = $this->service->searchPolicies(1, ['type' => 'health']);
        $this->assertCount(1, $results);
        $this->assertSame('health', $results[0]->getType());
    }

    public function testSearchPoliciesAppliesProviderFilter(): void
    {
        $this->service->createPolicy(userId: 1, type: 'health', provider: 'Alpha', policyNumber: 'POL-A', coverageAmount: 1000, premium: 50);
        $this->service->createPolicy(userId: 1, type: 'health', provider: 'Beta', policyNumber: 'POL-B', coverageAmount: 1000, premium: 50);

        $results = $this->service->searchPolicies(1, ['provider' => 'Alpha']);
        $this->assertCount(1, $results);
        $this->assertSame('Alpha', $results[0]->getProvider());
    }

    public function testSearchPoliciesAppliesStatusFilter(): void
    {
        $this->service->createPolicy(userId: 1, type: 'health', provider: 'P', policyNumber: 'POL-A', coverageAmount: 1000, premium: 50, status: 'active');
        $this->service->createPolicy(userId: 1, type: 'health', provider: 'P', policyNumber: 'POL-B', coverageAmount: 1000, premium: 50, status: 'cancelled');

        $active = $this->service->searchPolicies(1, ['status' => 'active']);
        $this->assertCount(1, $active);
        $this->assertSame('active', $active[0]->getStatus());
    }

    public function testUpdatePolicyChangesFields(): void
    {
        $policy = $this->service->createPolicy(
            userId: 1,
            type: 'health',
            provider: 'Initial',
            policyNumber: 'POL-UPDATE',
            coverageAmount: 5000,
            premium: 100
        );

        $updated = $this->service->updatePolicy(
            $policy->getId(),
            [
                'coverage_amount' => 10000,
                'premium' => 200,
                'beneficiary' => 'John Doe',
                'status' => 'active',
            ],
            1
        );

        $this->assertNotNull($updated);
        $this->assertSame(10000.0, $updated->getCoverageAmount());
        $this->assertSame(200.0, $updated->getPremium());
        $this->assertSame('John Doe', $updated->getBeneficiary());
        $this->assertSame('active', $updated->getStatus());
    }

    public function testDeletePolicy(): void
    {
        $policy = $this->service->createPolicy(
            userId: 1,
            type: 'car',
            provider: 'Del',
            policyNumber: 'POL-DELETE',
            coverageAmount: 1000,
            premium: 50
        );
        $id = $policy->getId();

        $result = $this->service->deletePolicy($id, 1);
        $this->assertTrue($result);
        $deleted = $this->policyRepo->find($id);
        $this->assertNull($deleted);
    }

    public function testGetUserClaims(): void
    {
        $userId = 100;
        $policy = $this->service->createPolicy(
            userId: $userId,
            type: 'health',
            provider: 'HealthCo',
            policyNumber: 'POL-CLAIM-1',
            coverageAmount: 10000,
            premium: 500
        );
        $claim1 = $this->createClaimDirect($policy, ['description' => 'Claim 1']);
        $claim2 = $this->createClaimDirect($policy, ['description' => 'Claim 2']);

        // Another policy for different user
        $otherPolicy = $this->service->createPolicy(
            userId: 200,
            type: 'car',
            provider: 'AutoCo',
            policyNumber: 'POL-CLAIM-2',
            coverageAmount: 5000,
            premium: 200
        );
        $this->createClaimDirect($otherPolicy, ['description' => 'Other claim']);

        $claims = $this->service->getUserClaims($userId);
        $this->assertCount(2, $claims);
        foreach ($claims as $c) {
            $this->assertSame($policy->getId(), $c->getPolicy()->getId());
        }
    }

    public function testCountUserClaims(): void
    {
        $userId = 101;
        $policy = $this->service->createPolicy(
            userId: $userId,
            type: 'life',
            provider: 'LifeInc',
            policyNumber: 'POL-COUNT',
            coverageAmount: 50000,
            premium: 1000
        );
        $this->createClaimDirect($policy);
        $this->createClaimDirect($policy);

        $count = $this->service->countUserClaims($userId);
        $this->assertSame(2, $count);
    }

    public function testDeleteClaim(): void
    {
        $userId = 102;
        $policy = $this->service->createPolicy(
            userId: $userId,
            type: 'property',
            provider: 'PropCo',
            policyNumber: 'POL-DEL-CLAIM',
            coverageAmount: 20000,
            premium: 300
        );
        $claim = $this->createClaimDirect($policy, ['description' => 'Delete me']);
        // Fetch the persisted claim to obtain generated ID
        $savedClaim = $this->claimRepo->findByClaimNumber($claim->getClaimNumber());
        $claimId = $savedClaim->getId();

        $result = $this->service->deleteClaim($claimId, $userId);
        $this->assertTrue($result);
        $deleted = $this->claimRepo->find($claimId);
        $this->assertNull($deleted);
    }

    public function testCreatePolicyFailsWhenProviderEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider is required');
        $this->service->createPolicy(
            userId: 1,
            type: 'health',
            provider: '',
            policyNumber: 'POL-EMPTY',
            coverageAmount: 1000,
            premium: 50
        );
    }

    public function testCreatePolicyFailsWhenCoverageAmountInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Coverage amount must be positive');
        $this->service->createPolicy(
            userId: 1,
            type: 'health',
            provider: 'ProviderX',
            policyNumber: 'POL-NEG',
            coverageAmount: -1000,
            premium: 50
        );
    }

    public function testCreatePolicyFailsWhenPremiumInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Premium must be positive');
        $this->service->createPolicy(
            userId: 1,
            type: 'health',
            provider: 'ProviderX',
            policyNumber: 'POL-PREMIUM',
            coverageAmount: 1000,
            premium: -10
        );
    }

    public function testGetExpiringPolicies(): void
    {
        $userId = 300;
        $now = new \DateTime();
        $future = (clone $now)->modify('+10 days');
        $past = (clone $now)->modify('-10 days');

        // Active, expiring within 30 days
        $policy1 = $this->service->createPolicy(
            userId: $userId,
            type: 'health',
            provider: 'FutureInc',
            policyNumber: 'POL-FUTURE',
            coverageAmount: 1000,
            premium: 50,
            endDate: $future->format('Y-m-d H:i:s'),
            status: 'active'
        );
        // Expired (end date in past) but status active? Should be excluded if endDate < now? The query: `p.endDate BETWEEN NOW() AND :date`. So only future endDate within range. So this should not be included.
        $this->service->createPolicy(
            userId: $userId,
            type: 'health',
            provider: 'PastInc',
            policyNumber: 'POL-PAST',
            coverageAmount: 1000,
            premium: 50,
            endDate: $past->format('Y-m-d H:i:s'),
            status: 'active'
        );
        // Cancelled with future end date: should be excluded because status != active
        $this->service->createPolicy(
            userId: $userId,
            type: 'health',
            provider: 'CancelledInc',
            policyNumber: 'POL-CANCEL',
            coverageAmount: 1000,
            premium: 50,
            endDate: $future->format('Y-m-d H:i:s'),
            status: 'cancelled'
        );

        $expiring = $this->service->getExpiringPolicies($userId, 30);
        $this->assertCount(1, $expiring);
        $this->assertSame($policy1->getId(), $expiring[0]->getId());
    }

    public function testGetStatistics(): void
    {
        $userId = 400;
        $this->service->createPolicy(userId: $userId, type: 'health', provider: 'P1', policyNumber: 'POL-STAT-1', coverageAmount: 1000, premium: 50, status: 'active');
        $this->service->createPolicy(userId: $userId, type: 'car', provider: 'P2', policyNumber: 'POL-STAT-2', coverageAmount: 2000, premium: 100, status: 'cancelled');
        $this->service->createPolicy(userId: $userId, type: 'life', provider: 'P3', policyNumber: 'POL-STAT-3', coverageAmount: 5000, premium: 200, status: 'expired');

        $stats = $this->service->getStatistics($userId);
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertSame(3, $stats['total']);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('cancelled', $stats);
        $this->assertArrayHasKey('expired', $stats);
    }
}
