<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\QualityAnalyticsService;
use App\Realtime\Service\QualityScoreCalculatorService;
use App\Realtime\Service\ConnectionStabilityService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]

class QualityAnalyticsServiceTest extends TestCase
{
    private QualityScoreCalculatorService&MockObject $scoreMock;
    private ConnectionStabilityService&MockObject $stabilityMock;
    private QualityAnalyticsService $service;

    protected function setUp(): void
    {
        $this->scoreMock = $this->createMock(QualityScoreCalculatorService::class);
        $this->stabilityMock = $this->createMock(ConnectionStabilityService::class);
        $this->service = new QualityAnalyticsService($this->scoreMock, $this->stabilityMock);
    }

    public function testGetAnalyticsEmptyInput(): void
    {
        $result = $this->service->getAnalytics([]);

        $this->assertSame(0, $result['total_calls']);
        $this->assertSame(0, $result['average_quality_score']);
        $this->assertSame(0, $result['average_duration']);
        $this->assertSame(0, $result['quality_distribution']['excellent']);
        $this->assertSame(0, $result['common_issues']['high_latency']);
    }

    public function testGetAnalyticsFiltersByDate(): void
    {
        $now = time();
        $days = 10;
        $cutoff = $now - ($days * 24 * 60 * 60);
        $oldTime = $cutoff - 100; // older
        $recentTime = $cutoff + 100; // within cutoff

        $calls = [
            'old_call' => ['start_time' => $oldTime, 'duration' => 60, 'quality_metrics' => []],
            'recent_call' => ['start_time' => $recentTime, 'duration' => 120, 'quality_metrics' => []],
        ];

        $this->scoreMock->expects($this->once())
            ->method('calculateScore')
            ->with($this->callback(fn($data) => $data['start_time'] >= $cutoff))
            ->willReturn(85.0);
        $this->scoreMock->expects($this->once())
            ->method('getQualityLevel')
            ->with(85.0)
            ->willReturn('good');
        $this->stabilityMock->expects($this->once())
            ->method('hasHighLatency')
            ->willReturn(false);
        $this->stabilityMock->expects($this->once())
            ->method('hasPacketLoss')
            ->willReturn(false);
        $this->stabilityMock->expects($this->once())
            ->method('hasLowBandwidth')
            ->willReturn(false);
        $this->stabilityMock->expects($this->once())
            ->method('hasConnectionDrops')
            ->willReturn(false);

        $result = $this->service->getAnalytics($calls, $days);

        $this->assertSame(1, $result['total_calls']);
        $this->assertSame(85.0, $result['average_quality_score']);
        $this->assertSame(120.0, $result['average_duration']);
        $this->assertSame(0, $result['quality_distribution']['excellent']);
        $this->assertSame(1, $result['quality_distribution']['good']);
    }

    public function testGetAnalyticsAggregatesMultipleCalls(): void
    {
        $cutoff = time();
        $calls = [
            'c1' => ['start_time' => $cutoff, 'duration' => 100, 'quality_metrics' => []],
            'c2' => ['start_time' => $cutoff, 'duration' => 200, 'quality_metrics' => []],
        ];

        $this->scoreMock->expects($this->exactly(2))
            ->method('calculateScore')
            ->willReturn(100.0);
        $this->scoreMock->expects($this->exactly(2))
            ->method('getQualityLevel')
            ->with(100.0)
            ->willReturn('excellent');
        $this->stabilityMock->expects($this->exactly(2))
            ->method('hasHighLatency')
            ->willReturn(false);
        $this->stabilityMock->expects($this->exactly(2))
            ->method('hasPacketLoss')
            ->willReturn(false);
        $this->stabilityMock->expects($this->exactly(2))
            ->method('hasLowBandwidth')
            ->willReturn(false);
        $this->stabilityMock->expects($this->exactly(2))
            ->method('hasConnectionDrops')
            ->willReturn(false);

        $result = $this->service->getAnalytics($calls, 30);

        $this->assertSame(2, $result['total_calls']);
        $this->assertSame(100.0, $result['average_quality_score']);
        $this->assertSame(150.0, $result['average_duration']);
        $this->assertSame(2, $result['quality_distribution']['excellent']);
    }

