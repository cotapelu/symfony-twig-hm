<?php declare(strict_types=1);

namespace App\Tests\Unit\Insurance\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Insurance\Service\InsuranceService;
use App\Insurance\Repository\PolicyRepository;
use App\Insurance\Repository\ClaimRepository;
use App\Insurance\Entity\Policy;
use App\Insurance\Entity\Claim;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class InsuranceServiceTest extends TestCase
{
    private PolicyRepository&MockObject $policyRepository;
    private ClaimRepository&MockObject $claimRepository;
    private EntityManagerInterface&MockObject $em;
    private InsuranceService $service;

    protected function setUp(): void
    {
        $this->policyRepository = $this->createMock(PolicyRepository::class);
        $this->claimRepository = $this->createMock(ClaimRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new InsuranceService($this->policyRepository, $this->claimRepository, $this->em);
    }

    // ========== POLICY CREATION ==========

    public function testCreatePolicyThrowsForInvalidUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be positive');
        $this->service->createPolicy(0, 'health', 'Provider1', 'POL123');
    }

    public function testCreatePolicyThrowsForEmptyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Policy type is required');
        $this->service->createPolicy(1, '', 'Provider1', 'POL123');
    }

    public function testCreatePolicyThrowsForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid policy type');
        $this->service->createPolicy(1, 'invalid', 'Provider1', 'POL123');
    }

    public function testCreatePolicyThrowsForEmptyProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider is required');
        $this->service->createPolicy(1, 'health', '', 'POL123');
    }

    public function testCreatePolicyThrowsForEmptyPolicyNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Policy number is required');
        $this->service->createPolicy(1, 'health', 'Provider1', '');
    }

    public function testCreatePolicyThrowsForDuplicatePolicyNumber(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('findByPolicyNumber')
            ->with('POL123')
            ->willReturn($this->createMock(Policy::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Policy number must be unique');
        $this->service->createPolicy(1, 'health', 'Provider1', 'POL123');
    }

    public function testCreatePolicyThrowsForNegativeCoverageAmount(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('findByPolicyNumber')
            ->with('POL123')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Coverage amount must be positive');
        $this->service->createPolicy(1, 'health', 'Provider1', 'POL123', -1000);
    }

    public function testCreatePolicyThrowsForNegativePremium(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('findByPolicyNumber')
            ->with('POL123')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Premium must be positive');
        $this->service->createPolicy(1, 'health', 'Provider1', 'POL123', 1000, -50);
    }

    public function testCreatePolicyThrowsForLongBeneficiary(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('findByPolicyNumber')
            ->with('POL123')
            ->willReturn(null);

        $longName = str_repeat('a', 256);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Beneficiary name cannot exceed 255 characters');
        $this->service->createPolicy(1, 'health', 'Provider1', 'POL123', 1000, 50, $longName);
    }

    public function testCreatePolicyThrowsForInvalidStartDate(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('findByPolicyNumber')
            ->with('POL123')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid start date format');
        $this->service->createPolicy(1, 'health', 'Provider1', 'POL123', 1000, 50, 'Beneficiary', 'invalid-date');
    }

    public function testCreatePolicySuccess(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('findByPolicyNumber')
            ->with('POL123')
            ->willReturn(null);
        $this->policyRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Policy $p) {
                return $p->getUserId() === '1'
                    && $p->getType() === 'health'
                    && $p->getProvider() === 'Provider1'
                    && $p->getPolicyNumber() === 'POL123'
                    && $p->getCoverageAmount() === 1000.0
                    && $p->getPremium() === 50.0
                    && $p->getBeneficiary() === htmlspecialchars('Beneficiary', ENT_QUOTES, 'UTF-8')
                    && $p->getStatus() === 'active';
            }));

        $policy = $this->service->createPolicy(1, 'health', 'Provider1', 'POL123', 1000.0, 50.0, 'Beneficiary', null, null, 'active');
        $this->assertInstanceOf(Policy::class, $policy);
    }

    // ========== GET POLICY ==========

    public function testGetPolicyReturnsNullWhenNotFound(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getPolicy(999);
        $this->assertNull($result);
    }

    public function testGetPolicyReturnsPolicyWhenFound(): void
    {
        $policy = $this->createMock(Policy::class);
        $policy->method('getUserId')->willReturn('5');

        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);

        $result = $this->service->getPolicy(123);
        $this->assertSame($policy, $result);
    }

    public function testGetPolicyThrowsAccessDeniedWhenUserIdMismatch(): void
    {
        $policy = $this->createMock(Policy::class);
        $policy->method('getUserId')->willReturn('10');

        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->getPolicy(123, 5);
    }

    // ========== GET ALL POLICIES ==========

    public function testGetAllPoliciesReturnsAll(): void
    {
        $policies = [$this->createMock(Policy::class), $this->createMock(Policy::class)];
        $this->policyRepository->expects($this->once())
            ->method('findAll')
            ->with(50, 0)
            ->willReturn($policies);

        $result = $this->service->getAllPolicies();
        $this->assertSame($policies, $result);
    }

    public function testCountAllPoliciesReturnsCount(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('countAll')
            ->willReturn(42);

        $result = $this->service->countAllPolicies();
        $this->assertSame(42, $result);
    }

    // ========== USER POLICIES ==========

    public function testGetUserPoliciesReturnsPoliciesForUser(): void
    {
        $policies = [$this->createMock(Policy::class)];
        $this->policyRepository->expects($this->once())
            ->method('findByUser')
            ->with('5', 50, 0)
            ->willReturn($policies);

        $result = $this->service->getUserPolicies(5);
        $this->assertSame($policies, $result);
    }

    public function testCountUserPoliciesReturnsCount(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('countByUser')
            ->with('5')
            ->willReturn(10);

        $result = $this->service->countUserPolicies(5);
        $this->assertSame(10, $result);
    }

    // ========== POLICIES BY TYPE ==========

    public function testGetPoliciesByTypeFiltersCorrectly(): void
    {
        $policies = [$this->createMock(Policy::class)];
        $this->policyRepository->expects($this->once())
            ->method('findByUserAndType')
            ->with('5', 'health', 50, 0)
            ->willReturn($policies);

        $result = $this->service->getPoliciesByType(5, 'health');
        $this->assertSame($policies, $result);
    }

    public function testCountPoliciesByTypeReturnsCount(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('countByUserAndType')
            ->with('5', 'car')
            ->willReturn(3);

        $result = $this->service->countPoliciesByType(5, 'car');
        $this->assertSame(3, $result);
    }

    // ========== USER CLAIMS ==========

    public function testGetUserClaimsReturnsClaimsForUser(): void
    {
        $claims = [$this->createMock(Claim::class)];
        $this->claimRepository->expects($this->once())
            ->method('findByUserId')
            ->with('5', 50, 0)
            ->willReturn($claims);

        $result = $this->service->getUserClaims(5);
        $this->assertSame($claims, $result);
    }

    public function testCountUserClaimsReturnsCount(): void
    {
        $this->claimRepository->expects($this->once())
            ->method('countByUserId')
            ->with('5')
            ->willReturn(5);

        $result = $this->service->countUserClaims(5);
        $this->assertSame(5, $result);
    }

    // ========== EXPIRING POLICIES ==========
    // Skipped: requires QueryBuilder mocking; covered by integration tests.

    public function testGetExpiringPoliciesReturnsActivePoliciesWithinDays(): void
    {
        $userId = 123;
        $days = 30;

        // Create mocks for QueryBuilder and Query
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // Capture setParameter calls
        $params = [];
        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$params, $qb) {
                $params[$name] = $value;
                return $qb;
            });

        // Capture where and andWhere predicates
        $wherePredicates = [];
        $andWherePredicates = [];
        $qb->expects($this->any())
            ->method('where')
            ->willReturnCallback(function ($predicate) use (&$wherePredicates, $qb) {
                $wherePredicates[] = $predicate;
                return $qb;
            });
        $qb->expects($this->any())
            ->method('andWhere')
            ->willReturnCallback(function ($predicate) use (&$andWherePredicates, $qb) {
                $andWherePredicates[] = $predicate;
                return $qb;
            });

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.endDate', 'ASC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        // Repository createQueryBuilder
        $this->policyRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->service->getExpiringPolicies($userId, $days);

        $this->assertIsArray($result);
        $this->assertSame($userId, $params['uid']);
        $this->assertSame('active', $params['status']);
        $this->assertInstanceOf(\DateTime::class, $params['date']);
        // Verify that the date is $days days from now
        $expectedDate = new \DateTime();
        $expectedDate->modify("+$days days");
        $this->assertEquals($expectedDate->format('Y-m-d'), $params['date']->format('Y-m-d'));
        // Verify where/andWhere predicates
        $this->assertStringContainsString('p.userId', $wherePredicates[0]);
        $this->assertStringContainsString('p.status', $andWherePredicates[0]);
        $this->assertStringContainsString('p.endDate', $andWherePredicates[1]);
    }

    // ========== SEARCH POLICIES ==========

    public function testSearchPoliciesAppliesUserIdFilter(): void
    {
        $userId = 5;
        $filters = [];

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $andWherePredicates = [];
        $setParams = [];
        $qb->expects($this->any())
            ->method('andWhere')
            ->willReturnCallback(function ($predicate) use (&$andWherePredicates, $qb) {
                $andWherePredicates[] = $predicate;
                return $qb;
            });
        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParams, $qb) {
                $setParams[$name] = $value;
                return $qb;
            });
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.createdAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->policyRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->service->searchPolicies($userId, $filters);

        $this->assertIsArray($result);
        $this->assertCount(1, $andWherePredicates);
        $this->assertStringContainsString('p.userId = :uid', $andWherePredicates[0]);
        $this->assertSame($userId, $setParams['uid']);
    }

    public function testSearchPoliciesThrowsForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid policy type filter');

        // Mock repository to avoid null errors on createQueryBuilder and chaining
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $this->policyRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->service->searchPolicies(5, ['type' => 'invalid']);
    }

    public function testSearchPoliciesThrowsForInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status filter');

        // Mock repository to avoid null errors on createQueryBuilder and chaining
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $this->policyRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->service->searchPolicies(5, ['status' => 'invalid']);
    }

    public function testSearchPoliciesAppliesTypeAndProviderAndStatusFilters(): void
    {
        $userId = 5;
        $filters = [
            'type' => 'health',
            'provider' => 'ProviderA',
            'status' => 'active',
        ];

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $andWherePredicates = [];
        $setParams = [];
        $qb->expects($this->any())
            ->method('andWhere')
            ->willReturnCallback(function ($predicate) use (&$andWherePredicates, $qb) {
                $andWherePredicates[] = $predicate;
                return $qb;
            });
        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParams, $qb) {
                $setParams[$name] = $value;
                return $qb;
            });
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.createdAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->policyRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->service->searchPolicies($userId, $filters);

        $this->assertIsArray($result);
        // First andWhere should be userId, then type, provider, status
        $this->assertGreaterThanOrEqual(1, count($andWherePredicates));
        $this->assertStringContainsString('p.userId = :uid', $andWherePredicates[0]);
        // Find and verify type, provider, status predicates
        $predicateStr = implode(' ', $andWherePredicates);
        $this->assertStringContainsString('p.type = :type', $predicateStr);
        $this->assertStringContainsString('p.provider = :provider', $predicateStr);
        $this->assertStringContainsString('p.status = :status', $predicateStr);
        $this->assertSame($userId, $setParams['uid']);
        $this->assertSame('health', $setParams['type']);
        $this->assertSame('ProviderA', $setParams['provider']);
        $this->assertSame('active', $setParams['status']);
    }

    // ========== UPDATE POLICY ==========

    public function testUpdatePolicyReturnsNullWhenNotFound(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->updatePolicy(999, ['coverage_amount' => 2000]);
        $this->assertNull($result);
    }

    public function testUpdatePolicyThrowsForNegativeCoverage(): void
    {
        $policy = $this->createMock(Policy::class);
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Coverage amount must be positive');
        $this->service->updatePolicy(123, ['coverage_amount' => -100]);
    }

    public function testUpdatePolicyThrowsForNegativePremium(): void
    {
        $policy = $this->createMock(Policy::class);
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Premium must be positive');
        $this->service->updatePolicy(123, ['premium' => -50]);
    }

    public function testUpdatePolicyThrowsForLongBeneficiary(): void
    {
        $policy = $this->createMock(Policy::class);
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);

        $longName = str_repeat('a', 256);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Beneficiary name cannot exceed 255 characters');
        $this->service->updatePolicy(123, ['beneficiary' => $longName]);
    }

    public function testUpdatePolicyThrowsForInvalidEndDate(): void
    {
        $policy = $this->createMock(Policy::class);
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid end date format');
        $this->service->updatePolicy(123, ['end_date' => 'bad-date']);
    }

    public function testUpdatePolicyThrowsForInvalidStatus(): void
    {
        $policy = $this->createMock(Policy::class);
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status');
        $this->service->updatePolicy(123, ['status' => 'invalid']);
    }

    public function testUpdatePolicySavesChanges(): void
    {
        $policy = $this->createMock(Policy::class);
        $policy->expects($this->once())->method('setCoverageAmount')->with(2000.0)->willReturnSelf();
        $policy->expects($this->once())->method('setPremium')->with(100.0)->willReturnSelf();
        $policy->expects($this->once())->method('setBeneficiary')->with($this->callback(function($v) {
            return htmlspecialchars('New Beneficiary', ENT_QUOTES, 'UTF-8') === $v;
        }))->willReturnSelf();
        $policy->expects($this->once())->method('setEndDate')->with($this->isInstanceOf(\DateTime::class))->willReturnSelf();
        $policy->expects($this->once())->method('setStatus')->with('cancelled')->willReturnSelf();

        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);
        $this->policyRepository->expects($this->once())
            ->method('save')
            ->with($policy);

        $result = $this->service->updatePolicy(123, [
            'coverage_amount' => 2000,
            'premium' => 100,
            'beneficiary' => 'New Beneficiary',
            'end_date' => '2026-12-31',
            'status' => 'cancelled'
        ]);
        $this->assertSame($policy, $result);
    }

    // ========== DELETE POLICY ==========

    public function testDeletePolicyReturnsFalseWhenNotFound(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->deletePolicy(999);
        $this->assertFalse($result);
    }

    public function testDeletePolicyRemovesPolicy(): void
    {
        $policyId = 123;
        $policy = $this->createMock(Policy::class);

        // Mock repository to return the policy
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with($policyId)
            ->willReturn($policy);

        // Expect remove and flush on entity manager
        $this->em->expects($this->once())
            ->method('remove')
            ->with($policy)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $result = $this->service->deletePolicy($policyId);
        $this->assertTrue($result);
    }

    // ========== ACTIVATE/ CANCEL/ RENEW ==========

    public function testActivatePolicyReturnsNullWhenNotFound(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $result = $this->service->activatePolicy(123);
        $this->assertNull($result);
    }

    public function testActivatePolicySetsStatusActive(): void
    {
        $policy = $this->createMock(Policy::class);
        $policy->expects($this->once())->method('setStatus')->with('active')->willReturnSelf();

        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);
        $this->policyRepository->expects($this->once())
            ->method('save')
            ->with($policy);

        $result = $this->service->activatePolicy(123);
        $this->assertSame($policy, $result);
    }

    public function testCancelPolicyReturnsNullWhenNotFound(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $result = $this->service->cancelPolicy(123);
        $this->assertNull($result);
    }

    public function testCancelPolicySetsStatusCancelled(): void
    {
        $policy = $this->createMock(Policy::class);
        $policy->expects($this->once())->method('setStatus')->with('cancelled')->willReturnSelf();

        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);
        $this->policyRepository->expects($this->once())
            ->method('save')
            ->with($policy);

        $result = $this->service->cancelPolicy(123);
        $this->assertSame($policy, $result);
    }

    public function testRenewPolicyReturnsNullWhenNotFound(): void
    {
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $result = $this->service->renewPolicy(123, 12);
        $this->assertNull($result);
    }

    public function testRenewPolicyThrowsIfNoEndDate(): void
    {
        $policy = $this->createMock(Policy::class);
        $policy->method('getEndDate')->willReturn(null);

        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Policy has no end date to renew');
        $this->service->renewPolicy(123, 12);
    }

    public function testRenewPolicyExtendsEndDateAndActivates(): void
    {
        $originalEnd = new \DateTime('2024-01-01');
        $policy = $this->createMock(Policy::class);
        $policy->method('getEndDate')->willReturn($originalEnd);
        $policy->expects($this->once())->method('setEndDate')->with($this->callback(function($date) {
            return $date instanceof \DateTime && $date->format('Y-m-d') === '2025-01-01';
        }))->willReturnSelf();
        $policy->expects($this->once())->method('setStatus')->with('active')->willReturnSelf();

        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($policy);
        $this->policyRepository->expects($this->once())
            ->method('save')
            ->with($policy);

        $result = $this->service->renewPolicy(123, 12);
        $this->assertSame($policy, $result);
    }

    // ========== CLAIMS ==========

    public function testCreateClaimThrowsForInvalidAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Claim amount must be positive');
        $this->service->createClaim('1', 'CLM123', 'Description', -100);
    }

    public function testCreateClaimThrowsForDuplicateClaimNumber(): void
    {
        $this->claimRepository->expects($this->once())
            ->method('findByClaimNumber')
            ->with('CLM123')
            ->willReturn($this->createMock(Claim::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Claim number must be unique');
        $this->service->createClaim('1', 'CLM123', 'Description', 100);
    }

    public function testCreateClaimThrowsForPolicyNotFound(): void
    {
        $this->claimRepository->expects($this->once())
            ->method('findByClaimNumber')
            ->with('CLM123')
            ->willReturn(null);
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Policy not found');
        $this->service->createClaim('1', 'CLM123', 'Description', 100);
    }

    public function testCreateClaimSuccess(): void
    {
        $policy = $this->createMock(Policy::class);
        $policy->method('getUserId')->willReturn('1');

        $this->claimRepository->expects($this->once())
            ->method('findByClaimNumber')
            ->with('CLM123')
            ->willReturn(null);
        $this->policyRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($policy);
        $this->claimRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Claim $c) use ($policy) {
                return $c->getPolicy() === $policy
                    && $c->getClaimNumber() === 'CLM123'
                    && $c->getDescription() === 'Description'
                    && $c->getClaimAmount() === 100.0
                    && $c->getStatus() === 'pending';
            }));

        $claim = $this->service->createClaim('1', 'CLM123', 'Description', 100.0, 'pending');
        $this->assertInstanceOf(Claim::class, $claim);
    }

    public function testGetClaimReturnsNullWhenNotFound(): void
    {
        $this->claimRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getClaim(999);
        $this->assertNull($result);
    }

    public function testGetClaimThrowsAccessDenied(): void
    {
        $policy = $this->createMock(Policy::class);
        $policy->method('getUserId')->willReturn('10');

        $claim = $this->createMock(Claim::class);
        $claim->method('getPolicy')->willReturn($policy);

        $this->claimRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($claim);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->getClaim(123, 5);
    }

    public function testGetPolicyClaimsReturnsClaimsForPolicy(): void
    {
        $claims = [$this->createMock(Claim::class)];
        $this->claimRepository->expects($this->once())
            ->method('findByPolicy')
            ->with('POL123', 50, 0)
            ->willReturn($claims);

        $result = $this->service->getPolicyClaims('POL123');
        $this->assertSame($claims, $result);
    }

    public function testCountPolicyClaimsReturnsCount(): void
    {
        $this->claimRepository->expects($this->once())
            ->method('countByPolicy')
            ->with('POL123')
            ->willReturn(5);

        $result = $this->service->countPolicyClaims('POL123');
        $this->assertSame(5, $result);
    }

    public function testUpdateClaimStatusReturnsNullWhenNotFound(): void
    {
        $this->claimRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->updateClaimStatus(999, 'approved');
        $this->assertNull($result);
    }

    public function testUpdateClaimStatusThrowsForInvalidStatus(): void
    {
        $claim = $this->createMock(Claim::class);
        $this->claimRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($claim);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid claim status');
        $this->service->updateClaimStatus(123, 'invalid');
    }

    public function testUpdateClaimStatusSavesStatus(): void
    {
        $claim = $this->createMock(Claim::class);
        $claim->expects($this->once())->method('setStatus')->with('approved')->willReturnSelf();

        $this->claimRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($claim);
        $this->claimRepository->expects($this->once())
            ->method('save')
            ->with($claim);

        $result = $this->service->updateClaimStatus(123, 'approved');
        $this->assertSame($claim, $result);
    }

    public function testDeleteClaimReturnsFalseWhenNotFound(): void
    {
        $this->claimRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->deleteClaim(999);
        $this->assertFalse($result);
    }

    public function testDeleteClaimRemovesClaim(): void
    {
        $claimId = 456;
        $claim = $this->createMock(Claim::class);

        $this->claimRepository->expects($this->once())
            ->method('find')
            ->with($claimId)
            ->willReturn($claim);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($claim)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $result = $this->service->deleteClaim($claimId);
        $this->assertTrue($result);
    }

}
