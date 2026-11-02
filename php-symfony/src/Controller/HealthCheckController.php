<?php

namespace App\Controller;

use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class HealthCheckController extends BaseController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ?CacheInterface $cache = null,
        private ?MessageBusInterface $bus = null
    ) {
        parent::__construct();
    }

    #[Route('/health/live', name: 'health_live', methods: ['GET'])]
    public function live(): JsonResponse
    {
        return $this->json([
            'status' => 'alive',
            'timestamp' => (new DateTime())->format('c')
        ]);
    }

    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $components = [
            'database' => $this->checkDatabase(),
        ];

        if ($this->cache) {
            $components['cache'] = $this->checkCache();
        }
        if ($this->bus) {
            $components['messenger'] = $this->checkMessenger();
        }

        $isHealthy = !in_array('error', $components, true);
        $status = $isHealthy ? 'healthy' : 'unhealthy';
        $code = $isHealthy ? 200 : 503;

        return $this->json([
            'status' => $status,
            'components' => $components,
            'timestamp' => (new DateTime())->format('c')
        ], $code);
    }

    #[Route('/ready', name: 'ready_check', methods: ['GET'])]
    public function ready(): JsonResponse
    {
        $components = [
            'database' => $this->checkDatabase(),
        ];

        if ($this->cache) {
            $components['cache'] = $this->checkCache();
        }
        if ($this->bus) {
            $components['messenger'] = $this->checkMessenger();
        }

        $isReady = !in_array('error', $components, true);
        $status = $isReady ? 'ready' : 'not ready';
        $code = $isReady ? 200 : 503;

        return $this->json([
            'status' => $status,
            'components' => $components,
            'timestamp' => (new DateTime())->format('c')
        ], $code);
    }

    private function checkDatabase(): string
    {
        try {
            $this->em->getConnection()->fetchOne('SELECT 1');
            return 'ok';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    private function checkCache(): string
    {
        try {
            if ($this->cache === null) {
                return 'skipped';
            }
            $this->cache->get('health_check_probe', fn() => 'ok', 1);
            return 'ok';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    private function checkMessenger(): string
    {
        // Messenger bus availability is determined by service container wiring
        return 'ok';
    }
}