<?php

declare(strict_types=1);

namespace Tests\Unit\Insurance\Controller;

use App\Insurance\Controller\InsuranceController;
use App\Insurance\Service\InsuranceService;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class InsuranceControllerTest extends TestCase
{
    private InsuranceService $insuranceServiceMock;
    private InsuranceController $controller;

    protected function setUp(): void
    {
        $this->insuranceServiceMock = $this->createMock(InsuranceService::class);
        $this->controller = new InsuranceController($this->insuranceServiceMock);
        // Initialize container to avoid AbstractController errors
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function invokePrivate(object $obj, string $method, array $args = [])
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    // ==================== Auth & JSON ====================

    // ==================== Policy Validators ====================

    public function testValidatePolicyType_MissingType_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Policy type is required');
        $this->invokePrivate($this->controller, 'validatePolicyType', [['other' => 'data'], true]);
    }

    public function testValidatePolicyType_InvalidType_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid policy type');
        $this->invokePrivate($this->controller, 'validatePolicyType', [['type' => 'invalid'], true]);
    }

    public function testValidatePolicyType_ValidType_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validatePolicyType', [['type' => 'health'], true]);
        $this->assertTrue(true);
    }

    public function testValidatePolicyType_NotCreation_NoException(): void
    {
        // On update (isCreation false), validation is skipped
        $this->invokePrivate($this->controller, 'validatePolicyType', [['type' => 'invalid'], false]);
        $this->assertTrue(true);
    }

    // provider validator
    public function testValidateProvider_Missing_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider is required');
        $this->invokePrivate($this->controller, 'validateProvider', [['other' => 'data'], true]);
    }

    public function testValidateProvider_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateProvider', [['provider' => 'Acme Insurance'], true]);
        $this->assertTrue(true);
    }

    // policy number validator
    public function testValidatePolicyNumber_Missing_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Policy number is required');
        $this->invokePrivate($this->controller, 'validatePolicyNumber', [['other' => 'data'], true]);
    }

    public function testValidatePolicyNumber_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validatePolicyNumber', [['policy_number' => 'PN12345'], true]);
        $this->assertTrue(true);
    }

    // coverage amount validator
    public function testValidateCoverageAmount_Missing_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateCoverageAmount', [['other' => 'data']]);
        $this->assertTrue(true);
    }

    public function testValidateCoverageAmount_Negative_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Coverage amount must be positive');
        $this->invokePrivate($this->controller, 'validateCoverageAmount', [['coverage_amount' => -100]]);
    }

    public function testValidateCoverageAmount_Zero_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Coverage amount must be positive');
        $this->invokePrivate($this->controller, 'validateCoverageAmount', [['coverage_amount' => 0]]);
    }

    public function testValidateCoverageAmount_Positive_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateCoverageAmount', [['coverage_amount' => 1000]]);
        $this->assertTrue(true);
    }

    // premium validator
    public function testValidatePremium_Missing_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validatePremium', [['other' => 'data']]);
        $this->assertTrue(true);
    }

    public function testValidatePremium_Negative_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Premium must be positive');
        $this->invokePrivate($this->controller, 'validatePremium', [['premium' => -50]]);
    }

    // beneficiary validator
    public function testValidateBeneficiary_Missing_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateBeneficiary', [['other' => 'data']]);
        $this->assertTrue(true);
    }

    public function testValidateBeneficiary_TooLong_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Beneficiary name cannot exceed 255 characters');
        $this->invokePrivate($this->controller, 'validateBeneficiary', [['beneficiary' => str_repeat('a', 256)]]);
    }

    public function testValidateBeneficiary_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateBeneficiary', [['beneficiary' => 'John Doe']]);
        $this->assertTrue(true);
    }

    // end date validator
    public function testValidateEndDate_Missing_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateEndDate', [['other' => 'data']]);
        $this->assertTrue(true);
    }

    public function testValidateEndDate_Future_NoException(): void
    {
        $future = (new \DateTimeImmutable('+1 day'))->format('Y-m-d');
        $this->invokePrivate($this->controller, 'validateEndDate', [['end_date' => $future]]);
        $this->assertTrue(true);
    }

    public function testValidateEndDate_InvalidFormat_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid end date format');
        $this->invokePrivate($this->controller, 'validateEndDate', [['end_date' => 'not-a-date']]);
    }

    // Policy optional fields validator
    public function testValidatePolicyOptionalFields_AllValid_NoException(): void
    {
        $data = [
            'coverage_amount' => 1000,
            'premium' => 100,
            'beneficiary' => 'Jane Doe',
            'end_date' => (new \DateTimeImmutable('+1 year'))->format('Y-m-d')
        ];
        $this->invokePrivate($this->controller, 'validatePolicyOptionalFields', [$data]);
        $this->assertTrue(true);
    }

    public function testValidatePolicyOptionalFields_CoverageNegative_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Coverage amount must be positive');
        $data = ['coverage_amount' => -1];
        $this->invokePrivate($this->controller, 'validatePolicyOptionalFields', [$data]);
    }

    // ==================== Claim Validators ====================

    public function testValidateClaimPolicyId_Missing_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('policyId is required');
        $this->invokePrivate($this->controller, 'validateClaimPolicyId', [['other' => 'data']]);
    }

    public function testValidateClaimNumber_Missing_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('claimNumber is required');
        $this->invokePrivate($this->controller, 'validateClaimNumber', [['other' => 'data']]);
    }

    public function testValidateClaimAmount_Missing_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('claimAmount is required');
        $this->invokePrivate($this->controller, 'validateClaimAmount', [['other' => 'data']]);
    }

    public function testValidateClaimAmount_Zero_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('claimAmount must be positive');
        $this->invokePrivate($this->controller, 'validateClaimAmount', [['claimAmount' => 0]]);
    }

    public function testValidateClaimDescription_Empty_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Description is required');
        $this->invokePrivate($this->controller, 'validateClaimDescription', [['description' => '   ']]);
    }

    public function testValidateClaimStatus_Invalid_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid claim status');
        $this->invokePrivate($this->controller, 'validateClaimStatus', [['status' => 'unknown']]);
    }

    // ==================== Shared Int Validators ====================

    public function testValidatePositiveInt_BelowMin_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('value must be at least 10');
        $this->invokePrivate($this->controller, 'validatePositiveInt', [5, 'value', 10]);
    }

    public function testValidatePositiveInt_AboveMax_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('value must not exceed 100');
        $this->invokePrivate($this->controller, 'validatePositiveInt', [200, 'value', 1, 100]);
    }

    public function testValidatePositiveInt_Valid_ReturnsValue(): void
    {
        $result = $this->invokePrivate($this->controller, 'validatePositiveInt', [42, 'value', 1, 100]);
        $this->assertSame(42, $result);
    }

    // ==================== Endpoint Integration ====================

    public function testCreatePolicy_Success_Returns201(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $policyStub = new class {
            public function getId(): int { return 1; }
            public function setCoverageAmount(?float $c): void {}
            public function setPremium(?float $p): void {}
            public function setBeneficiary(?string $b): void {}
            public function setEndDate(?\DateTime $d): void {}
        };

        $this->insuranceServiceMock->expects($this->once())
            ->method('createPolicy')
            ->with($this->equalTo(123), $this->equalTo('health'), $this->equalTo('ProviderX'), $this->equalTo('PN123'))
            ->willReturn($policyStub);

        $data = [
            'type' => 'health',
            'provider' => 'ProviderX',
            'policy_number' => 'PN123',
            'coverage_amount' => 5000,
            'premium' => 200,
            'beneficiary' => 'Alice',
            'end_date' => (new \DateTimeImmutable('+1 year'))->format('Y-m-d')
        ];
        $request = Request::create('/api/insurance', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createPolicy($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('policy', $content);
    }

    public function testCreatePolicy_MissingRequired_Returns400(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $data = ['provider' => 'ProviderX', 'policy_number' => 'PN123']; // missing type
        $request = Request::create('/api/insurance', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createPolicy($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testGetPolicies_ReturnsList(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $policyStub1 = new class {
            public function toArray(): array { return ['id'=>1, 'type'=>'health']; }
        };
        $policyStub2 = new class {
            public function toArray(): array { return ['id'=>2, 'type'=>'life']; }
        };

        $this->insuranceServiceMock->expects($this->once())
            ->method('countUserPolicies')
            ->with($this->equalTo(123))
            ->willReturn(2);
        $this->insuranceServiceMock->expects($this->once())
            ->method('getUserPolicies')
            ->with($this->equalTo(123), $this->equalTo(50), $this->equalTo(0))
            ->willReturn([$policyStub1, $policyStub2]);

        $request = Request::create('/api/insurance/policies', 'GET', ['limit' => '50', 'offset' => '0']);
        $response = $this->controller->getPolicies($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('policies', $content);
        $this->assertCount(2, $content['policies']);
    }

    // ==================== Policy Additional Endpoints ====================

    public function testGetPolicy_NotFound_Returns404(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $this->insuranceServiceMock->method('getPolicy')->with(1)->willReturn(null);
        $request = new Request();
        $response = $this->controller->getPolicy(1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetPolicy_Success_Returns200(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $policy = new class { public function getId(): int { return 1; } public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->method('getPolicy')->with(1)->willReturn($policy);
        $request = new Request();
        $response = $this->controller->getPolicy(1);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdatePolicy_NotFound_Returns404(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $this->insuranceServiceMock->method('getPolicy')->with(1)->willReturn(null);
        $request = Request::create('/api/insurance/policies/1', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['type' => 'health']));
        $response = $this->controller->updatePolicy(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdatePolicy_Success_Returns200(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $existing = new class { public function getId(): int { return 1; } public function getUserId(): int { return 123; } };
        $updated = new class { public function getId(): int { return 1; } public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->method('getPolicy')->with(1)->willReturn($existing);
        $this->insuranceServiceMock->method('updatePolicy')->with(1, $this->anything())->willReturn($updated);
        $request = Request::create('/api/insurance/policies/1', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['type' => 'health']));
        $response = $this->controller->updatePolicy(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDeletePolicy_Success_Returns204(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $policy = new class { public function getId(): int { return 1; } public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->method('getPolicy')->with(1)->willReturn($policy);
        $this->insuranceServiceMock->method('deletePolicy')->with(1)->willReturn(true);
        $request = new Request();
        $response = $this->controller->deletePolicy(1);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testGetUserPolicies_AccessDenied_Returns403(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $this->insuranceServiceMock->expects($this->never())->method('getUserPolicies');
        $request = new Request();
        $response = $this->controller->getUserPolicies(456);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetUserPolicies_Success_Returns200(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $policies = [new class { public function getId(): int { return 1; } }];
        $this->insuranceServiceMock->method('getUserPolicies')->with(123)->willReturn($policies);
        $request = new Request();
        $response = $this->controller->getUserPolicies(123);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== Claim Endpoints ====================

    public function testCreateClaim_MissingRequired_Returns400(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $data = []; // missing all required
        $request = Request::create('/api/insurance/claims', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createClaim($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateClaim_InvalidPolicy_Returns403(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        // Policy exists but belongs to another user
        $policy = new class { public function getUserId(): int { return 456; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);

        $data = [
            'policyId' => 1,
            'claimNumber' => 'CLM123',
            'description' => 'Test claim',
            'claimAmount' => 1000
        ];
        $request = Request::create('/api/insurance/claims', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createClaim($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCreateClaim_Success_Returns201(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $policy = new class { public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);

        $claim = new class {
            public function getId(): int { return 1; }
            public function getClaimNumber(): string { return 'CLM123'; }
            public function getPolicy() { return new class { public function getId(): int { return 1; } }; }
            public function getClaimAmount(): float { return 1000.0; }
            public function getStatus(): string { return 'pending'; }
            public function getDescription(): string { return 'Test claim'; }
            public function getCreatedAt() { return new \DateTime(); }
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('createClaim')
            ->with('1', 'CLM123', 'Test claim', 1000.0, 'pending')
            ->willReturn($claim);

        $data = [
            'policyId' => 1,
            'claimNumber' => 'CLM123',
            'description' => 'Test claim',
            'claimAmount' => 1000
        ];
        $request = Request::create('/api/insurance/claims', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createClaim($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('claim', $content);
    }

    public function testGetClaim_InvalidId_Returns400(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $request = Request::create('/api/insurance/claims/0', 'GET');
        $response = $this->controller->getClaim(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetClaim_NotFound_Returns404(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $this->insuranceServiceMock->expects($this->once())
            ->method('getClaim')
            ->with(1)
            ->willReturn(null);
        $request = new Request();
        $response = $this->controller->getClaim(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetClaim_AccessDenied_Returns403(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $claim = new class { 
            public function getPolicy() { return new class { public function getId(): int { return 1; } }; } 
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getClaim')
            ->with(1)
            ->willReturn($claim);
        // Policy belongs to another user
        $policy = new class { public function getUserId(): int { return 456; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);

        $request = new Request();
        $response = $this->controller->getClaim(1, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetClaim_Success_Returns200(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $claim = new class {
            public function getPolicy() { return new class { public function getId(): int { return 1; } }; }
            public function getId(): int { return 1; }
            public function getClaimNumber(): string { return 'CLM123'; }
            public function getClaimAmount(): float { return 1000.0; }
            public function getStatus(): string { return 'pending'; }
            public function getDescription(): string { return 'Test claim'; }
            public function getCreatedAt() { return new \DateTime(); }
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getClaim')
            ->with(1)
            ->willReturn($claim);
        $policy = new class { public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);

        $request = new Request();
        $response = $this->controller->getClaim(1, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('claim', $content);
    }

    public function testGetPolicyClaims_InvalidPolicyId_Returns400(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $request = Request::create('/api/insurance/policies/0/claims', 'GET');
        $response = $this->controller->getPolicyClaims(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetPolicyClaims_AccessDenied_Returns403(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $policy = new class { public function getUserId(): int { return 456; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);

        $request = Request::create('/api/insurance/policies/1/claims', 'GET');
        $response = $this->controller->getPolicyClaims(1, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetPolicyClaims_Success_Returns200(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $policy = new class { public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);

        $claim1 = new class {
            public function getId(): int { return 1; }
            public function getClaimNumber(): string { return 'CLM1'; }
            public function getClaimAmount(): float { return 500.0; }
            public function getStatus(): string { return 'approved'; }
            public function getDescription(): string { return 'Desc1'; }
            public function getCreatedAt() { return new \DateTime(); }
        };
        $claim2 = new class {
            public function getId(): int { return 2; }
            public function getClaimNumber(): string { return 'CLM2'; }
            public function getClaimAmount(): float { return 300.0; }
            public function getStatus(): string { return 'pending'; }
            public function getDescription(): string { return 'Desc2'; }
            public function getCreatedAt() { return new \DateTime(); }
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('countPolicyClaims')
            ->with('1')
            ->willReturn(2);
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicyClaims')
            ->with('1', 50, 0)
            ->willReturn([$claim1, $claim2]);

        $request = Request::create('/api/insurance/policies/1/claims', 'GET', ['limit' => '50', 'offset' => '0']);
        $response = $this->controller->getPolicyClaims(1, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('claims', $content);
        $this->assertCount(2, $content['claims']);
        $this->assertArrayHasKey('meta', $content);
    }

    public function testUpdateClaimStatus_InvalidId_Returns400(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $request = Request::create('/api/insurance/claims/0/status', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['status' => 'approved']));
        $response = $this->controller->updateClaimStatus(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateClaimStatus_NotFound_Returns404(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $this->insuranceServiceMock->expects($this->once())
            ->method('getClaim')
            ->with(1)
            ->willReturn(null);
        $request = Request::create('/api/insurance/claims/1/status', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['status' => 'approved']));
        $response = $this->controller->updateClaimStatus(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateClaimStatus_AccessDenied_Returns403(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $claim = new class { 
            public function getPolicy() { return new class { public function getId(): int { return 1; } }; } 
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getClaim')
            ->with(1)
            ->willReturn($claim);
        $policy = new class { public function getUserId(): int { return 456; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);

        $request = Request::create('/api/insurance/claims/1/status', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['status' => 'approved']));
        $response = $this->controller->updateClaimStatus(1, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUpdateClaimStatus_InvalidStatus_Returns400(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $claim = new class { 
            public function getPolicy() { return new class { public function getId(): int { return 1; } }; } 
            public function getId(): int { return 1; }
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getClaim')
            ->with(1)
            ->willReturn($claim);
        $policy = new class { public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);
        $this->insuranceServiceMock->expects($this->never())
            ->method('updateClaimStatus');

        $request = Request::create('/api/insurance/claims/1/status', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['status' => 'invalid']));
        $response = $this->controller->updateClaimStatus(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateClaimStatus_Success_Returns200(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $claim = new class { 
            public function getPolicy() { return new class { public function getId(): int { return 1; } }; } 
            public function getId(): int { return 1; }
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getClaim')
            ->with(1)
            ->willReturn($claim);
        $policy = new class { public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);
        $updatedClaim = new class {
            public function getId(): int { return 1; }
            public function getStatus(): string { return 'approved'; }
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('updateClaimStatus')
            ->with(1, 'approved')
            ->willReturn($updatedClaim);

        $request = Request::create('/api/insurance/claims/1/status', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['status' => 'approved']));
        $response = $this->controller->updateClaimStatus(1, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('claim', $content);
        $this->assertSame('approved', $content['claim']['status']);
    }

    public function testDeleteClaim_InvalidId_Returns400(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $request = Request::create('/api/insurance/claims/0', 'DELETE');
        $response = $this->controller->deleteClaim(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testDeleteClaim_NotFound_Returns404(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $this->insuranceServiceMock->expects($this->once())
            ->method('getClaim')
            ->with(1)
            ->willReturn(null);
        $request = Request::create('/api/insurance/claims/1', 'DELETE');
        $response = $this->controller->deleteClaim(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDeleteClaim_AccessDenied_Returns403(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $claim = new class { 
            public function getPolicy() { return new class { public function getId(): int { return 1; } }; } 
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getClaim')
            ->with(1)
            ->willReturn($claim);
        $policy = new class { public function getUserId(): int { return 456; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);

        $request = Request::create('/api/insurance/claims/1', 'DELETE');
        $response = $this->controller->deleteClaim(1, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeleteClaim_Success_Returns200(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);

        $claim = new class { 
            public function getPolicy() { return new class { public function getId(): int { return 1; } }; } 
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getClaim')
            ->with(1)
            ->willReturn($claim);
        $policy = new class { public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn($policy);
        $this->insuranceServiceMock->expects($this->once())
            ->method('deleteClaim')
            ->with(1)
            ->willReturn(true);

        $request = Request::create('/api/insurance/claims/1', 'DELETE');
        $response = $this->controller->deleteClaim(1, $request);
        $this->assertSame(204, $response->getStatusCode());
    }

    // ==================== Renew Policy Endpoints ====================

    public function testRenewPolicy_InvalidId_Returns400(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $request = Request::create('/api/insurance/policies/0/renew', 'POST');
        $response = $this->controller->renewPolicy(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRenewPolicy_NotFound_Returns404(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(1)
            ->willReturn(null);
        $request = Request::create('/api/insurance/policies/1/renew', 'POST');
        $response = $this->controller->renewPolicy(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRenewPolicy_AccessDenied_Returns403(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $policy = new class { public function getUserId(): int { return 456; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(2)
            ->willReturn($policy);
        $request = Request::create('/api/insurance/policies/2/renew', 'POST');
        $response = $this->controller->renewPolicy(2, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testRenewPolicy_InvalidMonths_Returns400(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $policy = new class { public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(3)
            ->willReturn($policy);
        $this->insuranceServiceMock->expects($this->never())->method('renewPolicy');
        $data = ['months' => 0];
        $request = Request::create('/api/insurance/policies/3/renew', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->renewPolicy(3, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRenewPolicy_Success_DefaultMonths_Returns200(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $policy = new class { public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(4)
            ->willReturn($policy);
        $renewed = new class {
            public function getId(): int { return 4; }
            public function getPremium(): float { return 200.0; }
        };
        $this->insuranceServiceMock->expects($this->once())
            ->method('renewPolicy')
            ->with(4, 12)
            ->willReturn($renewed);
        $request = Request::create('/api/insurance/policies/4/renew', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $response = $this->controller->renewPolicy(4, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('policy', $content);
    }

    public function testRenewPolicy_Success_CustomMonths_Returns200(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');
        $this->controller->setTestUser($user);
        $policy = new class { public function getUserId(): int { return 123; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('getPolicy')
            ->with(5)
            ->willReturn($policy);
        $renewed = new class { public function getId(): int { return 5; } };
        $this->insuranceServiceMock->expects($this->once())
            ->method('renewPolicy')
            ->with(5, 24)
            ->willReturn($renewed);
        $data = ['months' => 24];
        $request = Request::create('/api/insurance/policies/5/renew', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->renewPolicy(5, $request);
        $this->assertSame(200, $response->getStatusCode());
    }
}

