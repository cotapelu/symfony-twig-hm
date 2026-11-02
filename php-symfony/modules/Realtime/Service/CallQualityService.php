<?php

declare(strict_types=1);

namespace App\Realtime\Service;


/**
 * Call Quality Monitoring Service
 * Tracks WebRTC connection quality, bandwidth, latency, and packet loss.
 */
class CallQualityService
{
    private array $activeCalls = [];
    private array $qualityMetrics = [];

    public function __construct(
    ) {
    }

    /**
     * Start monitoring a call.
     */
    public function startCallMonitoring(string $callId, string $userId, array $participants)
    {
        $this->activeCalls[$callId] = [
            'id' => $callId,
            'user_id' => $userId,
            'participants' => $participants,
            'start_time' => time(),
            'quality_metrics' => [],
            'connection_stats' => [],
            'bandwidth_history' => [],
            'latency_history' => [],
            'packet_loss_history' => [],
        ];
    }

    /**
     * Stop monitoring a call.
     */
    public function stopCallMonitoring(string $callId)
    {
        if (!isset($this->activeCalls[$callId])) {
            return [];
        }

        $callData = $this->activeCalls[$callId];
        $callData['end_time'] = time();
        $callData['duration'] = $callData['end_time'] - $callData['start_time'];

        // Calculate final quality score
        $callData['final_quality_score'] = $this->calculateQualityScore($callData);

        // Store in quality metrics for analytics
        $this->qualityMetrics[$callId] = $callData;

        unset($this->activeCalls[$callId]);

        return $callData;
    }

    /**
     * Update call quality metrics.
     */
    public function updateQualityMetrics(string $callId, array $metrics)
    {
        if (!isset($this->activeCalls[$callId])) {
            return;
        }

        $call = &$this->activeCalls[$callId];
        $timestamp = time();

        // Store metrics with timestamp
        $metrics['timestamp'] = $timestamp;
        $call['quality_metrics'][] = $metrics;

        // Update specific metric histories
        if (isset($metrics['bandwidth'])) {
            $call['bandwidth_history'][] = [
                'timestamp' => $timestamp,
                'value' => $metrics['bandwidth'],
            ];
        }

        if (isset($metrics['latency'])) {
            $call['latency_history'][] = [
                'timestamp' => $timestamp,
                'value' => $metrics['latency'],
            ];
        }

        if (isset($metrics['packetLoss'])) {
            $call['packet_loss_history'][] = [
                'timestamp' => $timestamp,
                'value' => $metrics['packetLoss'],
            ];
        }

        // Update connection stats
        if (isset($metrics['connectionState'])) {
            $call['connection_stats'][] = [
                'timestamp' => $timestamp,
                'state' => $metrics['connectionState'],
                'iceConnectionState' => $metrics['iceConnectionState'] ?? null,
                'iceGatheringState' => $metrics['iceGatheringState'] ?? null,
            ];
        }
    }

    /**
     * Get current call quality for a user.
     */
    public function getCallQuality(string $callId, string $userId)
    {
        if (!isset($this->activeCalls[$callId])) {
            return ['status' => 'not_found'];
        }

        $call = $this->activeCalls[$callId];

        // Calculate current quality metrics
        $currentMetrics = end($call['quality_metrics']) ?: [];
        $qualityScore = $this->calculateQualityScore($call);

        return [
            'call_id' => $callId,
            'status' => 'active',
            'duration' => time() - $call['start_time'],
            'participants' => count($call['participants']),
            'quality_score' => $qualityScore,
            'current_metrics' => $currentMetrics,
            'bandwidth_trend' => $this->calculateTrend($call['bandwidth_history']),
            'latency_trend' => $this->calculateTrend($call['latency_history']),
            'packet_loss_trend' => $this->calculateTrend($call['packet_loss_history']),
            'connection_stability' => $this->calculateConnectionStability($call['connection_stats']),
        ];
    }

    /**
     * Get quality analytics for a time period.
     */
    public function getQualityAnalytics(int $days = 30)
    {
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $analytics = $this->initializeAnalytics();
        $totalQualityScore = 0;
        $totalDuration = 0;

        foreach ($this->qualityMetrics as $callData) {
            if ($callData['start_time'] < $cutoffTime) {
                continue;
            }
            [$score, $duration] = $this->processCallMetrics($analytics, $callData);
            $totalQualityScore += $score;
            $totalDuration += $duration;
        }

        if ($analytics['total_calls'] > 0) {
            $analytics['average_quality_score'] = round($totalQualityScore / $analytics['total_calls'], 2);
            $analytics['average_duration'] = round($totalDuration / $analytics['total_calls'], 2);
        }

        return $analytics;
    }

    private function initializeAnalytics(): array
    {
        return [
            'total_calls' => 0,
            'average_quality_score' => 0,
            'quality_distribution' => [
                'excellent' => 0,
                'good' => 0,
                'fair' => 0,
                'poor' => 0,
            ],
            'common_issues' => [
                'high_latency' => 0,
                'packet_loss' => 0,
                'low_bandwidth' => 0,
                'connection_drops' => 0,
            ],
            'average_duration' => 0,
            'peak_concurrent_calls' => 0,
        ];
    }

