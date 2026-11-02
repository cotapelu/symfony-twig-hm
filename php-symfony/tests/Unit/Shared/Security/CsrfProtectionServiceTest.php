<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Security;

use App\Shared\Security\CsrfProtectionService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class CsrfProtectionServiceTest extends TestCase
{
    private CsrfProtectionService $service;
    private CsrfTokenManagerInterface&MockObject $tokenManagerMock;

    protected function setUp(): void
    {
        $this->tokenManagerMock = $this->createMock(CsrfTokenManagerInterface::class);
        $this->service = new CsrfProtectionService($this->tokenManagerMock);
    }

    public function testValidateTokenAllowsGetRequests(): void
    {
        $request = new Request();
        $request->setMethod('GET');
        $result = $this->service->validateToken($request);
        $this->assertTrue($result);
    }

    public function testValidateTokenReturnsFalseWhenNoTokenProvided(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $result = $this->service->validateToken($request);
        $this->assertFalse($result);
    }

    public function testValidateTokenChecksHeaderFirst(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->headers->set('X-CSRF-Token', 'headertoken');

        $this->tokenManagerMock->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(fn($token) => $token->getValue() === 'headertoken' && $token->getId() === 'default'))
            ->willReturn(true);

        $result = $this->service->validateToken($request);
        $this->assertTrue($result);
    }

    public function testValidateTokenChecksRequestParameter(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->request->set('_token', 'paramtoken');

        $this->tokenManagerMock->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(fn($token) => $token->getValue() === 'paramtoken' && $token->getId() === 'default'))
            ->willReturn(true);

        $result = $this->service->validateToken($request);
        $this->assertTrue($result);
    }

    public function testValidateTokenChecksCsrfTokenAlias(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->request->set('csrf_token', 'aliastoken');

        $this->tokenManagerMock->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(fn($token) => $token->getValue() === 'aliastoken' && $token->getId() === 'default'))
            ->willReturn(true);

        $result = $this->service->validateToken($request);
        $this->assertTrue($result);
    }

    public function testValidateTokenChecksQueryParameter(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->query->set('_token', 'querytoken');

        $this->tokenManagerMock->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(fn($token) => $token->getValue() === 'querytoken' && $token->getId() === 'default'))
            ->willReturn(true);

        $result = $this->service->validateToken($request);
        $this->assertTrue($result);
    }

    public function testValidateTokenUsesCustomTokenId(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->headers->set('X-CSRF-Token', 'token123');

        $this->tokenManagerMock->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(fn($token) => $token->getValue() === 'token123' && $token->getId() === 'custom_id'))
            ->willReturn(true);

        $result = $this->service->validateToken($request, 'custom_id');
        $this->assertTrue($result);
    }

    public function testValidateTokenReturnsFalseWhenInvalid(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->headers->set('X-CSRF-Token', 'invalidtoken');

        $this->tokenManagerMock->expects($this->once())
            ->method('isTokenValid')
            ->with($this->anything())
            ->willReturn(false);

        $result = $this->service->validateToken($request);
        $this->assertFalse($result);
    }

    public function testGetTokenDelegatesToTokenManager(): void
    {
        $csrfToken = $this->createMock(CsrfToken::class);
        $csrfToken->method('getValue')->willReturn('generated_token');

        $this->tokenManagerMock->expects($this->once())
            ->method('getToken')
            ->with('default')
            ->willReturn($csrfToken);

        $result = $this->service->getToken();
        $this->assertEquals('generated_token', $result);
    }

    public function testGetTokenWithCustomId(): void
    {
        $csrfToken = $this->createMock(CsrfToken::class);
        $csrfToken->method('getValue')->willReturn('custom_generated');

        $this->tokenManagerMock->expects($this->once())
            ->method('getToken')
            ->with('my_id')
            ->willReturn($csrfToken);

        $result = $this->service->getToken('my_id');
        $this->assertEquals('custom_generated', $result);
    }

    public function testRefreshTokenDelegatesToTokenManager(): void
    {
        $csrfToken = $this->createMock(CsrfToken::class);
        $csrfToken->method('getValue')->willReturn('refreshed_token');

        $this->tokenManagerMock->expects($this->once())
            ->method('refreshToken')
            ->with('default')
            ->willReturn($csrfToken);

        $result = $this->service->refreshToken();
        $this->assertEquals('refreshed_token', $result);
    }

    public function testRefreshTokenWithCustomId(): void
    {
        $csrfToken = $this->createMock(CsrfToken::class);
        $csrfToken->method('getValue')->willReturn('refreshed_custom');

        $this->tokenManagerMock->expects($this->once())
            ->method('refreshToken')
            ->with('custom_refresh_id')
            ->willReturn($csrfToken);

        $result = $this->service->refreshToken('custom_refresh_id');
        $this->assertEquals('refreshed_custom', $result);
    }
}
