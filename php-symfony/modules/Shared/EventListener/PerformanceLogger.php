<?php

declare(strict_types=1);

namespace App\Shared\EventListener;

use App\Shared\Service\PerformanceMonitorService;
use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class PerformanceLogger
{
    private array $queries = [];

    public function __construct(
        private PerformanceMonitorService $monitor,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(priority: 4096)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->monitor->startTimer('request');
        $this->monitor->logMemorySnapshot('request_start');
    }

    #[AsEventListener(priority: -4096)]
    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $timerData = $this->monitor->endTimer('request');

        $endpoint = $request->getPathInfo();
        $method = $request->getMethod();
        $statusCode = $response->getStatusCode();
        $durationMs = $timerData['duration_ms'] ?? 0;

        $this->monitor->logApiCall($endpoint, $method, $statusCode, $durationMs / 1000);

        if (isset($timerData['duration_ms']) && $timerData['duration_ms'] > 1000) {
            $this->logger->warning('Slow request detected', [
                'endpoint' => $endpoint,
                'method' => $method,
                'duration_ms' => $timerData['duration_ms'],
            ]);
        }

        $this->monitor->checkNPlusOneQueries($this->queries);
        $this->queries = [];

        $this->monitor->logMemorySnapshot('request_end');
    }

    public function logQuery(string $sql, ?array $params = null, float $duration = 0): void
    {
        $this->queries[] = $sql;

        $this->monitor->logQuery($sql, $duration, 0);

        if ($duration > 0.1) {
            $this->logger->warning('Slow query detected', [
                'sql' => $sql,
                'duration' => $duration,
            ]);
            // Increment slow query metric
            \App\Shared\Middleware\MetricsMiddleware::recordSlowQuery();
        }
    }
}