    private function processCallMetrics(array &$analytics, array $callData): array
    {
        ++$analytics['total_calls'];
        $score = $callData['final_quality_score'];
        $this->categorizeScore($analytics, $score);
        $this->trackIssues($analytics, $callData);
        return [$score, $callData['duration']];
    }

    private function categorizeScore(array &$analytics, float $score): void
    {
        if ($score > 90) {
            $analytics['quality_distribution']['excellent']++;
        } elseif ($score > 70) {
            $analytics['quality_distribution']['good']++;
        } elseif ($score > 50) {
            $analytics['quality_distribution']['fair']++;
        } else {
            $analytics['quality_distribution']['poor']++;
        }
    }

    private function trackIssues(array &$analytics, array $callData): void
    {
        if ($this->hasHighLatency($callData)) {
            ++$analytics['common_issues']['high_latency'];
        }
        if ($this->hasPacketLoss($callData)) {
            ++$analytics['common_issues']['packet_loss'];
        }
        if ($this->hasLowBandwidth($callData)) {
            ++$analytics['common_issues']['low_bandwidth'];
        }
        if ($this->hasConnectionDrops($callData)) {
            ++$analytics['common_issues']['connection_drops'];
        }
    }

    /**
     * Calculate overall quality score (0-100).
     */
    private function calculateQualityScore(array $callData): float
    {
        if (empty($callData['quality_metrics'])) {
            return 100.0;
        }

        $scores = [];
        foreach ($callData['quality_metrics'] as $metric) {
            $score = 100;
            $score += $this->calculateBandwidthImpact($metric['bandwidth'] ?? null);
            $score += $this->calculateLatencyImpact($metric['latency'] ?? null);
            $score += $this->calculatePacketLossImpact($metric['packetLoss'] ?? null);
            $scores[] = max(0, $score);
        }

        $recentScores = array_slice($scores, -10);
        return round(array_sum($recentScores) / count($recentScores), 2);
    }

    private function calculateBandwidthImpact(?int $bw): int
    {
        if ($bw === null) return 0;
        if ($bw < 100) return -50;
        if ($bw < 500) return -25;
        if ($bw < 1000) return -10;
        return 0;
    }

    private function calculateLatencyImpact(?int $lat): int
    {
        if ($lat === null) return 0;
        if ($lat > 500) return -50;
        if ($lat > 200) return -25;
        if ($lat > 100) return -10;
        return 0;
    }

    private function calculatePacketLossImpact(?float $loss): int
    {
        if ($loss === null) return 0;
        if ($loss > 10) return -50;
        if ($loss > 5) return -25;
        if ($loss > 1) return -10;
        return 0;
    }

    /**
     * Calculate trend for a metric series.
     */
    private function calculateTrend(array $history)
    {
        if (count($history) < 2) {
            return 'stable';
        }

        $recent = array_slice($history, -5); // Last 5 measurements
        $values = array_column($recent, 'value');

        if (empty($values)) {
            return 'stable';
        }

        $first = $values[0];
        $last = end($values);
        $change = (($last - $first) / $first) * 100;

        if ($change > 10) {
            return 'increasing';
        }
        if ($change < -10) {
            return 'decreasing';
        }

        return 'stable';
    }

    /**
     * Calculate connection stability.
     */
    private function calculateConnectionStability(array $connectionStats)
    {
        if (empty($connectionStats)) {
            return 100.0;
        }

        $totalConnections = count($connectionStats);
        $stableConnections = 0;

        foreach ($connectionStats as $stat) {
            if (($stat['state'] ?? '') === 'connected'
                && ($stat['iceConnectionState'] ?? '') === 'connected') {
                ++$stableConnections;
            }
        }

        return round(($stableConnections / $totalConnections) * 100, 2);
    }

    /**
     * Check if call had high latency issues.
     */
    private function hasHighLatency(array $callData): bool
    {
        if (empty($callData['latency_history']) || !is_array($callData['latency_history'])) {
            return false;
        }
        foreach ($callData['latency_history'] as $measurement) {
            if ($measurement['value'] > 200) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if call had packet loss issues.
     */
    private function hasPacketLoss(array $callData): bool
    {
        if (empty($callData['packet_loss_history']) || !is_array($callData['packet_loss_history'])) {
            return false;
        }
        foreach ($callData['packet_loss_history'] as $measurement) {
            if ($measurement['value'] > 2) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if call had low bandwidth issues.
     */
    private function hasLowBandwidth(array $callData): bool
    {
        if (empty($callData['bandwidth_history']) || !is_array($callData['bandwidth_history'])) {
            return false;
        }
        foreach ($callData['bandwidth_history'] as $measurement) {
            if ($measurement['value'] < 500) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if call had connection drops.
     */
    private function hasConnectionDrops(array $callData): bool
    {
        if (empty($callData['connection_stats']) || !is_array($callData['connection_stats'])) {
            return false;
        }
        $disconnectedCount = 0;
        foreach ($callData['connection_stats'] as $stat) {
            if (($stat['state'] ?? '') === 'disconnected'
                || ($stat['iceConnectionState'] ?? '') === 'disconnected') {
                ++$disconnectedCount;
            }
        }
        return $disconnectedCount > 0;
    }

    /**
     * Get active calls count.
     */
    public function getActiveCallsCount()
    {
        return count($this->activeCalls);
    }

    /**
     * Get all active calls.
     */
    public function getActiveCalls()
    {
        return $this->activeCalls;
    }
}
