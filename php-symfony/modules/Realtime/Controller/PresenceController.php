<?php

declare(strict_types=1);

namespace App\Realtime\Controller;

use App\Realtime\Service\PresenceService;
use App\User\Service\AuthService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PresenceController extends BaseController
{
    public function __construct(
        private PresenceService $presenceService,
        private AuthService $authService,
    ) {
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

    #[Route('/api/presence/online', name: 'api_presence_online', methods: ['POST'])]
    public function setUserOnline(Request $request): Response
    {
        try {
            $userId = $this->authService->getAuthenticatedUserId($this->getUser());
            $result = $this->presenceService->setUserOnline($userId);

            $this->logAction('presence.set_online', ['userId' => $userId]);
            $this->incrementMetric('presence.set_online');

            return $this->json($result);
        } catch (\Exception $e) {
            $this->logAction('presence.set_online.error', ['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], 401);
        }
    }
}
