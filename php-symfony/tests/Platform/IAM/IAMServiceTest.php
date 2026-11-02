<?php

declare(strict_types=1);

namespace Platform\IAM;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use Platform\IAM\Exception\IAMException;
use Platform\IAM\Model\User;

/**
 * @covers \Platform\IAM\IAMService
 */
#[AllowMockObjectsWithoutExpectations]
class IAMServiceTest extends TestCase
{
    public function testValidateCredentialsValid(): void
    {
        // Create a mock authService and rbac for testing
        $authServiceMock = $this->createMock(\Platform\IAM\AuthServiceInterface::class);
        $rbacMock = $this->createMock(\Platform\IAM\RBAC\RoleBasedAccessControl::class);
        
        $iamService = new \Platform\IAM\IAMService($authServiceMock, $rbacMock);
        
        $password = 'password123';
        $this->assertTrue($iamService->validateCredentials($password));
    }

    public function testValidateCredentialsTooShort(): void
    {
        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.validateCredentials - Password too short (min 8 chars)');
        $this->expectExceptionCode(400);

        // Create a mock authService and rbac for testing
        $authServiceMock = $this->createMock(\Platform\IAM\AuthServiceInterface::class);
        $rbacMock = $this->createMock(\Platform\IAM\RBAC\RoleBasedAccessControl::class);
        
        $iamService = new \Platform\IAM\IAMService($authServiceMock, $rbacMock);
        
        $iamService->validateCredentials('123');
    }

    public function testValidateCredentialsEmpty(): void
    {
        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.validateCredentials - Empty credentials');
        $this->expectExceptionCode(400);

        // Create a mock authService and rbac for testing
        $authServiceMock = $this->createMock(\Platform\IAM\AuthServiceInterface::class);
        $rbacMock = $this->createMock(\Platform\IAM\RBAC\RoleBasedAccessControl::class);
        
        $iamService = new \Platform\IAM\IAMService($authServiceMock, $rbacMock);
        
        $iamService->validateCredentials('');
    }
}