<?php

declare(strict_types=1);

namespace App\Realtime\Service;


/**
 * WebRTC Service for voice and video calling
 * Handles peer-to-peer connections, signaling, and media management.
 */
class WebRTCService
{
    private array $activeCalls = [];
    private array $pendingOffers = [];

    public function __construct(
        private CallQualityService $callQualityService,
    ) {
    }

    /**
     * Create a new call session.
     */
    public function createCall(string $callId, string $callerId, array $participants, string $callType = 'voice')
    {
        // Validate call type
        if (!in_array($callType, ['voice', 'video', 'screen'])) {
            throw new \InvalidArgumentException('Invalid call type. Must be "voice", "video", or "screen"');
        }

        // Validate participants array
        if (empty($participants)) {
            throw new \InvalidArgumentException('At least one participant is required');
        }

        // Validate participant IDs
        foreach ($participants as $participant) {
            if (!is_string($participant) || empty(trim($participant))) {
                throw new \InvalidArgumentException('Participant IDs must be non-empty strings');
            }
        }

        $call = [
            'id' => $callId,
            'caller_id' => $callerId,
            'participants' => $participants,
            'call_type' => $callType,
            'status' => 'ringing',
            'start_time' => time(),
            'ice_servers' => $this->getIceServers(),
            'participants_status' => array_fill_keys($participants, [
                'status' => 'invited',
                'joined_at' => null,
                'media_stream' => null,
                'audio_enabled' => true,
                'video_enabled' => 'video' === $callType || 'screen' === $callType,
                'screen_sharing' => 'screen' === $callType,
            ]),
        ];

        $this->activeCalls[$callId] = $call;

        // Start quality monitoring
        $this->callQualityService->startCallMonitoring($callId, $callerId, $participants);

        return $call;
    }

    /**
     * Join an existing call.
     */
    public function joinCall(string $callId, string $participantId)
    {
        $this->validateJoinInput($callId, $participantId);

        if (!isset($this->activeCalls[$callId])) {
            throw new \InvalidArgumentException('Call not found or has ended');
        }
        $call = &$this->activeCalls[$callId];

        $this->validateParticipantInvited($call, $participantId);
        $this->validateNotAlreadyJoined($call, $participantId);

        $call['participants_status'][$participantId]['status'] = 'joined';
        $call['participants_status'][$participantId]['joined_at'] = time();

        $this->maybeActivateCall($call);
        return $call;
    }

    private function validateJoinInput(string $callId, string $participantId): void
    {
        if (empty($callId) || empty($participantId)) {
            throw new \InvalidArgumentException('Call ID and participant ID are required');
        }
        if (!is_string($callId) || !is_string($participantId)) {
            throw new \InvalidArgumentException('Call ID and participant ID must be strings');
        }
    }

    private function validateParticipantInvited(array &$call, string $participantId): void
    {
        if (!is_array($call['participants']) || !in_array($participantId, $call['participants'])) {
            throw new \InvalidArgumentException('Participant not invited to this call');
        }
    }

    private function validateNotAlreadyJoined(array &$call, string $participantId): void
    {
        if (!isset($call['participants_status'][$participantId]) || !is_array($call['participants_status'][$participantId])) {
            throw new \InvalidArgumentException('Invalid participant status data');
        }
        if (($call['participants_status'][$participantId]['status'] ?? null) === 'joined') {
            throw new \InvalidArgumentException('Participant already joined this call');
        }
    }

    private function maybeActivateCall(array &$call): void
    {
        $joinedCount = count(array_filter($call['participants_status'], fn ($p) => 'joined' === $p['status']));
        if ($joinedCount >= 2) {
            $call['status'] = 'active';
        }
    }

    /**
     * Leave a call.
     */
    public function leaveCall(string $callId, string $participantId)
    {
        if (!isset($this->activeCalls[$callId])) {
            return ['status' => 'not_found'];
        }

        $call = &$this->activeCalls[$callId];

        if (isset($call['participants_status'][$participantId])) {
            $call['participants_status'][$participantId]['status'] = 'left';
            $call['participants_status'][$participantId]['left_at'] = time();
        }

        // Check if call should end
        $activeParticipants = array_filter($call['participants_status'], fn ($p) => 'joined' === $p['status']);
        if (count($activeParticipants) < 2) {
            $call['status'] = 'ended';
            $call['end_time'] = time();

            // Stop quality monitoring
            $this->callQualityService->stopCallMonitoring($callId);
        }

        return $call;
    }

