<?php

declare(strict_types=1);

namespace App\Realtime\Service;

/**
 * Quality Analytics Service
 * Generates quality analytics and reports for calls.
 */
class QualityAnalyticsService
{
    private QualityScoreCalculatorService $scoreCalculator;
    private ConnectionStabilityService $stabilityService;

    public function __construct(
        QualityScoreCalculatorService $scoreCalculator,
        ConnectionStabilityService $stabilityService
    ) {
        $this->scoreCalculator = $scoreCalculator;
        $this->stabilityService = $stabilityService;
    }

    public function getAnalytics(array $qualityMetrics, int $days = 30)
    {
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $analytics = $this->initializeAnalytics();
        $totalQualityScore = 0;
        $totalDuration = 0;

        foreach ($qualityMetrics as $callData) {
            if ($callData['start_time'] < $cutoffTime) {
                continue;
            }
            [$score, $duration] = $this->processCallData($analytics, $callData);
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

    private function processCallData(array &$analytics, array $callData): array
    {
        ++$analytics['total_calls'];

        $finalScore = $this->scoreCalculator->calculateScore($callData);
        $level = $this->scoreCalculator->getQualityLevel($finalScore);
        ++$analytics['quality_distribution'][$level];

        $this->trackIssue($analytics, $callData, 'high_latency', 'latency_history');
        $this->trackIssue($analytics, $callData, 'packet_loss', 'packet_loss_history');
        $this->trackIssue($analytics, $callData, 'low_bandwidth', 'bandwidth_history');
        $this->trackIssue($analytics, $callData, 'connection_drops', 'connection_stats');

        return [$finalScore, $callData['duration']];
    }

    private function trackIssue(array &$analytics, array $callData, string $issueKey, string $historyKey): void
    {
        $history = $callData[$historyKey] ?? [];
        if ($issueKey === 'high_latency' && $this->stabilityService->hasHighLatency($history)) {
            ++$analytics['common_issues'][$issueKey];
        }
        if ($issueKey === 'packet_loss' && $this->stabilityService->hasPacketLoss($history)) {
            ++$analytics['common_issues'][$issueKey];
        }
        if ($issueKey === 'low_bandwidth' && $this->stabilityService->hasLowBandwidth($history)) {
            ++$analytics['common_issues'][$issueKey];
        }
        if ($issueKey === 'connection_drops' && $this->stabilityService->hasConnectionDrops($history)) {
            ++$analytics['common_issues'][$issueKey];
        }
    }

    public function getCurrentQuality(array $callData)
    {
        $qualityScore = $this->scoreCalculator->calculateScore($callData);
        $currentMetrics = end($callData['quality_metrics']) ?: [];

        return [
            'call_id' => $callData['id'],
            'status' => 'active',
            'duration' => time() - $callData['start_time'],
            'participants' => count($callData['participants']),
            'quality_score' => $qualityScore,
            'quality_level' => $this->scoreCalculator->getQualityLevel($qualityScore),
            'current_metrics' => $currentMetrics,
            'bandwidth_trend' => $this->scoreCalculator->calculateTrend($callData['bandwidth_history'] ?? []),
            'latency_trend' => $this->scoreCalculator->calculateTrend($callData['latency_history'] ?? []),
            'packet_loss_trend' => $this->scoreCalculator->calculateTrend($callData['packet_loss_history'] ?? []),
            'connection_stability' => $this->stabilityService->calculateStability($callData['connection_stats'] ?? []),
        ];
    }
}
