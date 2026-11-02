<?php

declare(strict_types=1);

namespace Tests\Unit\Realtime\Service;

use App\Realtime\Service\CallQualityService;
use PHPUnit\Framework\TestCase;

class CallQualityServiceTest extends TestCase
{
    private CallQualityService $service;

    protected function setUp(): void
    {
        $this->service = new CallQualityService();
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $method = new \ReflectionMethod($obj, $method);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    // ==================== startCallMonitoring ====================

    public function testStartCallMonitoring_InitializesActiveCall(): void
    {
        $this->service->startCallMonitoring('call1', 'user1', ['user2']);
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = $ref->getValue($this->service);
        $this->assertArrayHasKey('call1', $calls);
        $this->assertSame('user1', $calls['call1']['user_id']);
        $this->assertSame(['user2'], $calls['call1']['participants']);
        $this->assertArrayHasKey('start_time', $calls['call1']);
        $this->assertEmpty($calls['call1']['quality_metrics']);
    }

    public function testStartCallMonitoring_OverwritesExisting(): void
    {
        $this->service->startCallMonitoring('call1', 'user1', ['user2']);
        $this->service->startCallMonitoring('call1', 'userX', []);
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = $ref->getValue($this->service);
        $this->assertSame('userX', $calls['call1']['user_id']);
        $this->assertCount(0, $calls['call1']['participants']);
    }

    // ==================== stopCallMonitoring ====================

    public function testStopCallMonitoring_NonExistent_ReturnsEmptyArray(): void
    {
        $result = $this->service->stopCallMonitoring('unknown');
        $this->assertSame([], $result);
    }

    public function testStopCallMonitoring_CalculatesDurationAndFinalScore(): void
    {
        // Setup: start call and inject a quality metric via updateQualityMetrics
        $this->service->startCallMonitoring('call1', 'user1', []);
        // Simulate some metrics
        $this->service->updateQualityMetrics('call1', ['latency' => 100, 'packetLoss' => 0.1]);
        // Simulate time passing by manipulating start_time via reflection
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = &$ref->getValue($this->service);
        // artificially set start_time to 10 seconds ago
        $calls['call1']['start_time'] = time() - 10;
        // Now stop
        $result = $this->service->stopCallMonitoring('call1');
        $this->assertArrayHasKey('duration', $result);
        $this->assertGreaterThanOrEqual(0, $result['duration']);
        $this->assertLessThanOrEqual(20, $result['duration']); // near 10
        $this->assertArrayHasKey('final_quality_score', $result);
        $this->assertIsFloat($result['final_quality_score']);
    }

    public function testStopCallMonitoring_MovesToQualityMetrics(): void
    {
        $this->service->startCallMonitoring('call1', 'user1', []);
        $this->service->updateQualityMetrics('call1', ['latency' => 50]);
        $result = $this->service->stopCallMonitoring('call1');
        $ref = new \ReflectionProperty($this->service, 'qualityMetrics');
        $ref->setAccessible(true);
        $metrics = $ref->getValue($this->service);
        $this->assertArrayHasKey('call1', $metrics);
        $this->assertSame($result, $metrics['call1']);
    }

    public function testStopCallMonitoring_RemovesFromActiveCalls(): void
    {
        $this->service->startCallMonitoring('call1', 'user1', []);
        $this->service->stopCallMonitoring('call1');
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = $ref->getValue($this->service);
        $this->assertArrayNotHasKey('call1', $calls);
    }

    // ==================== updateQualityMetrics ====================

    public function testUpdateQualityMetrics_NoActionIfCallNotActive(): void
    {
        $this->service->updateQualityMetrics('unknown', ['latency' => 100]);
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = $ref->getValue($this->service);
        $this->assertEmpty($calls);
    }

    public function testUpdateQualityMetrics_AddsMetricAndHistories(): void
    {
        $this->service->startCallMonitoring('call1', 'user1', []);
        $this->service->updateQualityMetrics('call1', [
            'latency' => 150,
            'packetLoss' => 0.5,
            'bandwidth' => 1024,
            'connectionState' => 'connected',
            'iceConnectionState' => 'connected',
            'iceGatheringState' => 'complete',
        ]);
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = $ref->getValue($this->service);
        $metrics = $calls['call1']['quality_metrics'];
        $this->assertCount(1, $metrics);
        $this->assertSame(150, $metrics[0]['latency']);
        // Histories
        $this->assertCount(1, $calls['call1']['latency_history']);
        $this->assertCount(1, $calls['call1']['packet_loss_history']);
        $this->assertCount(1, $calls['call1']['bandwidth_history']);
        $this->assertCount(1, $calls['call1']['connection_stats']);
        $this->assertSame('connected', $calls['call1']['connection_stats'][0]['state']);
    }

    public function testUpdateQualityMetrics_HandlesPartialMetrics(): void
    {
        $this->service->startCallMonitoring('call1', 'user1', []);
        $this->service->updateQualityMetrics('call1', ['latency' => 200]);
        $this->service->updateQualityMetrics('call1', ['bandwidth' => 512]);
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $calls = $ref->getValue($this->service);
        $this->assertCount(2, $calls['call1']['quality_metrics']);
        $this->assertCount(1, $calls['call1']['latency_history']);
        $this->assertCount(1, $calls['call1']['bandwidth_history']);
    }

    // ==================== getCallQuality ====================

    public function testGetCallQuality_ReturnsDataForActiveCall(): void
    {
        $this->service->startCallMonitoring('call1', 'user1', []);
        $this->service->updateQualityMetrics('call1', ['latency' => 100]);
        $result = $this->service->getCallQuality('call1', 'user1');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('call_id', $result);
        $this->assertSame('call1', $result['call_id']);
        $this->assertArrayHasKey('current_metrics', $result);
        $this->assertSame(100, $result['current_metrics']['latency']);
        $this->assertArrayHasKey('timestamp', $result['current_metrics']);
    }

    public function testGetCallQuality_ReturnsEmptyIfCallNotFound(): void
    {
        $result = $this->service->getCallQuality('unknown', 'user1');
        $this->assertSame(['status' => 'not_found'], $result);
    }

    // ==================== getQualityAnalytics ====================

    public function testGetQualityAnalytics_NoDataReturnsEmptyStats(): void
    {
        $analytics = $this->service->getQualityAnalytics(1);
        $this->assertIsArray($analytics);
        $this->assertSame(0, $analytics['total_calls']);
        $this->assertSame(0, $analytics['average_quality_score']);
        $this->assertSame(0, $analytics['average_duration']);
        $this->assertArrayHasKey('quality_distribution', $analytics);
    }

    public function testQualityAnalytics_AggregatesData(): void
    {
        // Prepare two completed calls with scores
        $now = time();
        $callData1 = [
            'call_id' => 'call1',
            'user_id' => 'u1',
            'start_time' => $now - 40, // within 1 day
            'end_time' => $now - 30,
            'duration' => 10,
            'final_quality_score' => 85.0,
            'quality_metrics' => [
                ['latency' => 100, 'timestamp' => $now-30],
            ],
        ];
        $callData2 = [
            'call_id' => 'call2',
            'user_id' => 'u2',
            'start_time' => $now - 20, // within 1 day
            'end_time' => $now - 10,
            'duration' => 10,
            'final_quality_score' => 70.5,
            'quality_metrics' => [
                ['latency' => 200],
            ],
        ];
        // Inject into qualityMetrics
        $ref = new \ReflectionProperty($this->service, 'qualityMetrics');
        $ref->setAccessible(true);
        $metrics = $ref->getValue($this->service);
        $metrics['call1'] = $callData1;
        $metrics['call2'] = $callData2;
        $ref->setValue($this->service, $metrics);

        $analytics = $this->service->getQualityAnalytics(1); // last day

        // Verify structure
        $this->assertIsArray($analytics);
        $this->assertSame(2, $analytics['total_calls']);
        $this->assertSame(77.75, $analytics['average_quality_score']);
        $this->assertSame(10.0, $analytics['average_duration']);
        $this->assertArrayHasKey('quality_distribution', $analytics);
        $this->assertSame(0, $analytics['quality_distribution']['excellent']);
        $this->assertSame(2, $analytics['quality_distribution']['good']);
        $this->assertSame(0, $analytics['quality_distribution']['fair']);
        $this->assertSame(0, $analytics['quality_distribution']['poor']);
    }

    // ==================== getActiveCallsCount & getActiveCalls ====================

    public function testGetActiveCallsCount_ReturnsCorrectCount(): void
    {
        $this->service->startCallMonitoring('c1', 'u1', []);
        $this->service->startCallMonitoring('c2', 'u2', []);
        $this->assertSame(2, $this->service->getActiveCallsCount());
    }

    public function testGetActiveCalls_ReturnsCopyOfActiveCalls(): void
    {
        $this->service->startCallMonitoring('c1', 'u1', []);
        $calls = $this->service->getActiveCalls();
        $this->assertArrayHasKey('c1', $calls);
        // Ensure it's a copy
        $calls['c1']['user_id'] = 'hacked';
        $ref = new \ReflectionProperty($this->service, 'activeCalls');
        $ref->setAccessible(true);
        $original = $ref->getValue($this->service);
        $this->assertSame('u1', $original['c1']['user_id']);
    }
}
