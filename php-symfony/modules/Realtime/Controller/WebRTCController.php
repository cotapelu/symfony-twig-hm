<?php

declare(strict_types=1);

namespace App\Realtime\Controller;

use App\Realtime\Service\WebRTCSignalingService;
use App\Shared\Security\AuthenticationService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WebRTCController extends BaseController
{
    private const MAX_ROOM_ID_LENGTH = 100;
    private const MAX_SDP_LENGTH = 50000;
    private const MAX_CANDIDATE_LENGTH = 5000;

public function __construct(
        private WebRTCSignalingService $signalingService,
        private AuthenticationService $authenticationService,
    ) {}

    private function logAction(string $action, array $context = []): void
    {
        $logData = [
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
            'action' => $action,
            'context' => $context,
        ];
        error_log(json_encode($logData, JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        $metricData = [
            'metric' => $metric,
            'tags' => $tags,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        ];
        error_log(json_encode($metricData));
    }

    private function validateRoomId(string $roomId): ?string
    {
        if (empty($roomId)) {
            return 'Room ID is required';
        }
        if (strlen($roomId) > self::MAX_ROOM_ID_LENGTH) {
            return 'Room ID too long';
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $roomId)) {
            return 'Invalid room ID format';
        }
        return null;
    }

    private function validateSDP(array $sdp): ?string
    {
        if (empty($sdp)) {
            return 'SDP is required';
        }
        $sdpJson = json_encode($sdp);
        if (strlen($sdpJson) > self::MAX_SDP_LENGTH) {
            return 'SDP too long';
        }
        return null;
    }

    private function validateCandidate(array $candidate): ?string
    {
        if (empty($candidate)) {
            return 'Candidate is required';
        }
        $candidateJson = json_encode($candidate);
        if (strlen($candidateJson) > self::MAX_CANDIDATE_LENGTH) {
            return 'Candidate too long';
        }
        return null;
    }

    private function getUserId(Request $request): ?string
    {
        try {
            $user = $this->authenticationService->requireAuthentication($request);
            return $user['id'];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function ensureRoomMember(string $roomId, string $userId): ?array
    {
        $roomData = $this->signalingService->getRoomData($roomId);
        if ($roomData && isset($roomData['participants']) && in_array($userId, $roomData['participants'])) {
            return $roomData;
        }
        return null;
    }

    #[Route('/api/webrtc/room/{roomId}/join', name: 'webrtc_join_room', methods: ['POST'])]
    public function joinRoom(Request $request, string $roomId): Response
    {
        $userId = $this->getUserId($request);
        if ($userId === null) return $this->json(['error'=>'Authentication required'], 401);
        if ($error = $this->validateRoomId($roomId)) return $this->json(['error'=>$error], 400);
        $data = $this->decodeJsonOr400($request);
        if ($data instanceof JsonResponse) return $data;
        $roomData = $this->signalingService->joinRoom($roomId, $userId);
        if (!$roomData) {
            $this->logAction('webrtc.join_room.not_found', ['roomId'=>$roomId, 'userId'=>$userId]);
            return $this->json(['error' => 'Room not found'], 404);
        }
        if (!isset($roomData['participants']) || !in_array($userId, $roomData['participants'])) {
            $this->logAction('webrtc.join_room.access_denied', ['roomId'=>$roomId, 'userId'=>$userId]);
            return $this->json(['error' => 'Access denied to this room'], 403);
        }
        $this->logAction('webrtc.join_room.success', ['roomId'=>$roomId, 'userId'=>$userId]);
        $this->incrementMetric('webrtc.join_room', ['status'=>'success']);
        return $this->json($roomData);
    }

    #[Route('/api/webrtc/room/{roomId}/offer', name: 'webrtc_send_offer', methods: ['POST'])]
    public function sendOffer(Request $request, string $roomId): Response
    {
        $userId = $this->getUserId($request);
        if ($userId === null) return $this->json(['error'=>'Authentication required'], 401);
        if ($error = $this->validateRoomId($roomId)) return $this->json(['error'=>$error], 400);
        $data = $this->decodeJsonOr400($request);
        if ($data instanceof JsonResponse) return $data;
        if ($error = $this->validateSDP($data['offer'] ?? [])) return $this->json(['error'=>$error], 400);
        if (!$this->ensureRoomMember($roomId, $userId)) return $this->json(['error' => 'Access denied or room not found'], 403);
        $this->signalingService->sendOffer($roomId, $userId, $data['offer']);
        $this->logAction('webrtc.send_offer', ['roomId'=>$roomId, 'userId'=>$userId]);
        $this->incrementMetric('webrtc.send_offer');
        return $this->json(['success' => true]);
    }

    #[Route('/api/webrtc/room/{roomId}/answer', name: 'webrtc_send_answer', methods: ['POST'])]
    public function sendAnswer(Request $request, string $roomId): Response
    {
        $userId = $this->getUserId($request);
        if ($userId === null) return $this->json(['error'=>'Authentication required'], 401);
        if ($error = $this->validateRoomId($roomId)) return $this->json(['error'=>$error], 400);
        $data = $this->decodeJsonOr400($request);
        if ($data instanceof JsonResponse) return $data;
        if ($error = $this->validateSDP($data['answer'] ?? [])) return $this->json(['error'=>$error], 400);
        if (!$this->ensureRoomMember($roomId, $userId)) return $this->json(['error' => 'Access denied or room not found'], 403);
        $this->signalingService->sendAnswer($roomId, $userId, $data['answer']);
        $this->logAction('webrtc.send_answer', ['roomId'=>$roomId, 'userId'=>$userId]);
        $this->incrementMetric('webrtc.send_answer');
        return $this->json(['success'=>true]);
    }

    #[Route('/api/webrtc/room/{roomId}/candidate', name: 'webrtc_send_candidate', methods: ['POST'])]
    public function sendCandidate(Request $request, string $roomId): Response
    {
        $userId = $this->getUserId($request);
        if ($userId === null) return $this->json(['error'=>'Authentication required'], 401);
        if ($error = $this->validateRoomId($roomId)) return $this->json(['error'=>$error], 400);
        $data = $this->decodeJsonOr400($request);
        if ($data instanceof JsonResponse) return $data;
        if ($error = $this->validateCandidate($data['candidate'] ?? [])) return $this->json(['error'=>$error], 400);
        if (!$this->ensureRoomMember($roomId, $userId)) return $this->json(['error' => 'Access denied or room not found'], 403);
        $this->signalingService->sendCandidate($roomId, $userId, $data['candidate']);
        $this->logAction('webrtc.send_candidate', ['roomId'=>$roomId, 'userId'=>$userId]);
        $this->incrementMetric('webrtc.send_candidate');
        return $this->json(['success'=>true]);
    }

    #[Route('/api/webrtc/room/{roomId}', name: 'webrtc_get_room', methods: ['GET'])]
    public function getRoom(Request $request, string $roomId): Response
    {
        $userId = $this->getUserId($request);
        if ($userId === null) return $this->json(['error'=>'Authentication required'], 401);
        if ($error = $this->validateRoomId($roomId)) return $this->json(['error'=>$error], 400);
        $roomData = $this->ensureRoomMember($roomId, $userId);
        if ($roomData === null) return $this->json(['error' => 'Access denied or room not found'], 403);
        $this->logAction('webrtc.get_room', ['roomId'=>$roomId, 'userId'=>$userId]);
        return $this->json($roomData);
    }

    #[Route('/api/webrtc/room/{roomId}/leave', name: 'webrtc_leave_room', methods: ['POST'])]
    public function leaveRoom(Request $request, string $roomId): Response
    {
        $userId = $this->getUserId($request);
        if ($userId === null) return $this->json(['error'=>'Authentication required'], 401);
        if ($error = $this->validateRoomId($roomId)) return $this->json(['error'=>$error], 400);
        $data = $this->decodeJsonOr400($request);
        if ($data instanceof JsonResponse) return $data;
        $this->signalingService->leaveRoom($roomId, $userId);
        $this->logAction('webrtc.leave_room', ['roomId'=>$roomId, 'userId'=>$userId]);
        $this->incrementMetric('webrtc.leave_room');
        return $this->json(['success'=>true]);
    }
}
