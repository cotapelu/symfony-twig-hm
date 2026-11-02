<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Middleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Middleware\TracingMiddleware;
use PHPUnit\Framework\Attributes\AfterEach;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AllowMockObjectsWithoutExpectations]

class TracingMiddlewareTest extends TestCase
{
    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function createResponseEvent(Request $request, Response $response): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }

    #[AfterEach]
    public function resetAfterEach(): void
    {
        TracingMiddleware::reset();
    }

    public function testOnKernelRequestWithValidTraceParent(): void
    {
        TracingMiddleware::reset();
        $middleware = new TracingMiddleware();

        $traceParent = '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01';
        $request = new Request();
        $request->headers->set('traceparent', $traceParent);
        $event = $this->createRequestEvent($request);
        $middleware->onKernelRequest($event);

        $this->assertSame('0af7651916cd43dd8448eb211c80319c', $request->attributes->get('trace_id'));
        $this->assertSame('b7ad6b7169203331', $request->attributes->get('parent_span_id'));
        $this->assertSame('01', $request->attributes->get('trace_flags'));

        $currentTraceId = TracingMiddleware::getCurrentTraceId();
        $currentSpanId = TracingMiddleware::getCurrentSpanId();
        $this->assertNotNull($currentTraceId);
        $this->assertNotNull($currentSpanId);
        $this->assertSame('0af7651916cd43dd8448eb211c80319c', $currentTraceId);
        $this->assertNotSame('b7ad6b7169203331', $currentSpanId); // new span generated
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $currentSpanId);
    }

    public function testOnKernelRequestGeneratesNewTraceWhenHeaderMissing(): void
    {
        TracingMiddleware::reset();
        $middleware = new TracingMiddleware();

        $request = new Request(); // no traceparent
        $event = $this->createRequestEvent($request);
        $middleware->onKernelRequest($event);

        $traceId = TracingMiddleware::getCurrentTraceId();
        $spanId = TracingMiddleware::getCurrentSpanId();

        $this->assertNotNull($traceId);
        $this->assertNotNull($spanId);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $traceId);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $spanId);
        $this->assertSame('00', $request->attributes->get('trace_flags'));
        $this->assertNull($request->attributes->get('parent_span_id'));
    }

    public function testOnKernelRequestIgnoresInvalidFormat(): void
    {
        TracingMiddleware::reset();
        $middleware = new TracingMiddleware();

        $request = new Request();
        $request->headers->set('traceparent', 'invalid-format-too-short');
        $event = $this->createRequestEvent($request);
        $middleware->onKernelRequest($event);

        $traceId = TracingMiddleware::getCurrentTraceId();
        $spanId = TracingMiddleware::getCurrentSpanId();

        $this->assertNotNull($traceId);
        $this->assertNotNull($spanId);
        $this->assertNull($request->attributes->get('parent_span_id'));
    }

    public function testOnKernelRequestRejectsInvalidVersion(): void
    {
        TracingMiddleware::reset();
        $middleware = new TracingMiddleware();

        $traceParent = '99-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01';
        $request = new Request();
        $request->headers->set('traceparent', $traceParent);
        $event = $this->createRequestEvent($request);
        $middleware->onKernelRequest($event);

        $this->assertNotNull(TracingMiddleware::getCurrentTraceId());
        $this->assertNull($request->attributes->get('parent_span_id'));
        $this->assertSame('00', $request->attributes->get('trace_flags'));
    }

    public function testOnKernelResponseSetsTraceParentHeader(): void
    {
        TracingMiddleware::reset();
        $middleware = new TracingMiddleware();

        $traceParent = '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01';
        $request = new Request();
        $request->headers->set('traceparent', $traceParent);
        $event = $this->createRequestEvent($request);
        $middleware->onKernelRequest($event);

        // Capture IDs before response (onKernelResponse resets)
        $traceId = TracingMiddleware::getCurrentTraceId();
        $spanId = TracingMiddleware::getCurrentSpanId();

        $response = new Response();
        $responseEvent = $this->createResponseEvent($request, $response);
        $middleware->onKernelResponse($responseEvent);

        $this->assertTrue($response->headers->has('traceparent'));
        $header = $response->headers->get('traceparent');
        $this->assertMatchesRegularExpression('/^00-[0-9a-f]{32}-[0-9a-f]{16}-[0-9a-f]{2}$/', $header);
        $this->assertStringContainsString($traceId, $header);
        $this->assertStringContainsString($spanId, $header);
    }

    public function testOnKernelExceptionFinishesSpanWithExceptionFlag(): void
    {
        TracingMiddleware::reset();
        $middleware = new TracingMiddleware();

        $request = new Request();
        $event = $this->createRequestEvent($request);
        $middleware->onKernelRequest($event);

        usleep(1000); // ensure duration > 0
        $middleware->onKernelException();

        $spans = TracingMiddleware::getSpans();
        $this->assertCount(1, $spans);
        $this->assertTrue($spans[0]['exception']);
        $this->assertArrayHasKey('trace_id', $spans[0]);
        $this->assertArrayHasKey('span_id', $spans[0]);
        $this->assertArrayHasKey('duration_ms', $spans[0]);
        $this->assertGreaterThan(0, $spans[0]['duration_ms']);
    }

    public function testFinishSpanRecordsDurationAndResetsCurrentTrace(): void
    {
        TracingMiddleware::reset();
        $middleware = new TracingMiddleware();

        $request = new Request();
        $event = $this->createRequestEvent($request);
        $middleware->onKernelRequest($event);

        usleep(1000); // ensure some duration

        $response = new Response();
        $responseEvent = $this->createResponseEvent($request, $response);
        $middleware->onKernelResponse($responseEvent);

        $spans = TracingMiddleware::getSpans();
        $this->assertCount(1, $spans);
        $this->assertGreaterThan(0, $spans[0]['duration_ms']);
        $this->assertNull(TracingMiddleware::getCurrentTraceId());
        $this->assertNull(TracingMiddleware::getCurrentSpanId());
    }

    public function testGetSpansReturnsEmptyArrayWhenNoTraces(): void
    {
        TracingMiddleware::reset();
        $this->assertSame([], TracingMiddleware::getSpans());
    }

    public function testMultipleRequestsGenerateIndependentTraces(): void
    {
        TracingMiddleware::reset();
        $middleware = new TracingMiddleware();

        // First request
        $request1 = new Request();
        $event1 = $this->createRequestEvent($request1);
        $middleware->onKernelRequest($event1);
        $traceId1 = TracingMiddleware::getCurrentTraceId();
        $response1 = new Response();
        $responseEvent1 = $this->createResponseEvent($request1, $response1);
        $middleware->onKernelResponse($responseEvent1);

        // Second request
        $request2 = new Request();
        $event2 = $this->createRequestEvent($request2);
        $middleware->onKernelRequest($event2);
        $traceId2 = TracingMiddleware::getCurrentTraceId();
        $response2 = new Response();
        $responseEvent2 = $this->createResponseEvent($request2, $response2);
        $middleware->onKernelResponse($responseEvent2);

        $this->assertNotSame($traceId1, $traceId2);
        $spans = TracingMiddleware::getSpans();
        $this->assertCount(2, $spans);
    }

    public function testParseTraceParent(): void
    {
        $middleware = new TracingMiddleware();
        $traceParent = '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01';
        $expected = [
            'version' => '00',
            'trace-id' => '0af7651916cd43dd8448eb211c80319c',
            'parent-span-id' => 'b7ad6b7169203331',
            'trace-flags' => '01',
        ];
        $this->assertSame($expected, $this->invokeParse($middleware, $traceParent));
    }

    private function invokeParse(TracingMiddleware $middleware, string $traceParent): array
    {
        $method = new ReflectionMethod($middleware, 'parseTraceParent');
        $method->setAccessible(true);
        return $method->invoke($middleware, $traceParent);
    }

    public function testGenerateIdsAreUniqueAndCorrectLength(): void
    {
        $middleware = new TracingMiddleware();
        $traceIds = [];
        $spanIds = [];
        for ($i = 0; $i < 10; $i++) {
            $traceIds[] = $this->invokeGenerateTraceId($middleware);
            $spanIds[] = $this->invokeGenerateSpanId($middleware);
        }
        $this->assertCount(10, $traceIds);
        $this->assertCount(10, $spanIds);
        $this->assertCount(count(array_unique($traceIds)), $traceIds);
        $this->assertCount(count(array_unique($spanIds)), $spanIds);
        foreach ($traceIds as $tid) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $tid);
        }
        foreach ($spanIds as $sid) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $sid);
        }
    }

    private function invokeGenerateTraceId(TracingMiddleware $middleware): string
    {
        $method = new ReflectionMethod($middleware, 'generateTraceId');
        $method->setAccessible(true);
        return $method->invoke($middleware);
    }

    private function invokeGenerateSpanId(TracingMiddleware $middleware): string
    {
        $method = new ReflectionMethod($middleware, 'generateSpanId');
        $method->setAccessible(true);
        return $method->invoke($middleware);
    }
}