    public function testGetAnalyticsTracksCommonIssues(): void
    {
        $now = time();
        $call = [
            'call1' => [
                'start_time' => $now,
                'duration' => 100,
                'quality_metrics' => [],
                'latency_history' => [1,2,3],
                'packet_loss_history' => [0.1],
                'bandwidth_history' => [500],
                'connection_stats' => ['drops' => 1],
            ],
        ];

        $this->scoreMock->expects($this->once())
            ->method('calculateScore')
            ->willReturn(70.0);
        $this->scoreMock->expects($this->once())
            ->method('getQualityLevel')
            ->with(70.0)
            ->willReturn('good');
        $this->stabilityMock->expects($this->once())
            ->method('hasHighLatency')
            ->with($this->anything())
            ->willReturn(true);
        $this->stabilityMock->expects($this->once())
            ->method('hasPacketLoss')
            ->with($this->anything())
            ->willReturn(true);
        $this->stabilityMock->expects($this->once())
            ->method('hasLowBandwidth')
            ->with($this->anything())
            ->willReturn(false);
        $this->stabilityMock->expects($this->once())
            ->method('hasConnectionDrops')
            ->with($this->anything())
            ->willReturn(true);

        $result = $this->service->getAnalytics($call, 30);

        $this->assertSame(1, $result['common_issues']['high_latency']);
        $this->assertSame(1, $result['common_issues']['packet_loss']);
        $this->assertSame(0, $result['common_issues']['low_bandwidth']);
        $this->assertSame(1, $result['common_issues']['connection_drops']);
    }

    public function testGetAnalyticsDaysParameterFiltersCorrectly(): void
    {
        $now = time();
        $days = 2;
        $cutoff = $now - ($days * 24 * 60 * 60);
        $older = $cutoff - 1;
        $newer = $cutoff + 1;

        $calls = [
            'older' => ['start_time' => $older, 'duration' => 10, 'quality_metrics' => []],
            'newer' => ['start_time' => $newer, 'duration' => 20, 'quality_metrics' => []],
        ];

        $this->scoreMock->expects($this->once())
            ->method('calculateScore')
            ->with($this->callback(fn($data) => $data['start_time'] >= $cutoff))
            ->willReturn(100.0);
        $this->scoreMock->expects($this->once())
            ->method('getQualityLevel')
            ->with(100.0)
            ->willReturn('excellent');
        $this->stabilityMock->expects($this->once())
            ->method('hasHighLatency')
            ->willReturn(false);
        $this->stabilityMock->expects($this->once())
            ->method('hasPacketLoss')
            ->willReturn(false);
        $this->stabilityMock->expects($this->once())
            ->method('hasLowBandwidth')
            ->willReturn(false);
        $this->stabilityMock->expects($this->once())
            ->method('hasConnectionDrops')
            ->willReturn(false);

        $result = $this->service->getAnalytics($calls, $days);

        $this->assertSame(1, $result['total_calls']);
    }

    public function testGetCurrentQuality(): void
    {
        $start = time() - 120;
        $callData = [
            'id' => 'call_123',
            'start_time' => $start,
            'participants' => ['u1', 'u2'],
            'quality_metrics' => [
                ['bandwidth' => 500, 'latency' => 80],
            ],
            'bandwidth_history' => [['value' => 400], ['value' => 500]],
            'latency_history' => [['value' => 100], ['value' => 80]],
            'packet_loss_history' => [['value' => 0.5]],
            'connection_stats' => ['drops' => 0],
        ];

        $this->scoreMock->expects($this->once())
            ->method('calculateScore')
            ->with($callData)
            ->willReturn(88.5);
        $this->scoreMock->expects($this->once())
            ->method('getQualityLevel')
            ->with(88.5)
            ->willReturn('good');

        $capturedTrends = [];
        $trendReturns = ['increasing', 'decreasing', 'stable'];
        $this->scoreMock->expects($this->exactly(3))
            ->method('calculateTrend')
            ->willReturnCallback(function($arg) use (&$capturedTrends, &$trendReturns) {
                $capturedTrends[] = $arg;
                return array_shift($trendReturns);
            });

        $this->stabilityMock->expects($this->once())
            ->method('calculateStability')
            ->with($callData['connection_stats'])
            ->willReturn(0.95);

        $result = $this->service->getCurrentQuality($callData);

        // Verify arguments passed to calculateTrend
        $this->assertCount(3, $capturedTrends);
        $this->assertSame($callData['bandwidth_history'], $capturedTrends[0]);
        $this->assertSame($callData['latency_history'], $capturedTrends[1]);
        $this->assertSame($callData['packet_loss_history'], $capturedTrends[2]);

        $this->assertSame('call_123', $result['call_id']);
        $this->assertSame('active', $result['status']);
        $this->assertSame(2, $result['participants']);
        $this->assertSame(88.5, $result['quality_score']);
        $this->assertSame('good', $result['quality_level']);
        $this->assertSame(['bandwidth' => 500, 'latency' => 80], $result['current_metrics']);
        $this->assertSame('increasing', $result['bandwidth_trend']);
        $this->assertSame('decreasing', $result['latency_trend']);
        $this->assertSame('stable', $result['packet_loss_trend']);
        $this->assertSame(0.95, $result['connection_stability']);
    }

