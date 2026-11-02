<?php

declare(strict_types=1);

namespace App\Tests\EventListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\EventListener\SecurityHeadersListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AllowMockObjectsWithoutExpectations]
class SecurityHeadersListenerTest extends TestCase
{
    private SecurityHeadersListener $listener;

    protected function setUp(): void
    {
        $this->listener = new SecurityHeadersListener();
    }

    public function testOnKernelResponseAddsSecurityHeaders(): void
    {
        $response = new Response();
        $request = new Request();
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
        $this->assertStringStartsWith('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
        $this->assertStringContainsString('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertStringStartsWith("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }
}