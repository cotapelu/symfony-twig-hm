<?php

declare(strict_types=1);

namespace App\Realtime\Service;

/**
 * Call Monitor Service
 * Manages active call monitoring lifecycle.
 */
class CallMonitorService
{
    private array $activeCalls = [];

    public function startMonitoring(string $callId, string $userId, array $participants)
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

    public function stopMonitoring(string $callId)
    {
        if (!isset($this->activeCalls[$callId])) {
            return null;
        }

        $callData = $this->activeCalls[$callId];
        $callData['end_time'] = time();
        $callData['duration'] = $callData['end_time'] - $callData['start_time'];

        unset($this->activeCalls[$callId]);

        return $callData;
    }

    public function getActiveCall(string $callId)
    {
        return $this->activeCalls[$callId] ?? null;
    }

    public function updateMetrics(string $callId, array $metrics)
    {
        if (!isset($this->activeCalls[$callId])) {
            return;
        }

        $call = &$this->activeCalls[$callId];
        $timestamp = time();
        $metrics['timestamp'] = $timestamp;
        $call['quality_metrics'][] = $metrics;

        if (isset($metrics['bandwidth'])) {
            $call['bandwidth_history'][] = ['timestamp' => $timestamp, 'value' => $metrics['bandwidth']];
        }

        if (isset($metrics['latency'])) {
            $call['latency_history'][] = ['timestamp' => $timestamp, 'value' => $metrics['latency']];
        }

        if (isset($metrics['packetLoss'])) {
            $call['packet_loss_history'][] = ['timestamp' => $timestamp, 'value' => $metrics['packetLoss']];
        }

        if (isset($metrics['connectionState'])) {
            $call['connection_stats'][] = [
                'timestamp' => $timestamp,
                'state' => $metrics['connectionState'],
                'iceConnectionState' => $metrics['iceConnectionState'] ?? null,
                'iceGatheringState' => $metrics['iceGatheringState'] ?? null,
            ];
        }
    }

    public function getActiveCallsCount()
    {
        return count($this->activeCalls);
    }

    public function getAllActiveCalls()
    {
        return $this->activeCalls;
    }
}
