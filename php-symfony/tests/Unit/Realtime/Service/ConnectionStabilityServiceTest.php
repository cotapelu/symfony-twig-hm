<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\ConnectionStabilityService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]

class ConnectionStabilityServiceTest extends TestCase
{
    private ConnectionStabilityService $service;

    protected function setUp(): void
    {
        $this->service = new ConnectionStabilityService();
    }

    public function testCalculateStabilityEmpty(): void
    {
        $this->assertSame(100.0, $this->service->calculateStability([]));
    }

    public function testCalculateStabilityAllStable(): void
    {
        $stats = [
            ['state' => 'connected', 'iceConnectionState' => 'connected'],
            ['state' => 'connected', 'iceConnectionState' => 'connected'],
        ];
        $this->assertSame(100.0, $this->service->calculateStability($stats));
    }

    public function testCalculateStabilityMixed(): void
    {
        $stats = [
            ['state' => 'connected', 'iceConnectionState' => 'connected'],
            ['state' => 'disconnected', 'iceConnectionState' => 'connected'],
            ['state' => 'connected', 'iceConnectionState' => 'disconnected'],
        ];
        $this->assertSame(33.33, $this->service->calculateStability($stats));
    }

    public function testCalculateStabilityAllUnstable(): void
    {
        $stats = [
            ['state' => 'disconnected', 'iceConnectionState' => 'connected'],
            ['state' => 'connected', 'iceConnectionState' => 'disconnected'],
        ];
        $this->assertSame(0.0, $this->service->calculateStability($stats));
    }

    public function testHasConnectionDropsFalse(): void
    {
        $stats = [
            ['state' => 'connected', 'iceConnectionState' => 'connected'],
            ['state' => 'connecting', 'iceConnectionState' => 'checking'],
        ];
        $this->assertFalse($this->service->hasConnectionDrops($stats));
    }

    public function testHasConnectionDropsTrue(): void
    {
        $stats = [
            ['state' => 'connected', 'iceConnectionState' => 'connected'],
            ['state' => 'disconnected', 'iceConnectionState' => 'connected'],
        ];
        $this->assertTrue($this->service->hasConnectionDrops($stats));
    }

    public function testHasHighLatencyFalse(): void
    {
        $history = [
            ['value' => 100],
            ['value' => 150],
        ];
        $this->assertFalse($this->service->hasHighLatency($history));
    }

    public function testHasHighLatencyTrue(): void
    {
        $history = [
            ['value' => 100],
            ['value' => 250], // exceeds 200
        ];
        $this->assertTrue($this->service->hasHighLatency($history));
    }

    public function testHasHighLatencyCustomThreshold(): void
    {
        $history = [
            ['value' => 300],
        ];
        $this->assertFalse($this->service->hasHighLatency($history, 350));
        $this->assertTrue($this->service->hasHighLatency($history, 250));
    }

    public function testHasPacketLossFalse(): void
    {
        $history = [
            ['value' => 0.5],
            ['value' => 1.0],
        ];
        $this->assertFalse($this->service->hasPacketLoss($history));
    }

    public function testHasPacketLossTrue(): void
    {
        $history = [
            ['value' => 0.5],
            ['value' => 2.5], // exceeds 2
        ];
        $this->assertTrue($this->service->hasPacketLoss($history));
    }

    public function testHasLowBandwidthFalse(): void
    {
        $history = [
            ['value' => 600],
            ['value' => 1000],
        ];
        $this->assertFalse($this->service->hasLowBandwidth($history));
    }

    public function testHasLowBandwidthTrue(): void
    {
        $history = [
            ['value' => 600],
            ['value' => 400], // below 500
        ];
        $this->assertTrue($this->service->hasLowBandwidth($history));
    }

    public function testEdgeCasesWithMissingKeys(): void
    {
        $stats = [
            ['state' => 'connected'], // missing iceConnectionState -> not stable
            ['iceConnectionState' => 'connected'], // missing state -> not stable
            [], // neither -> not stable
        ];
        $this->assertSame(0.0, $this->service->calculateStability($stats));
        $this->assertFalse($this->service->hasConnectionDrops($stats));

        $latency = [['value' => 100], ['value' => 300]];
        $this->assertTrue($this->service->hasHighLatency($latency));
        $this->assertFalse($this->service->hasPacketLoss([['value' => 0.1]]));
        $this->assertFalse($this->service->hasLowBandwidth([['value' => 1000]]));
    }
}