    /**
     * Store WebRTC offer for signaling.
     */
    public function storeOffer(string $callId, string $fromParticipant, string $toParticipant, array $offer)
    {
        $key = $callId.'_'.$fromParticipant.'_'.$toParticipant;
        $this->pendingOffers[$key] = [
            'offer' => $offer,
            'timestamp' => time(),
            'from' => $fromParticipant,
            'to' => $toParticipant,
        ];
    }

    /**
     * Get pending offer for participant.
     */
    public function getOffer(string $callId, string $fromParticipant, string $toParticipant)
    {
        $key = $callId.'_'.$fromParticipant.'_'.$toParticipant;

        return $this->pendingOffers[$key] ?? null;
    }

    /**
     * Store WebRTC answer for signaling.
     */
    public function storeAnswer(string $callId, string $fromParticipant, string $toParticipant, array $answer)
    {
        $key = $callId.'_'.$fromParticipant.'_'.$toParticipant;
        if (isset($this->pendingOffers[$key])) {
            $this->pendingOffers[$key]['answer'] = $answer;
            $this->pendingOffers[$key]['answer_timestamp'] = time();
        }
    }

    /**
     * Store ICE candidate for signaling.
     */
    public function storeIceCandidate(string $callId, string $fromParticipant, string $toParticipant, array $candidate)
    {
        $key = $callId.'_'.$fromParticipant.'_'.$toParticipant;
        if (!isset($this->pendingOffers[$key]['ice_candidates'])) {
            $this->pendingOffers[$key]['ice_candidates'] = [];
        }
        $this->pendingOffers[$key]['ice_candidates'][] = $candidate;
    }

    /**
     * Get ICE candidates for participant.
     */
    public function getIceCandidates(string $callId, string $fromParticipant, string $toParticipant)
    {
        $key = $callId.'_'.$fromParticipant.'_'.$toParticipant;

        return $this->pendingOffers[$key]['ice_candidates'] ?? [];
    }

    /**
     * Update participant media status.
     */
    public function updateParticipantMedia(string $callId, string $participantId, array $mediaStatus)
    {
        if (!isset($this->activeCalls[$callId])) {
            return;
        }

        $call = &$this->activeCalls[$callId];

        if (isset($call['participants_status'][$participantId])) {
            $call['participants_status'][$participantId] = array_merge(
                $call['participants_status'][$participantId],
                $mediaStatus
            );
        }
    }

    /**
     * Get call information.
     */
    public function getCall(string $callId)
    {
        return $this->activeCalls[$callId] ?? null;
    }

    /**
     * Get all active calls.
     */
    public function getActiveCalls()
    {
        return array_filter($this->activeCalls, fn ($call) => 'active' === $call['status']);
    }

    /**
     * Clean up old offers (older than 5 minutes).
     */
    public function cleanupOldOffers()
    {
        $cutoffTime = time() - 300; // 5 minutes ago

        foreach ($this->pendingOffers as $key => $offer) {
            if ($offer['timestamp'] < $cutoffTime) {
                unset($this->pendingOffers[$key]);
            }
        }
    }

    /**
     * Get ICE servers configuration.
     */
    private function getIceServers()
    {
        $servers = [
            [
                'urls' => 'stun:stun.l.google.com:19302',
            ],
        ];

        // Add TURN servers from environment/config for production
        $turnServerUrl = $_ENV['TURN_SERVER_URL'] ?? null;
        $turnUsername = $_ENV['TURN_USERNAME'] ?? null;
        $turnCredential = $_ENV['TURN_CREDENTIAL'] ?? null;

        if ($turnServerUrl && $turnUsername && $turnCredential) {
            $servers[] = [
                'urls' => $turnServerUrl,
                'username' => $turnUsername,
                'credential' => $turnCredential,
            ];
        }

        return $servers;
    }

    /**
     * Generate call statistics.
     */
    public function getCallStatistics(string $callId)
    {
        $call = $this->getCall($callId);
        if (!$call) {
            return ['error' => 'Call not found'];
        }

        $duration = time() - $call['start_time'];
        $activeParticipants = count(array_filter($call['participants_status'], fn ($p) => 'joined' === $p['status']));

        return [
            'call_id' => $callId,
            'duration' => $duration,
            'total_participants' => count($call['participants']),
            'active_participants' => $activeParticipants,
            'call_type' => $call['call_type'],
            'status' => $call['status'],
            'participants_status' => $call['participants_status'],
        ];
    }
}
