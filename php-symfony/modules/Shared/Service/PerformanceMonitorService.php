<?php

declare(strict_types=1);

namespace App\Shared\Service;

use Psr\Log\LoggerInterface;

class PerformanceMonitorService
{
    private array $metrics = [];
    private array $timers = [];

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function startTimer(string $name)
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    public function endTimer(string $name)
    {
        if (!isset($this->timers[$name])) {
            return [];
        }

        $end = microtime(true);
        $memoryEnd = memory_get_usage(true);

        $duration = $end - $this->timers[$name]['start'];
        $memoryUsed = $memoryEnd - $this->timers[$name]['memory_start'];

        $metric = [
            'name' => $name,
            'duration_ms' => round($duration * 1000, 2),
            'memory_bytes' => $memoryUsed,
            'memory_mb' => round($memoryUsed / 1024 / 1024, 2),
            'timestamp' => date('c'),
        ];

        $this->metrics[] = $metric;
        unset($this->timers[$name]);

        return $metric;
    }

    public function recordMetric(string $name, float|int $value, array $tags = [])
    {
        $metric = [
            'name' => $name,
            'value' => $value,
            'timestamp' => date('c'),
        ];

        if (!empty($tags)) {
            $metric['tags'] = $tags;
        }

        $this->metrics[] = $metric;
    }

    public function recordError(string $message, array $context = [])
    {
        $this->logger->error($message, $context);

        $this->metrics[] = [
            'name' => 'error',
            'message' => $message,
            'context' => $context,
            'timestamp' => date('c'),
        ];
    }

    public function recordWarning(string $message, array $context = [])
    {
        $this->logger->warning($message, $context);

        $this->metrics[] = [
            'name' => 'warning',
            'message' => $message,
            'context' => $context,
            'timestamp' => date('c'),
        ];
    }

    public function getMetrics()
    {
        return $this->metrics;
    }

    public function getMetricsByType(string $type)
    {
        return array_filter($this->metrics, function ($metric) use ($type) {
            return ($metric['name'] ?? '') === $type;
        });
    }

    public function clearMetrics()
    {
        $this->metrics = [];
        $this->timers = [];
    }

    public function getSummary()
    {
        $slowestCalls = array_filter($this->metrics, function ($m) {
            return isset($m['duration_ms']) && $m['duration_ms'] > 1000;
        });

        $memoryHogs = array_filter($this->metrics, function ($m) {
            return isset($m['memory_mb']) && $m['memory_mb'] > 50;
        });

        $errors = $this->getMetricsByType('error');
        $warnings = $this->getMetricsByType('warning');

        return [
            'total_metrics' => count($this->metrics),
            'slow_calls' => count($slowestCalls),
            'memory_hogs' => count($memoryHogs),
            'errors' => count($errors),
            'warnings' => count($warnings),
            'avg_duration_ms' => $this->calculateAverageDuration(),
            'avg_memory_mb' => $this->calculateAverageMemory(),
        ];
    }

    private function calculateAverageDuration()
    {
        $durations = array_filter(array_map(function ($m) {
            return $m['duration_ms'] ?? null;
        }, $this->metrics));

        if (empty($durations)) {
            return 0;
        }

        return round(array_sum($durations) / count($durations), 2);
    }

    private function calculateAverageMemory()
    {
        $memories = array_filter(array_map(function ($m) {
            return $m['memory_mb'] ?? null;
        }, $this->metrics));

        if (empty($memories)) {
            return 0;
        }

        return round(array_sum($memories) / count($memories), 2);
    }

    public function logQuery(string $query, float $duration, int $rowCount)
    {
        $this->metrics[] = [
            'name' => 'database_query',
            'query' => $query,
            'duration_ms' => round($duration * 1000, 2),
            'row_count' => $rowCount,
            'is_slow' => $duration > 0.5,
            'timestamp' => date('c'),
        ];
    }

    public function logApiCall(string $endpoint, string $method, int $statusCode, float $duration)
    {
        $this->metrics[] = [
            'name' => 'api_call',
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
            'is_slow' => $duration > 1.0,
            'timestamp' => date('c'),
        ];
    }

    public function exportMetrics()
    {
        return json_encode($this->metrics, JSON_PRETTY_PRINT);
    }

    public function exportSummary()
    {
        return json_encode($this->getSummary(), JSON_PRETTY_PRINT);
    }

    public function checkNPlusOneQueries(array $executedQueries)
    {
        $queryGroups = [];

        foreach ($executedQueries as $query) {
            $queryPattern = preg_replace('/\d+/', 'N', $query);
            if (!isset($queryGroups[$queryPattern])) {
                $queryGroups[$queryPattern] = 0;
            }
            $queryGroups[$queryPattern]++;
        }

        foreach ($queryGroups as $pattern => $count) {
            if ($count > 5) {
                $this->recordWarning('Potential N+1 query detected', [
                    'query_pattern' => $pattern,
                    'count' => $count,
                    'suggestion' => 'Consider using JOIN or batch fetching',
                ]);
            }
        }
    }

    public function logMemorySnapshot(string $label)
    {
        $memory = [
            'name' => 'memory_snapshot',
            'label' => $label,
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'timestamp' => date('c'),
        ];

        $this->metrics[] = $memory;

        return $memory;
    }

    public function getSlowQueries(float $thresholdMs = 500)
    {
        return array_filter($this->metrics, function ($m) use ($thresholdMs) {
            return ($m['name'] ?? '') === 'database_query' 
                && isset($m['duration_ms']) 
                && $m['duration_ms'] > $thresholdMs;
        });
    }

    public function getSlowApiCalls(float $thresholdMs = 1000)
    {
        return array_filter($this->metrics, function ($m) use ($thresholdMs) {
            return ($m['name'] ?? '') === 'api_call' 
                && isset($m['duration_ms']) 
                && $m['duration_ms'] > $thresholdMs;
        });
    }
}
