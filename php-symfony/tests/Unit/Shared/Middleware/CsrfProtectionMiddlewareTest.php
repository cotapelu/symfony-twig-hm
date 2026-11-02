<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Middleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Middleware\CsrfProtectionMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Unit tests for CsrfProtectionMiddleware.
 *
 * Coverage target: 100% of middleware logic (branches, edge cases, error paths).
 * These tests verify CSRF validation logic without requiring kernel boot.
 */
#[AllowMockObjectsWithoutExpectations]
class CsrfProtectionMiddlewareTest extends TestCase
{
    private CsrfProtectionMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new CsrfProtectionMiddleware();
    }

    // ==================== onKernelRequest ====================

    public function testOnKernelRequestSkipsInTestEnvironment(): void
    {
        $_SERVER['APP_ENV'] = 'test';

        $request = new Request();
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->expects($this->never())->method('setResponse');

        $this->middleware->onKernelRequest($event);

        unset($_SERVER['APP_ENV']);
    }

    public function testOnKernelRequestSkipsNonMainRequest(): void
    {
        $_SERVER['APP_ENV'] = 'prod';

        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(false);
        $event->expects($this->never())->method('setResponse');

        $this->middleware->onKernelRequest($event);

        unset($_SERVER['APP_ENV']);
    }

    public function testOnKernelRequestSkipsNonApiRequest(): void
    {
        $_SERVER['APP_ENV'] = 'prod';

        $request = new Request();
        $request->server->set('REQUEST_URI', '/web/page');
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);
        $event->expects($this->never())->method('setResponse');

        $this->middleware->onKernelRequest($event);

        unset($_SERVER['APP_ENV']);
    }

    public function testOnKernelRequestSkipsForSafeMethods(): void
    {
        $_SERVER['APP_ENV'] = 'prod';

        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/test']);
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);
        $event->expects($this->never())->method('setResponse');

        $this->middleware->onKernelRequest($event);

        unset($_SERVER['APP_ENV']);
    }

    public function testOnKernelRequestSkipsForAuthorizationHeader(): void
    {
        $_SERVER['APP_ENV'] = 'prod';

        $request = new Request([], [], [], [], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/protected',
            'HTTP_AUTHORIZATION' => 'Bearer token123'
        ]);
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);
        $event->expects($this->never())->method('setResponse');

        $this->middleware->onKernelRequest($event);

        unset($_SERVER['APP_ENV']);
    }

    public function testOnKernelRequestSkipsForExemptRouteLogin(): void
    {
        $_SERVER['APP_ENV'] = 'prod';

        $request = new Request([], [], [], [], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/auth/login',
        ]);
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);
        $event->expects($this->never())->method('setResponse');

        $this->middleware->onKernelRequest($event);

        unset($_SERVER['APP_ENV']);
    }

    public function testOnKernelRequestSkipsForExemptRouteRegister(): void
    {
        $_SERVER['APP_ENV'] = 'prod';

        $request = new Request([], [], [], [], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/auth/register',
        ]);
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);
        $event->expects($this->never())->method('setResponse');

        $this->middleware->onKernelRequest($event);

        unset($_SERVER['APP_ENV']);
    }

    public function testOnKernelRequestSkipsForExemptRouteForgotPassword(): void
    {
        $_SERVER['APP_ENV'] = 'prod';

        $request = new Request([], [], [], [], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/auth/forgot-password',
        ]);
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);
        $event->expects($this->never())->method('setResponse');

        $this->middleware->onKernelRequest($event);

        unset($_SERVER['APP_ENV']);
    }

    public function testOnKernelRequestCallsValidateForStateChangingWithNoAuthAndNoExempt(): void
    {
        $_SERVER['APP_ENV'] = 'prod';

        $request = new Request([], [], [], [], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/data/update',
        ]);
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);
        // Expect setResponse to be called because CSRF validation will fail (no origin/referer)
        $event->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            return $response instanceof JsonResponse && $response->getStatusCode() === 403;
        }));

        $this->middleware->onKernelRequest($event);

        unset($_SERVER['APP_ENV']);
    }

    // ==================== validateCsrfRequest ====================

    public function testValidateCsrfRequestFailsWhenNoOriginAndNoReferer(): void
    {
        $request = new Request();
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            return $response instanceof JsonResponse && $response->getStatusCode() === 403;
        }));

        // Use reflection to call private method
        $method = new \ReflectionMethod($this->middleware, 'validateCsrfRequest');
        $method->setAccessible(true);
        $method->invoke($this->middleware, $request, $event);
    }

    public function testValidateCsrfRequestSucceedsWithTrustedOrigin(): void
    {
        $request = new Request([], [], [], [], [], [
            'HTTP_ORIGIN' => 'https://trusted.example.com',
        ]);
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->never())->method('setResponse');

        $mw = new CsrfProtectionMiddleware(['https://trusted.example.com']);
        $method = new \ReflectionMethod($mw, 'validateCsrfRequest');
        $method->setAccessible(true);
        $method->invoke($mw, $request, $event);
    }

    public function testValidateCsrfRequestFailsWithUntrustedOrigin(): void
    {
        $request = new Request([], [], [], [], [], [
            'HTTP_ORIGIN' => 'https://evil.com',
        ]);
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            return $response instanceof JsonResponse && $response->getStatusCode() === 403;
        }));

        $mw = new CsrfProtectionMiddleware(['https://trusted.example.com']);
        $method = new \ReflectionMethod($mw, 'validateCsrfRequest');
        $method->setAccessible(true);
        $method->invoke($mw, $request, $event);
    }

    public function testValidateCsrfRequestSucceedsWithTrustedReferer(): void
    {
        $request = new Request([], [], [], [], [], [
            'HTTP_REFERER' => 'https://trusted.example.com/page',
        ]);
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->never())->method('setResponse');

        $mw = new CsrfProtectionMiddleware(['https://trusted.example.com']);
        $method = new \ReflectionMethod($mw, 'validateCsrfRequest');
        $method->setAccessible(true);
        $method->invoke($mw, $request, $event);
    }

    public function testValidateCsrfRequestFailsWithUntrustedReferer(): void
    {
        $request = new Request([], [], [], [], [], [
            'HTTP_REFERER' => 'https://evil.com/page',
        ]);
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            return $response instanceof JsonResponse && $response->getStatusCode() === 403;
        }));

        $mw = new CsrfProtectionMiddleware(['https://trusted.example.com']);
        $method = new \ReflectionMethod($mw, 'validateCsrfRequest');
        $method->setAccessible(true);
        $method->invoke($mw, $request, $event);
    }

    public function testValidateCsrfRequestPrefersOriginOverReferer(): void
    {
        // When both headers present, origin takes precedence.
        $request = new Request([], [], [], [], [], [
            'HTTP_ORIGIN' => 'https://trusted.example.com',
            'HTTP_REFERER' => 'https://evil.com',
        ]);
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->never())->method('setResponse');

        $mw = new CsrfProtectionMiddleware(['https://trusted.example.com']);
        $method = new \ReflectionMethod($mw, 'validateCsrfRequest');
        $method->setAccessible(true);
        $method->invoke($mw, $request, $event);
    }

    // ==================== isOriginTrusted ====================

    public function testIsOriginTrustedWithEmptyTrustedOrigins(): void
    {
        $mw = new CsrfProtectionMiddleware([]);
        $result = $this->invokeMethod($mw, 'isOriginTrusted', ['https://any.example.com']);
        $this->assertTrue($result);
    }

    public function testIsOriginTrustedWithExactHostMatch(): void
    {
        $mw = new CsrfProtectionMiddleware(['https://example.com']);
        $result = $this->invokeMethod($mw, 'isOriginTrusted', ['https://example.com']);
        $this->assertTrue($result);
    }

    public function testIsOriginTrustedWithWwwSubdomain(): void
    {
        $mw = new CsrfProtectionMiddleware(['https://example.com']);
        $result = $this->invokeMethod($mw, 'isOriginTrusted', ['https://www.example.com']);
        $this->assertTrue($result);
    }

    public function testIsOriginTrustedWithDeepSubdomain(): void
    {
        $mw = new CsrfProtectionMiddleware(['https://example.com']);
        $result = $this->invokeMethod($mw, 'isOriginTrusted', ['https://a.b.example.com']);
        $this->assertTrue($result);
    }

    public function testIsOriginTrustedWithDifferentDomain(): void
    {
        $mw = new CsrfProtectionMiddleware(['https://example.com']);
        $result = $this->invokeMethod($mw, 'isOriginTrusted', ['https://notexample.com']);
        $this->assertFalse($result);
    }

    public function testIsOriginTrustedWithDifferentSubdomainOfDifferentDomain(): void
    {
        $mw = new CsrfProtectionMiddleware(['https://example.com']);
        $result = $this->invokeMethod($mw, 'isOriginTrusted', ['https://example.org']);
        $this->assertFalse($result);
    }

    public function testIsOriginTrustedWithNoHostInUrl(): void
    {
        $mw = new CsrfProtectionMiddleware(['https://example.com']);
        $result = $this->invokeMethod($mw, 'isOriginTrusted', ['not-a-url']);
        $this->assertFalse($result);
    }

    // ==================== isSubdomain ====================

    public function testIsSubdomainTrueForSubdomain(): void
    {
        $result = $this->invokeMethod($this->middleware, 'isSubdomain', ['www.example.com', 'example.com']);
        $this->assertTrue($result);
    }

    public function testIsSubdomainFalseForExactMatch(): void
    {
        // Exact match is not considered a subdomain.
        $result = $this->invokeMethod($this->middleware, 'isSubdomain', ['example.com', 'example.com']);
        $this->assertFalse($result);
    }

    public function testIsSubdomainFalseForDifferentDomain(): void
    {
        $result = $this->invokeMethod($this->middleware, 'isSubdomain', ['example.org', 'example.com']);
        $this->assertFalse($result);
    }

    public function testIsSubdomainTrueForDeepSubdomain(): void
    {
        $result = $this->invokeMethod($this->middleware, 'isSubdomain', ['a.b.c.example.com', 'example.com']);
        $this->assertTrue($result);
    }

    // ==================== Constructor variations ====================

    public function testConstructorWithString(): void
    {
        $mw = new CsrfProtectionMiddleware('https://example.com, https://other.com');
        $reflection = new \ReflectionProperty($mw, 'trustedOrigins');
        $reflection->setAccessible(true);
        $origins = $reflection->getValue($mw);
        $this->assertEquals(['https://example.com', 'https://other.com'], $origins);
    }

    public function testConstructorWithArray(): void
    {
        $mw = new CsrfProtectionMiddleware(['https://a.com', 'https://b.com']);
        $reflection = new \ReflectionProperty($mw, 'trustedOrigins');
        $reflection->setAccessible(true);
        $origins = $reflection->getValue($mw);
        $this->assertEquals(['https://a.com', 'https://b.com'], $origins);
    }

    public function testConstructorWithEmptyArray(): void
    {
        $mw = new CsrfProtectionMiddleware([]);
        $reflection = new \ReflectionProperty($mw, 'trustedOrigins');
        $reflection->setAccessible(true);
        $origins = $reflection->getValue($mw);
        $this->assertEquals([], $origins);
    }

    // ==================== Helper for invoking private methods ====================

    /**
     * Invoke a private/protected method on an object.
     */
    private function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionMethod($object, $methodName);
        $reflection->setAccessible(true);
        return $reflection->invoke($object, ...$parameters);
    }
}
