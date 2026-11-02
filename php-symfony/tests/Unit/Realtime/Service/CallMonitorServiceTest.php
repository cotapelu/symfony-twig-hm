<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\CallMonitorService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CallMonitorServiceTest extends TestCase
{
    private CallMonitorService $service;

    protected function setUp(): void
    {
        $this->service = new CallMonitorService();
    }

    public function testStartMonitoringInitializesCallData(): void
    {
        $this->service->startMonitoring('call1', 'user1', ['alice', 'bob']);
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = $ref->getValue($this->service);
        $this->assertArrayHasKey('call1', $calls);
        $this->assertSame('user1', $calls['call1']['user_id']);
        $this->assertSame(['alice', 'bob'], $calls['call1']['participants']);
        $this->assertArrayHasKey('start_time', $calls['call1']);
        $this->assertArrayHasKey('quality_metrics', $calls['call1']);
        $this->assertArrayHasKey('bandwidth_history', $calls['call1']);
        $this->assertArrayHasKey('latency_history', $calls['call1']);
        $this->assertArrayHasKey('packet_loss_history', $calls['call1']);
        $this->assertArrayHasKey('connection_stats', $calls['call1']);
    }

    public function testStopMonitoringReturnsDataAndRemovesCall(): void
    {
        $this->service->startMonitoring('call2', 'user2', []);
        $result = $this->service->stopMonitoring('call2');
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $this->assertArrayNotHasKey('call2', $ref->getValue($this->service));
        $this->assertSame('call2', $result['id']);
        $this->assertArrayHasKey('end_time', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertGreaterThanOrEqual(0, $result['duration']);
    }

    public function testStopMonitoringReturnsNullWhenCallNotFound(): void
    {
        $result = $this->service->stopMonitoring('unknown');
        $this->assertNull($result);
    }

    public function testGetActiveCallReturnsCallOrNull(): void
    {
        $this->service->startMonitoring('call3', 'user3', []);
        $call = $this->service->getActiveCall('call3');
        $this->assertNotNull($call);
        $this->assertSame('call3', $call['id']);
        $this->assertNull($this->service->getActiveCall('unknown'));
    }

    public function testGetActiveCallsCountReturnsInteger(): void
    {
        $this->assertSame(0, $this->service->getActiveCallsCount());
        $this->service->startMonitoring('c1', 'u1', []);
        $this->service->startMonitoring('c2', 'u2', []);
        $this->assertSame(2, $this->service->getActiveCallsCount());
    }

    public function testGetAllActiveCallsReturnsArray(): void
    {
        $this->service->startMonitoring('c1', 'u1', []);
        $this->service->startMonitoring('c2', 'u2', []);
        $all = $this->service->getAllActiveCalls();
        $this->assertIsArray($all);
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('c1', $all);
        $this->assertArrayHasKey('c2', $all);
    }

    public function testUpdateMetricsAddsToQualityMetricsAndHistories(): void
    {
        $this->service->startMonitoring('c1', 'u1', []);
        $metrics = [
            'bandwidth' => 1000,
            'latency' => 50,
            'packetLoss' => 0.5,
            'connectionState' => 'connected',
            'iceConnectionState' => 'connected',
        ];
        $this->service->updateMetrics('c1', $metrics);
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = $ref->getValue($this->service);
        $call = $calls['c1'];
        $this->assertCount(1, $call['quality_metrics']);
        $this->assertCount(1, $call['bandwidth_history']);
        $this->assertCount(1, $call['latency_history']);
        $this->assertCount(1, $call['packet_loss_history']);
        $this->assertCount(1, $call['connection_stats']);
        $this->assertSame(1000, $call['bandwidth_history'][0]['value']);
        $this->assertSame(50, $call['latency_history'][0]['value']);
        $this->assertSame(0.5, $call['packet_loss_history'][0]['value']);
        $this->assertSame('connected', $call['connection_stats'][0]['state']);
    }

    public function testUpdateMetricsIgnoresUnknownCall(): void
    {
        // Should not throw
        $this->service->updateMetrics('unknown', ['bandwidth' => 100]);
        // No assertion needed; just ensure no exception
        $this->assertTrue(true);
    }

    public function testUpdateMetricsTimestampIsAdded(): void
    {
        $this->service->startMonitoring('c1', 'u1', []);
        $before = time();
        $this->service->updateMetrics('c1', ['bandwidth' => 500]);
        $after = time();
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = $ref->getValue($this->service);
        $metric = $calls['c1']['quality_metrics'][0];
        $this->assertArrayHasKey('timestamp', $metric);
        $ts = $metric['timestamp'];
        $this->assertGreaterThanOrEqual($before, $ts);
        $this->assertLessThanOrEqual($after, $ts);
    }

    public function testMetricsAccumulateAcrossUpdates(): void
    {
        $this->service->startMonitoring('c1', 'u1', []);
        $this->service->updateMetrics('c1', ['bandwidth' => 100]);
        $this->service->updateMetrics('c1', ['latency' => 20]);
        $this->service->updateMetrics('c1', ['packetLoss' => 1.5]);
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = $ref->getValue($this->service);
        $this->assertCount(3, $calls['c1']['quality_metrics']);
        $this->assertCount(1, $calls['c1']['bandwidth_history']);
        $this->assertCount(1, $calls['c1']['latency_history']);
        $this->assertCount(1, $calls['c1']['packet_loss_history']);
    }
}