    public function testGetCurrentQualityWithEmptyHistories(): void
    {
        $callData = [
            'id' => 'call_x',
            'start_time' => time() - 50,
            'participants' => ['u'],
            'quality_metrics' => [],
            'bandwidth_history' => [],
            'latency_history' => [],
            'packet_loss_history' => [],
            'connection_stats' => [],
        ];

        $this->scoreMock->expects($this->once())
            ->method('calculateScore')
            ->with($callData)
            ->willReturn(100.0);
        $this->scoreMock->expects($this->once())
            ->method('getQualityLevel')
            ->with(100.0)
            ->willReturn('excellent');
        $this->scoreMock->expects($this->exactly(3))
            ->method('calculateTrend')
            ->willReturnOnConsecutiveCalls('stable', 'stable', 'stable');
        $this->stabilityMock->expects($this->once())
            ->method('calculateStability')
            ->with([])
            ->willReturn(1.0);

        $result = $this->service->getCurrentQuality($callData);

        $this->assertSame([], $result['current_metrics']);
        $this->assertSame('stable', $result['bandwidth_trend']);
    }

    public function testGetAnalyticsWithMixedQualityDistribution(): void
    {
        $now = time();
        $calls = [
            'c1' => ['start_time' => $now, 'duration' => 100, 'quality_metrics' => []],
            'c2' => ['start_time' => $now, 'duration' => 200, 'quality_metrics' => []],
            'c3' => ['start_time' => $now, 'duration' => 150, 'quality_metrics' => []],
        ];

        $scores = [95.0, 60.0, 80.0]; // excellent, fair, good
        $this->scoreMock->expects($this->exactly(3))
            ->method('calculateScore')
            ->willReturnOnConsecutiveCalls(...$scores);
        $this->scoreMock->expects($this->exactly(3))
            ->method('getQualityLevel')
            ->willReturnOnConsecutiveCalls('excellent', 'fair', 'good');
        $this->stabilityMock->expects($this->exactly(3))
            ->method('hasHighLatency')
            ->willReturn(false);
        $this->stabilityMock->expects($this->exactly(3))
            ->method('hasPacketLoss')
            ->willReturn(false);
        $this->stabilityMock->expects($this->exactly(3))
            ->method('hasLowBandwidth')
            ->willReturn(false);
        $this->stabilityMock->expects($this->exactly(3))
            ->method('hasConnectionDrops')
            ->willReturn(false);

        $result = $this->service->getAnalytics($calls, 30);

        $this->assertSame(3, $result['total_calls']);
        $this->assertSame(78.33, $result['average_quality_score']); // (95+60+80)/3 = 78.333...
        $this->assertSame(150.0, $result['average_duration']); // (100+200+150)/3 = 150
        $this->assertSame(1, $result['quality_distribution']['excellent']);
        $this->assertSame(1, $result['quality_distribution']['fair']);
        $this->assertSame(1, $result['quality_distribution']['good']);
        $this->assertSame(0, $result['quality_distribution']['poor']);
    }
}
