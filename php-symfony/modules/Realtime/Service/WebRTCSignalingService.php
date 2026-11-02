<?php

declare(strict_types=1);

namespace App\Realtime\Service;

class WebRTCSignalingService
{
    private array $rooms = [];

    public function createRoom(string $roomId, string $userId)
    {
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [
                'participants' => [],
                'offers' => [],
                'answers' => [],
                'candidates' => [],
                'created_at' => time(),
            ];
        }

        $this->rooms[$roomId]['participants'][$userId] = [
            'joined_at' => time(),
            'status' => 'connected',
        ];

        return [
            'roomId' => $roomId,
            'participants' => array_keys($this->rooms[$roomId]['participants']),
        ];
    }

    public function joinRoom(string $roomId, string $userId)
    {
        if (!isset($this->rooms[$roomId])) {
            return null;
        }

        $this->rooms[$roomId]['participants'][$userId] = [
            'joined_at' => time(),
            'status' => 'connected',
        ];

        return [
            'roomId' => $roomId,
            'participants' => array_keys($this->rooms[$roomId]['participants']),
            'offers' => $this->rooms[$roomId]['offers'],
        ];
    }

    public function sendOffer(string $roomId, string $userId, array $offer)
    {
        if (!isset($this->rooms[$roomId])) {
            return;
        }

        $this->rooms[$roomId]['offers'][$userId] = [
            'offer' => $offer,
            'timestamp' => time(),
        ];
    }

    public function sendAnswer(string $roomId, string $userId, array $answer)
    {
        if (!isset($this->rooms[$roomId])) {
            return;
        }

        $this->rooms[$roomId]['answers'][$userId] = [
            'answer' => $answer,
            'timestamp' => time(),
        ];
    }

    public function sendCandidate(string $roomId, string $userId, array $candidate)
    {
        if (!isset($this->rooms[$roomId])) {
            return;
        }

        if (!isset($this->rooms[$roomId]['candidates'][$userId])) {
            $this->rooms[$roomId]['candidates'][$userId] = [];
        }

        $this->rooms[$roomId]['candidates'][$userId][] = [
            'candidate' => $candidate,
            'timestamp' => time(),
        ];
    }

    public function getRoomData(string $roomId)
    {
        return $this->rooms[$roomId] ?? null;
    }

    public function leaveRoom(string $roomId, string $userId)
    {
        if (isset($this->rooms[$roomId]['participants'][$userId])) {
            unset($this->rooms[$roomId]['participants'][$userId]);

            // Clean up if room is empty
            if (empty($this->rooms[$roomId]['participants'])) {
                unset($this->rooms[$roomId]);
            }
        }
    }
}
