<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TracingMiddleware implements EventSubscriberInterface
{
    // W3C Trace Context header names
    private const TRACE_PARENT_HEADER = 'traceparent';
    private const TRACE_STATE_HEADER = 'tracestate';
    private const RESPONSE_TRACE_PARENT_HEADER = 'traceparent';

    private static array $currentTrace = []; // thread-local storage (per request)
    private static array $spans = []; // recorded spans

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Try to extract traceparent header
        $traceParent = $request->headers->get(self::TRACE_PARENT_HEADER);

        if ($traceParent && $this->isValidTraceParent($traceParent)) {
            $traceContext = $this->parseTraceParent($traceParent);
            $traceId = $traceContext['trace-id'];
            $parentSpanId = $traceContext['parent-span-id'];
            $traceFlags = $traceContext['trace-flags'];
        } else {
            // Generate new trace
            $traceId = $this->generateTraceId();
            $parentSpanId = null;
            $traceFlags = '00'; // not sampled by default? We'll sample
        }

        // Generate new span ID for this request
        $spanId = $this->generateSpanId();

        // Build traceparent for downstream services (if we are sampling)
        $newTraceParent = sprintf('00-%s-%s-%s', $traceId, $spanId, $traceFlags);

        // Store in request attributes for controllers/other middleware
        $request->attributes->set('trace_id', $traceId);
        $request->attributes->set('span_id', $spanId);
        $request->attributes->set('parent_span_id', $parentSpanId);
        $request->attributes->set('trace_flags', $traceFlags);

        // Store in static context for current request (accessible via static methods)
        self::$currentTrace = [
            'trace_id' => $traceId,
            'span_id' => $spanId,
            'parent_span_id' => $parentSpanId,
            'trace_flags' => $traceFlags,
            'start_time' => microtime(true),
        ];

        // Note: Setting response header is done in onKernelResponse when response is available

    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Capture current trace values before resetting
        $traceId = self::$currentTrace['trace_id'] ?? null;
        $spanId = self::$currentTrace['span_id'] ?? null;
        $traceFlags = self::$currentTrace['trace_flags'] ?? '00';

        $this->finishSpan();

        // Add traceparent header to response if we have trace data
        if ($traceId && $spanId) {
            $newTraceParent = sprintf('00-%s-%s-%s', $traceId, $spanId, $traceFlags);
            $event->getResponse()->headers->set(self::RESPONSE_TRACE_PARENT_HEADER, $newTraceParent);
        }
    }

    public function onKernelException(): void
    {
        $this->finishSpan(exception: true);
    }

    private function finishSpan(bool $exception = false): void
    {
        if (empty(self::$currentTrace)) {
            return;
        }

        $endTime = microtime(true);
        $start = self::$currentTrace['start_time'];
        $durationMs = (int)(($endTime - $start) * 1000);

        $span = [
            'trace_id' => self::$currentTrace['trace_id'],
            'span_id' => self::$currentTrace['span_id'],
            'parent_span_id' => self::$currentTrace['parent_span_id'],
            'duration_ms' => $durationMs,
            'exception' => $exception,
            'timestamp' => $start,
        ];

        self::$spans[] = $span;

        // Reset current trace
        self::$currentTrace = [];
    }

    // Public static accessors
    public static function getCurrentTraceId(): ?string
    {
        return self::$currentTrace['trace_id'] ?? null;
    }

    public static function getCurrentSpanId(): ?string
    {
        return self::$currentTrace['span_id'] ?? null;
    }

    public static function getSpans(): array
    {
        return self::$spans;
    }

    public static function reset(): void
    {
        self::$currentTrace = [];
        self::$spans = [];
    }

    // Helper: Generate 128-bit trace ID in hex (32 chars)
    private function generateTraceId(): string
    {
        // Generate 16 random bytes => 32 hex chars
        return bin2hex(random_bytes(16));
    }

    // Helper: Generate 64-bit span ID in hex (16 chars)
    private function generateSpanId(): string
    {
        // Generate 8 random bytes => 16 hex chars
        return bin2hex(random_bytes(8));
    }

    // Validate traceparent format (simplified)
    private function isValidTraceParent(string $traceParent): bool
    {
        // Format: version-trace-id-parent-span-id-trace-flags
        // Example: 00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01
        $parts = explode('-', $traceParent);
        if (count($parts) !== 4) {
            return false;
        }
        [$version, $traceId, $spanId, $traceFlags] = $parts;
        if (strlen($traceId) !== 32 || strlen($spanId) !== 16) {
            return false;
        }
        // Check version is 00
        return $version === '00' && preg_match('/^[0-9a-f]{32}$/', $traceId) && preg_match('/^[0-9a-f]{16}$/', $spanId);
    }

    private function parseTraceParent(string $traceParent): array
    {
        $parts = explode('-', $traceParent);
        return [
            'version' => $parts[0],
            'trace-id' => $parts[1],
            'parent-span-id' => $parts[2],
            'trace-flags' => $parts[3],
        ];
    }
}
