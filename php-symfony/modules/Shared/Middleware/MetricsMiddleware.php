<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MetricsMiddleware implements EventSubscriberInterface
{
    // Core metrics
    private static array $metrics = [
        'http_requests_total' => 0,
        'errors_total' => 0,
        'database_queries_total' => 0,
        'slow_queries_total' => 0,
        'cache_hits_total' => 0,
        'cache_misses_total' => 0,
    ];

    // Histogram for request duration (ms)
    private const DURATION_BUCKETS_MS = [5, 10, 25, 50, 100, 200, 500, 1000, 2000, 5000, 10000];
    private static array $durationBucketCounts = []; // cumulative counts per bucket
    private static float $durationSum = 0.0;
    private static int $durationCount = 0;

    private static array $requestStartTimes = [];

    public function __construct()
    {
        // Initialize bucket counts to zero
        foreach (self::DURATION_BUCKETS_MS as $bucket) {
            self::$durationBucketCounts[$bucket] = 0;
        }
    }

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
        self::$requestStartTimes[$request->getUri()] = microtime(true);
        self::$metrics['http_requests_total']++;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $uri = $request->getUri();

        if (isset(self::$requestStartTimes[$uri])) {
            $durationMs = (microtime(true) - self::$requestStartTimes[$uri]) * 1000;
            // Record histogram
            self::$durationSum += $durationMs;
            self::$durationCount++;

            // Update cumulative bucket counts
            foreach (self::DURATION_BUCKETS_MS as $bucket) {
                if ($durationMs <= $bucket) {
                    self::$durationBucketCounts[$bucket]++;
                }
            }

            unset(self::$requestStartTimes[$uri]);
        }
    }

    public function onKernelException(): void
    {
        self::$metrics['errors_total']++;
    }

    // Increment any metric dynamically (kept for compatibility)
    public static function incrementMetric(string $metric, float $value = 1): void
    {
        self::$metrics[$metric] = (self::$metrics[$metric] ?? 0) + $value;
    }

    public static function recordDatabaseQuery(string $operation): void
    {
        self::$metrics['database_queries_total'] = (self::$metrics['database_queries_total'] ?? 0) + 1;
    }

    public static function recordCacheHit(string $cacheType): void
    {
        self::$metrics['cache_hits_total'] = (self::$metrics['cache_hits_total'] ?? 0) + 1;
    }

    public static function recordCacheMiss(string $cacheType): void
    {
        self::$metrics['cache_misses_total'] = (self::$metrics['cache_misses_total'] ?? 0) + 1;
    }

    public static function recordSlowQuery(): void
    {
        self::$metrics['slow_queries_total'] = (self::$metrics['slow_queries_total'] ?? 0) + 1;
    }

    public static function getMetrics(): array
    {
        return self::$metrics;
    }

    public static function getDurationHistogram(): array
    {
        return [
            'buckets' => self::$durationBucketCounts,
            'sum' => self::$durationSum,
            'count' => self::$durationCount,
            'le_order' => self::DURATION_BUCKETS_MS,
        ];
    }

    public static function reset(): void
    {
        self::$metrics = [
            'http_requests_total' => 0,
            'errors_total' => 0,
            'database_queries_total' => 0,
            'cache_hits_total' => 0,
            'cache_misses_total' => 0,
        ];
        self::$durationSum = 0.0;
        self::$durationCount = 0;
        foreach (self::DURATION_BUCKETS_MS as $bucket) {
            self::$durationBucketCounts[$bucket] = 0;
        }
    }
}
