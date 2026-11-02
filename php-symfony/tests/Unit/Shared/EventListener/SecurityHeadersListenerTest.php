<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\EventListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\EventListener\SecurityHeadersListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AllowMockObjectsWithoutExpectations]

class SecurityHeadersListenerTest extends TestCase
{
    private function createResponseEvent(?object $response = null): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new \Symfony\Component\HttpFoundation\Request();
        if (!$response) {
            $response = new JsonResponse(['ok' => true]);
        }
        return new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = SecurityHeadersListener::getSubscribedEvents();
        $this->assertArrayHasKey('kernel.response', $events);
        $this->assertEquals(['onKernelResponse', -10], $events['kernel.response']);
    }

    public function testOnKernelResponseSetsStrictTransportSecurity(): void
    {
        $listener = new SecurityHeadersListener();
        $event = $this->createResponseEvent();
        $response = $event->getResponse();

        $listener->onKernelResponse($event);

        $this->assertEquals('max-age=31536000; includeSubDomains; preload', $response->headers->get('Strict-Transport-Security'));
    }

    public function testOnKernelResponseSetsXFrameOptions(): void
    {
        $listener = new SecurityHeadersListener();
        $event = $this->createResponseEvent();
        $response = $event->getResponse();

        $listener->onKernelResponse($event);

        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function testOnKernelResponseSetsXContentTypeOptions(): void
    {
        $listener = new SecurityHeadersListener();
        $event = $this->createResponseEvent();
        $response = $event->getResponse();

        $listener->onKernelResponse($event);

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function testOnKernelResponseSetsXXSSProtection(): void
    {
        $listener = new SecurityHeadersListener();
        $event = $this->createResponseEvent();
        $response = $event->getResponse();

        $listener->onKernelResponse($event);

        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    public function testOnKernelResponseSetsReferrerPolicy(): void
    {
        $listener = new SecurityHeadersListener();
        $event = $this->createResponseEvent();
        $response = $event->getResponse();

        $listener->onKernelResponse($event);

        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    public function testOnKernelResponseSetsContentSecurityPolicy(): void
    {
        $listener = new SecurityHeadersListener();
        $event = $this->createResponseEvent();
        $response = $event->getResponse();

        $listener->onKernelResponse($event);

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringStartsWith("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline' https://fonts.googleapis.com", $csp);
        $this->assertStringContainsString("img-src 'self' data: https: blob:", $csp);
        $this->assertStringContainsString("font-src 'self' https://fonts.gstatic.com", $csp);
        $this->assertStringContainsString("connect-src 'self' wss: https:", $csp);
        $this->assertStringContainsString("media-src 'self' https: blob:", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringContainsString('upgrade-insecure-requests', $csp);
    }

    public function testOnKernelResponseWorksWithDifferentResponseTypes(): void
    {
        $listener = new SecurityHeadersListener();

        $responses = [
            new JsonResponse(['data' => 'test']),
            new \Symfony\Component\HttpFoundation\Response('Hello', 200, ['X-Custom' => 'value']),
            new \Symfony\Component\HttpFoundation\RedirectResponse('/redirect'),
        ];

        foreach ($responses as $response) {
            $event = $this->createResponseEvent($response);
            $listener->onKernelResponse($event);
            $this->assertEquals('max-age=31536000; includeSubDomains; preload', $response->headers->get('Strict-Transport-Security'));
            $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
            $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
            $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
        }
    }

    public function testOnKernelResponseDoesNotOverrideExistingHeaders(): void
    {
        $listener = new SecurityHeadersListener();

        $response = new JsonResponse(['ok' => true]);
        $response->headers->set('X-Custom-Header', 'custom-value');

        $event = $this->createResponseEvent($response);
        $listener->onKernelResponse($event);

        // Security headers are set
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        // Custom header preserved
        $this->assertEquals('custom-value', $response->headers->get('X-Custom-Header'));
    }

    public function testSecurityHeadersValuesMatchExpected(): void
    {
        $listener = new SecurityHeadersListener();
        $event = $this->createResponseEvent();
        $response = $event->getResponse();
        $listener->onKernelResponse($event);

        $expectedHeaders = [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        foreach ($expectedHeaders as $header => $value) {
            $this->assertEquals($value, $response->headers->get($header), "Header $header should have expected value");
        }

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotFalse($csp);
        $this->assertGreaterThan(100, strlen($csp)); // CSP is lengthy
    }
}
