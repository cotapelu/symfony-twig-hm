<?php

declare(strict_types=1);

namespace App\Realtime\Service;

/**
 * Connection Stability Service
 * Analyzes WebRTC connection stability and detects issues.
 */
class ConnectionStabilityService
{
    public function calculateStability(array $connectionStats)
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

    public function hasConnectionDrops(array $connectionStats)
    {
        foreach ($connectionStats as $stat) {
            if (($stat['state'] ?? '') === 'disconnected'
                || ($stat['iceConnectionState'] ?? '') === 'disconnected') {
                return true;
            }
        }
        return false;
    }

    public function hasHighLatency(array $latencyHistory, int $threshold = 200)
    {
        foreach ($latencyHistory as $measurement) {
            if ($measurement['value'] > $threshold) {
                return true;
            }
        }
        return false;
    }

    public function hasPacketLoss(array $packetLossHistory, int $threshold = 2)
    {
        foreach ($packetLossHistory as $measurement) {
            if ($measurement['value'] > $threshold) {
                return true;
            }
        }
        return false;
    }

    public function hasLowBandwidth(array $bandwidthHistory, int $threshold = 500)
    {
        foreach ($bandwidthHistory as $measurement) {
            if ($measurement['value'] < $threshold) {
                return true;
            }
        }
        return false;
    }
}
