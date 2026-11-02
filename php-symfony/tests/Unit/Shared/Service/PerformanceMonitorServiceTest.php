<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\PerformanceMonitorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class PerformanceMonitorServiceTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private PerformanceMonitorService $service;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new PerformanceMonitorService($this->logger);
    }

    public function testTimerStartAndEnd(): void
    {
        $this->service->startTimer('test');
        usleep(1000); // small delay
        $result = $this->service->endTimer('test');

        $this->assertIsArray($result);
        $this->assertSame('test', $result['name']);
        $this->assertArrayHasKey('duration_ms', $result);
        $this->assertGreaterThan(0, $result['duration_ms']);
        $this->assertArrayHasKey('memory_bytes', $result);
        $this->assertArrayHasKey('memory_mb', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertSame(0, $this->service->getMetricsByType('timer')->length ?? 0); // actually metrics array includes it
        $metrics = $this->service->getMetrics();
        $this->assertCount(1, $metrics);
    }

    public function testEndTimerWithoutStartReturnsEmpty(): void
    {
        $result = $this->service->endTimer('nonexistent');
        $this->assertSame([], $result);
        $this->assertCount(0, $this->service->getMetrics());
    }

    public function testRecordMetric(): void
    {
        $this->service->recordMetric('custom', 123.45, ['tag' => 'value']);
        $metrics = $this->service->getMetrics();
        $this->assertCount(1, $metrics);
        $metric = $metrics[0];
        $this->assertSame('custom', $metric['name']);
        $this->assertSame(123.45, $metric['value']);
        $this->assertArrayHasKey('timestamp', $metric);
        $this->assertSame(['tag' => 'value'], $metric['tags'] ?? []);
    }

    public function testRecordErrorLogsAndRecordsMetric(): void
    {
        $this->logger->expects($this->once())->method('error')->with('msg', ['k' => 'v']);
        $this->service->recordError('msg', ['k' => 'v']);
        $metrics = $this->service->getMetrics();
        $this->assertCount(1, $metrics);
        $this->assertSame('error', $metrics[0]['name']);
        $this->assertSame('msg', $metrics[0]['message']);
    }

    public function testRecordWarningLogsAndRecordsMetric(): void
    {
        $this->logger->expects($this->once())->method('warning')->with('warn msg', []);
        $this->service->recordWarning('warn msg');
        $metrics = $this->service->getMetrics();
        $this->assertCount(1, $metrics);
        $this->assertSame('warning', $metrics[0]['name']);
    }

    public function testGetMetricsByType(): void
    {
        $this->service->recordMetric('cpu', 80);
        $this->service->recordMetric('mem', 50);
        $cpu = $this->service->getMetricsByType('cpu');
        $this->assertCount(1, $cpu);
        $this->assertSame('cpu', $cpu[0]['name']);
    }

    public function testClearMetrics(): void
    {
        $this->service->recordMetric('a', 1);
        $this->service->recordMetric('b', 2);
        $this->assertCount(2, $this->service->getMetrics());
        $this->service->clearMetrics();
        $this->assertCount(0, $this->service->getMetrics());
    }

    public function testGetSummaryCalculations(): void
    {
        // Add timer metrics with durations and memory
        for ($i = 0; $i < 3; $i++) {
            $this->service->startTimer('call');
            usleep(500 + $i * 100); // vary
            $this->service->endTimer('call');
        }
        // Add an error
        $this->logger->expects($this->once())->method('error');
        $this->service->recordError('fail');

        $summary = $this->service->getSummary();
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_metrics', $summary);
        $this->assertSame(4, $summary['total_metrics']);
        $this->assertArrayHasKey('slow_calls', $summary);
        $this->assertArrayHasKey('memory_hogs', $summary);
        $this->assertArrayHasKey('errors', $summary);
        $this->assertArrayHasKey('warnings', $summary);
        $this->assertSame(1, $summary['errors']);
    }

    public function testLogQuery(): void
    {
        $this->service->logQuery('SELECT * FROM users', 0.2, 10);
        $metrics = $this->service->getMetrics();
        $this->assertCount(1, $metrics);
        $q = $metrics[0];
        $this->assertSame('database_query', $q['name']);
        $this->assertSame('SELECT * FROM users', $q['query']);
        $this->assertEquals(200, $q['duration_ms']);
        $this->assertSame(10, $q['row_count']);
        $this->assertFalse($q['is_slow']); // threshold 0.5s => 0.2 < 0.5
    }

    public function testLogApiCall(): void
    {
        $this->service->logApiCall('/api/test', 'GET', 200, 0.5);
        $metrics = $this->service->getMetrics();
        $this->assertCount(1, $metrics);
        $api = $metrics[0];
        $this->assertSame('api_call', $api['name']);
        $this->assertSame('/api/test', $api['endpoint']);
        $this->assertSame('GET', $api['method']);
        $this->assertSame(200, $api['status_code']);
        $this->assertEquals(500, $api['duration_ms']);
        $this->assertFalse($api['is_slow']); // threshold 1s
    }

    public function testExportMetricsAndSummary(): void
    {
        $this->service->recordMetric('x', 1);
        $jsonMetrics = $this->service->exportMetrics();
        $this->assertIsString($jsonMetrics);
        $decoded = json_decode($jsonMetrics, true);
        $this->assertCount(1, $decoded);

        $jsonSummary = $this->service->exportSummary();
        $this->assertIsString($jsonSummary);
        $summary = json_decode($jsonSummary, true);
        $this->assertArrayHasKey('total_metrics', $summary);
    }

    public function testCheckNPlusOneQueriesRecordsWarningWhenPatternCountExceedsThreshold(): void
    {
        $queries = [];
        for ($i = 0; $i < 6; $i++) {
            $queries[] = "SELECT * FROM users WHERE id = $i";
        }
        $this->logger->expects($this->once())->method('warning')->with($this->stringContains('Potential N+1 query'), $this->arrayHasKey('query_pattern'));
        $this->service->checkNPlusOneQueries($queries);
        $metrics = $this->service->getMetrics();
        $warnings = $this->service->getMetricsByType('warning');
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('SELECT * FROM users WHERE id = N', $warnings[0]['context']['query_pattern']);
    }

    public function testCheckNPlusOneQueriesNoWarningWhenBelowThreshold(): void
    {
        $queries = [];
        for ($i = 0; $i < 3; $i++) {
            $queries[] = "SELECT * FROM users WHERE id = $i";
        }
        $this->logger->expects($this->never())->method('warning');
        $this->service->checkNPlusOneQueries($queries);
        $warnings = $this->service->getMetricsByType('warning');
        $this->assertCount(0, $warnings);
    }

    public function testLogMemorySnapshot(): void
    {
        $snapshot = $this->service->logMemorySnapshot('test-phase');
        $this->assertIsArray($snapshot);
        $this->assertSame('memory_snapshot', $snapshot['name']);
        $this->assertSame('test-phase', $snapshot['label']);
        $this->assertArrayHasKey('current_mb', $snapshot);
        $this->assertArrayHasKey('peak_mb', $snapshot);
        $metrics = $this->service->getMetrics();
        $this->assertCount(1, $metrics);
    }

    public function testGetSlowQueriesFiltersByThreshold(): void
    {
        $this->service->logQuery('q1', 0.1, 5); // 100ms
        $this->service->logQuery('q2', 0.6, 10); // 600ms
        $this->service->logQuery('q3', 1.5, 20); // 1500ms
        $slow = $this->service->getSlowQueries(500); // threshold 500ms
        $this->assertCount(2, $slow);
        $names = array_column($slow, 'query');
        $this->assertContains('q2', $names);
        $this->assertContains('q3', $names);
    }

    public function testGetSlowApiCallsFiltersByThreshold(): void
    {
        $this->service->logApiCall('/a', 'GET', 200, 0.5); // 500ms
        $this->service->logApiCall('/b', 'POST', 201, 2.0); // 2000ms
        $slow = $this->service->getSlowApiCalls(1000);
        $this->assertCount(1, $slow);
        $first = array_values($slow)[0];
        $this->assertSame('/b', $first['endpoint']);
    }
}
