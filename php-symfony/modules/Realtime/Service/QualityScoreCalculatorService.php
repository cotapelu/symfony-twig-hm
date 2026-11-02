<?php

declare(strict_types=1);

namespace App\Realtime\Service;

/**
 * Quality Score Calculator Service
 * Calculates quality scores based on WebRTC metrics.
 */
class QualityScoreCalculatorService
{
    public function calculateScore(array $callData): float
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

    public function calculateTrend(array $history)
    {
        if (count($history) < 2) {
            return 'stable';
        }

        $recent = array_slice($history, -5);
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

    public function getQualityLevel(float $score)
    {
        if ($score > 90) {
            return 'excellent';
        } elseif ($score > 70) {
            return 'good';
        } elseif ($score > 50) {
            return 'fair';
        }
        return 'poor';
    }
}
