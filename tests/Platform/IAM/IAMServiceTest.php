<?php

declare(strict_types=1);

namespace Tests\Platform\IAM;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Platform\IAM\IAMService;
use Platform\IAM\AuthServiceInterface;
use Platform\IAM\RBAC\RoleBasedAccessControl;
use Platform\IAM\Permissions\PermissionManager;
use Platform\IAM\Model\User;
use Platform\IAM\Exception\IAMException;

class IAMServiceTest extends TestCase
{
    private AuthServiceInterface&MockObject $authService;
    private RoleBasedAccessControl&MockObject $rbac;
    private PermissionManager&MockObject $permissionManager;
    private IAMService $iamService;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthServiceInterface::class);
        $this->rbac = $this->createMock(RoleBasedAccessControl::class);
        $this->permissionManager = $this->createMock(PermissionManager::class);
        $this->iamService = new IAMService(
            $this->authService,
            $this->rbac,
            $this->permissionManager
        );
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(IAMService::class, $this->iamService);
    }

    public function testAuthenticateSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('1');
        $user->method('getUsername')->willReturn('testuser');
        $user->method('toArray')->willReturn(['id' => '1', 'username' => 'testuser']);

        $this->authService->expects($this->once())
            ->method('authenticate')
            ->with('testuser', 'password')
            ->willReturn($user);

        $this->authService->expects($this->once())
            ->method('generateToken')
            ->with($user)
            ->willReturn('valid-token');

        $this->authService->expects($this->once())
            ->method('getUserRoles')
            ->with($user)
            ->willReturn(['role1', 'role2']);

        $this->authService->expects($this->once())
            ->method('getUserPermissions')
            ->with($user)
            ->willReturn(['perm1', 'perm2']);

        $result = $this->iamService->authenticate('testuser', 'password');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertEquals('valid-token', $result['token']);
        $this->assertEquals(['id' => '1', 'username' => 'testuser'], $result['user']);
    }

    public function testAuthenticateEmptyCredentialsThrowsException(): void
    {
        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.authenticate - Empty credentials provided');

        $this->iamService->authenticate('', 'password');
    }

    public function testAuthenticateInvalidCredentialsThrowsException(): void
    {
        $this->authService->expects($this->once())
            ->method('authenticate')
            ->with('testuser', 'wrongpassword')
            ->willReturn(null);

        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.authenticate - Invalid credentials for user');

        $this->iamService->authenticate('testuser', 'wrongpassword');
    }

    public function testAuthenticateSystemErrorThrowsException(): void
    {
        $this->authService->expects($this->once())
            ->method('authenticate')
            ->with('testuser', 'password')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.authenticate - Authentication system error');

        $this->iamService->authenticate('testuser', 'password');
    }

    public function testAuthorizeSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('1');
        $user->method('getUsername')->willReturn('testuser');
        $user->method('toArray')->willReturn(['id' => '1', 'username' => 'testuser']);

        $this->authService->expects($this->once())
            ->method('validateToken')
            ->with('valid-token')
            ->willReturn($user);

        $this->rbac->expects($this->once())
            ->method('checkPermission')
            ->with($user, 'read', null)
            ->willReturn(true);

        $this->authService->expects($this->once())
            ->method('getUserRoles')
            ->with($user)
            ->willReturn(['role1']);

        $this->authService->expects($this->once())
            ->method('getUserPermissions')
            ->with($user)
            ->willReturn(['perm1']);

        $result = $this->iamService->authorize('valid-token', 'read');

        $this->assertIsArray($result);
        $this->assertTrue($result['authorized']);
        $this->assertEquals(['id' => '1', 'username' => 'testuser'], $result['user']);
    }

    public function testAuthorizeEmptyTokenThrowsException(): void
    {
        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.authorize - Empty token provided');

        $this->iamService->authorize('', 'read');
    }

    public function testAuthorizeEmptyPermissionThrowsException(): void
    {
        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.authorize - Empty permission specified');

        $this->iamService->authorize('valid-token', '');
    }

    public function testAuthorizeInvalidTokenThrowsException(): void
    {
        $this->authService->expects($this->once())
            ->method('validateToken')
            ->with('invalid-token')
            ->willReturn(null);

        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.authorize - Invalid or expired token');

        $this->iamService->authorize('invalid-token', 'read');
    }

    public function testAuthorizeInsufficientPermissionsThrowsException(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUsername')->willReturn('testuser');

        $this->authService->expects($this->once())
            ->method('validateToken')
            ->with('valid-token')
            ->willReturn($user);

        $this->rbac->expects($this->once())
            ->method('checkPermission')
            ->with($user, 'write', null)
            ->willReturn(false);

        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.authorize - Insufficient permissions for user');

        $this->iamService->authorize('valid-token', 'write');
    }

    public function testAuthorizeSystemErrorThrowsException(): void
    {
        $this->authService->expects($this->once())
            ->method('validateToken')
            ->with('valid-token')
            ->willThrowException(new \Exception('Token validation failed'));

        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.authorize - Authorization system error');

        $this->iamService->authorize('valid-token', 'read');
    }

    public function testGetUserIdSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('123');

        $this->authService->expects($this->once())
            ->method('validateToken')
            ->with('valid-token')
            ->willReturn($user);

        $userId = $this->iamService->getUserId('valid-token');

        $this->assertEquals('123', $userId);
    }

    public function testGetUserIdEmptyTokenThrowsException(): void
    {
        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.getUserId - Empty token provided');

        $this->iamService->getUserId('');
    }

    public function testGetUserIdInvalidTokenThrowsException(): void
    {
        $this->authService->expects($this->once())
            ->method('validateToken')
            ->with('invalid-token')
            ->willReturn(null);

        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.getUserId - Invalid token format');

        $this->iamService->getUserId('invalid-token');
    }

    public function testGetUserIdSystemErrorThrowsException(): void
    {
        $this->authService->expects($this->once())
            ->method('validateToken')
            ->with('valid-token')
            ->willThrowException(new \Exception('Token validation failed'));

        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.getUserId - Invalid token format');

        $this->iamService->getUserId('valid-token');
    }

    public function testValidateCredentialsSuccess(): void
    {
        $this->assertTrue($this->iamService->validateCredentials('password123'));
    }

    public function testValidateCredentialsTooShortThrowsException(): void
    {
        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.validateCredentials - Password too short');

        $this->iamService->validateCredentials('123');
    }

    public function testValidateCredentialsEmptyThrowsException(): void
    {
        $this->expectException(IAMException::class);
        $this->expectExceptionMessage('[ERROR] IAMService.validateCredentials - Empty credentials provided');

        $this->iamService->validateCredentials('');
    }
}