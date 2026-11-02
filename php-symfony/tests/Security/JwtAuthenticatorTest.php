<?php

declare(strict_types=1);

namespace App\Tests\Security;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Security\JwtAuthenticator;
use App\Shared\Service\JwtService;
use App\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[AllowMockObjectsWithoutExpectations]
class JwtAuthenticatorTest extends TestCase
{
    public function testSupportsReturnsFalseForNonApiPaths(): void
    {
        $jwtService = $this->createMock(JwtService::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $authenticator = new JwtAuthenticator($jwtService, $userRepo);

        $request = new Request([], [], [], [], [], ['PATH_INFO' => '/']);
        $this->assertFalse($authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForExcludedPaths(): void
    {
        $jwtService = $this->createMock(JwtService::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $authenticator = new JwtAuthenticator($jwtService, $userRepo);

        $request = new Request([], [], [], [], [], ['PATH_INFO' => '/api/auth/login']);
        $this->assertFalse($authenticator->supports($request));
    }

    public function testSupportsReturnsTrueWhenBearerTokenPresent(): void
    {
        $jwtService = $this->createMock(JwtService::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $authenticator = new JwtAuthenticator($jwtService, $userRepo);

        $request = new Request([], [], [], [], [], [
            'PATH_INFO' => '/api/test',
            'HTTP_AUTHORIZATION' => 'Bearer validtoken'
        ]);
        $this->assertTrue($authenticator->supports($request));
    }

    public function testSupportsReturnsFalseWhenNoBearerToken(): void
    {
        $jwtService = $this->createMock(JwtService::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $authenticator = new JwtAuthenticator($jwtService, $userRepo);

        $request = new Request([], [], [], [], [], [
            'PATH_INFO' => '/api/test',
            'HTTP_AUTHORIZATION' => 'Basic something'
        ]);
        $this->assertFalse($authenticator->supports($request));
    }

    public function testAuthenticateThrowsWhenNoBearerToken(): void
    {
        $jwtService = $this->createMock(JwtService::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $authenticator = new JwtAuthenticator($jwtService, $userRepo);

        $request = new Request([], [], [], [], [], [
            'PATH_INFO' => '/api/test',
            'HTTP_AUTHORIZATION' => 'Basic token'
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No Bearer token found');
        $authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsWhenInvalidToken(): void
    {
        $jwtService = $this->createMock(JwtService::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $authenticator = new JwtAuthenticator($jwtService, $userRepo);

        $jwtService->method('validateToken')
            ->willReturn(null);

        $request = new Request([], [], [], [], [], [
            'PATH_INFO' => '/api/test',
            'HTTP_AUTHORIZATION' => 'Bearer invalidtoken'
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid or expired token');
        $authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsWhenUserIdMissing(): void
    {
        $jwtService = $this->createMock(JwtService::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $authenticator = new JwtAuthenticator($jwtService, $userRepo);

        $jwtService->method('validateToken')
            ->willReturn(['email' => 'test@example.com']); // no userId

        $request = new Request([], [], [], [], [], [
            'PATH_INFO' => '/api/test',
            'HTTP_AUTHORIZATION' => 'Bearer token'
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Token missing user ID');
        $authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsWhenUserNotFound(): void
    {
        $jwtService = $this->createMock(JwtService::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $authenticator = new JwtAuthenticator($jwtService, $userRepo);

        $jwtService->method('validateToken')
            ->willReturn(['userId' => 'non-existent-id']);

        $userRepo->method('find')
            ->with('non-existent-id')
            ->willReturn(null);

        $request = new Request([], [], [], [], [], [
            'PATH_INFO' => '/api/test',
            'HTTP_AUTHORIZATION' => 'Bearer token'
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('User not found');
        $authenticator->authenticate($request);
    }

    public function testAuthenticateReturnsPassportWhenValid(): void
    {
        $jwtService = $this->createMock(JwtService::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $authenticator = new JwtAuthenticator($jwtService, $userRepo);

        $user = new \App\User\Entity\User('user-123', 'test@example.com', ['ROLE_USER']);
        // ID already set via constructor

        $jwtService->method('validateToken')
            ->willReturn(['userId' => 'user-123']);

        $userRepo->method('find')
            ->with('user-123')
            ->willReturn($user);

        $request = new Request([], [], [], [], [], [
            'PATH_INFO' => '/api/test',
            'HTTP_AUTHORIZATION' => 'Bearer token'
        ]);

        $passport = $authenticator->authenticate($request);
        $this->assertNotNull($passport);
    }
}
