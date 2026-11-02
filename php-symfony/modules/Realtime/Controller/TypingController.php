<?php

declare(strict_types=1);

namespace App\Realtime\Controller;

use App\Realtime\Service\TypingIndicatorService;
use App\Shared\Security\AuthenticationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Controller\BaseController;

class TypingController extends BaseController
{
    public function __construct(
        private TypingIndicatorService $typingService,
        private AuthenticationService $authenticationService,
    ) {
        parent::__construct();
    }

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


    #[Route('/api/typing/start', name: 'api_typing_start', methods: ['POST'])]
    public function startTyping(Request $request): Response
    {
        try {
            $currentUser = $this->authenticationService->requireAuthentication($request);
            $userId = $currentUser['id'];
        } catch (\Exception $e) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $content = $request->getContent();
        if ($content === '') {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        $conversationId = $data['conversationId'] ?? null;

        if (!$conversationId) {
            return $this->json(['error' => 'Conversation ID required'], 400);
        }

        $this->typingService->startTyping($conversationId, $userId);

        $this->logAction('typing.start', ['conversationId' => $conversationId, 'userId' => $userId]);
        $this->incrementMetric('typing.start');

        return $this->json(['success' => true]);
    }

    #[Route('/api/typing/stop', name: 'api_typing_stop', methods: ['POST'])]
    public function stopTyping(Request $request): Response
    {
        try {
            $currentUser = $this->authenticationService->requireAuthentication($request);
            $userId = $currentUser['id'];
        } catch (\Exception $e) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $content = $request->getContent();
        if ($content === '') {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        $conversationId = $data['conversationId'] ?? null;

        if (!$conversationId) {
            return $this->json(['error' => 'Conversation ID required'], 400);
        }

        $this->typingService->stopTyping($conversationId, $userId);

        $this->logAction('typing.stop', ['conversationId' => $conversationId, 'userId' => $userId]);
        $this->incrementMetric('typing.stop');

        return $this->json(['success' => true]);
    }

    #[Route('/api/typing/status/{conversationId}', name: 'api_typing_status', methods: ['GET'])]
    public function getTypingStatus(string $conversationId): Response
    {
        $typingUsers = $this->typingService->getTypingUsers($conversationId);

        return $this->json([
            'conversationId' => $conversationId,
            'typingUsers' => $typingUsers,
        ]);
    }
}
