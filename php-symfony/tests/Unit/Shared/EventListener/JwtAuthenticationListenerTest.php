<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\EventListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\EventListener\JwtAuthenticationListener;
use App\Shared\Service\JwtService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]

class JwtAuthenticationListenerTest extends TestCase
{
    private JwtService&MockObject $jwtServiceMock;
    private JwtAuthenticationListener $listener;

    protected function setUp(): void
    {
        $this->jwtServiceMock = $this->createMock(JwtService::class);
        $this->listener = new JwtAuthenticationListener($this->jwtServiceMock);
    }

    private function createRequestEvent(string $path, string $method = 'GET', array $headers = []): RequestEvent
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => $path,
            'REQUEST_METHOD' => $method,
        ], null);
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    public function testExcludedPathsSkipAuthentication(): void
    {
        $excluded = [
            '/api/auth/login',
            '/api/auth/register',
            '/api/auth/forgot-password',
            '/api/auth/reset-password',
            '/',
            '/api/login',
            '/api/register',
            '/register',
            '/login',
            '/forgot-password',
            '/reset-password',
        ];
        foreach ($excluded as $path) {
            $event = $this->createRequestEvent($path);
            $this->listener->__invoke($event);
            $this->assertNull($event->getResponse(), "Expected no response for excluded path: $path");
        }
    }

    public function testEducationEndpointsSkipAuthentication(): void
    {
        $paths = [
            '/api/education',
            '/api/education/courses',
            '/api/education/courses/123',
        ];
        foreach ($paths as $path) {
            $event = $this->createRequestEvent($path);
            $this->listener->__invoke($event);
            $this->assertNull($event->getResponse(), "Expected no response for education path: $path");
        }
    }

    public function testNonApiPathsSkipAuthentication(): void
    {
        $paths = [
            '/web/page',
            '/admin',
            '/profile',
        ];
        foreach ($paths as $path) {
            $event = $this->createRequestEvent($path);
            $this->listener->__invoke($event);
            $this->assertNull($event->getResponse(), "Expected no response for non-API path: $path");
        }
    }

    public function testMissingAuthorizationHeaderReturns401(): void
    {
        $event = $this->createRequestEvent('/api/protected');
        $this->listener->__invoke($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['error' => 'Authorization header missing'], json_decode($response->getContent(), true));
    }

    public function testInvalidBearerFormatReturns401(): void
    {
        $event = $this->createRequestEvent('/api/protected', 'GET', ['Authorization' => 'Token abc123']);
        $this->listener->__invoke($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['error' => 'Authorization header missing'], json_decode($response->getContent(), true));
    }

    public function testEmptyTokenReturns401(): void
    {
        $event = $this->createRequestEvent('/api/protected', 'GET', ['Authorization' => 'Bearer ']);
        $this->listener->__invoke($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['error' => 'Invalid token'], json_decode($response->getContent(), true));
    }

    public function testInvalidTokenReturns401(): void
    {
        $this->jwtServiceMock->expects($this->once())
            ->method('getUserFromToken')
            ->with('invalidtoken')
            ->willReturn(null);

        $event = $this->createRequestEvent('/api/protected', 'GET', ['Authorization' => 'Bearer invalidtoken']);
        $this->listener->__invoke($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['error' => 'Invalid token'], json_decode($response->getContent(), true));
    }

    public function testValidTokenSetsUserInAttributes(): void
    {
        $user = ['id' => 42, 'email' => 'user@example.com'];
        $this->jwtServiceMock->expects($this->once())
            ->method('getUserFromToken')
            ->with('validtoken')
            ->willReturn($user);

        $event = $this->createRequestEvent('/api/protected', 'GET', ['Authorization' => 'Bearer validtoken']);
        $request = $event->getRequest();

        $this->listener->__invoke($event);

        $this->assertNull($event->getResponse());
        $this->assertTrue($request->attributes->has('user'));
        $this->assertSame($user, $request->attributes->get('user'));
    }

    public function testValidTokenWithComplexPayload(): void
    {
        $user = [
            'id' => 123,
            'email' => 'test@domain.com',
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
            'profile' => [
                'name' => 'John Doe',
                'avatar' => 'https://example.com/avatar.jpg',
            ],
        ];
        $this->jwtServiceMock->expects($this->once())
            ->method('getUserFromToken')
            ->with('complex.token.here')
            ->willReturn($user);

        $event = $this->createRequestEvent('/api/protected/resource', 'POST', ['Authorization' => 'Bearer complex.token.here']);
        $request = $event->getRequest();

        $this->listener->__invoke($event);

        $this->assertNull($event->getResponse());
        $this->assertSame($user, $request->attributes->get('user'));
    }

    public function testBearerTokenWithMultipleSpaces(): void
    {
        // Only first space separates scheme; rest are part of token (though unusual)
        $this->jwtServiceMock->expects($this->once())
            ->method('getUserFromToken')
            ->with('token with spaces')
            ->willReturn(['id' => 1]);

        $event = $this->createRequestEvent('/api/protected', 'GET', ['Authorization' => 'Bearer token with spaces']);
        $this->listener->__invoke($event);

        $this->assertNull($event->getResponse());
    }

    public function testPathTraversalDoesNotBypass(): void
    {
        // Path not matching exactly should not bypass
        $event = $this->createRequestEvent('/api/auth/login/extra');
        $this->listener->__invoke($event);
        // Should get 401 because not in excluded list (exact match required)
        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testApiWithTrailingSlashIsNotExcluded(): void
    {
        $event = $this->createRequestEvent('/api/login/');
        $this->listener->__invoke($event);
        // Not in excludedPaths exactly, so requires auth
        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
