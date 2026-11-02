<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Security;

use App\Shared\Security\AuthenticationService;
use App\Shared\Service\JwtService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class AuthenticationServiceTest extends TestCase
{
    private AuthenticationService $service;
    private JwtService&MockObject $jwtMock;

    protected function setUp(): void
    {
        $this->jwtMock = $this->createMock(JwtService::class);
        $this->service = new AuthenticationService($this->jwtMock);
    }

    public function testAuthenticateUserReturnsNullWhenNoAuthorizationHeader(): void
    {
        $request = new Request();
        $result = $this->service->authenticateUser($request);
        $this->assertNull($result);
    }

    public function testAuthenticateUserReturnsNullWhenTokenNotBearer(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Basic abc123');
        $result = $this->service->authenticateUser($request);
        $this->assertNull($result);
    }

    public function testAuthenticateUserReturnsUserFromJwtService(): void
    {
        $userData = ['id' => 'user123', 'email' => 'test@example.com'];
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer validtoken');

        $this->jwtMock->expects($this->once())
            ->method('getUserFromToken')
            ->with('validtoken')
            ->willReturn($userData);

        $result = $this->service->authenticateUser($request);
        $this->assertSame($userData, $result);
    }

    public function testRequireAuthenticationThrowsWhenUnauthenticated(): void
    {
        $request = new Request();
        // No Authorization header

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Authentication required');
        $this->expectExceptionCode(401);
        $this->service->requireAuthentication($request);
    }

    public function testRequireAuthenticationReturnsUserWhenAuthenticated(): void
    {
        $userData = ['id' => 'user123', 'email' => 'test@example.com'];
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer token');

        $this->jwtMock->method('getUserFromToken')->willReturn($userData);

        $result = $this->service->requireAuthentication($request);
        $this->assertSame($userData, $result);
    }

    public function testGetCurrentUserIdReturnsIdFromToken(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer token');
        $this->jwtMock->method('getUserFromToken')->willReturn(['id' => 'user456']);

        $result = $this->service->getCurrentUserId($request);
        $this->assertEquals('user456', $result);
    }

    public function testGetCurrentUserIdReturnsNullWhenNoUser(): void
    {
        $request = new Request();
        // No token
        $result = $this->service->getCurrentUserId($request);
        $this->assertNull($result);
    }

    public function testGetCurrentUserReturnsSameAsAuthenticateUser(): void
    {
        $userData = ['id' => 'user789', 'email' => 'user@example.com'];
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer token');

        $this->jwtMock->method('getUserFromToken')->willReturn($userData);

        $result = $this->service->getCurrentUser($request);
        $this->assertSame($userData, $result);
    }
}
