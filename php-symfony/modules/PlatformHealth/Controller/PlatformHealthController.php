<?php

declare(strict_types=1);

namespace App\PlatformHealth\Controller;

use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use DateTimeImmutable;

// Guard against redeclaration in test environments with multiple kernel boots
if (class_exists(PlatformHealthController::class, false)) {
    return;
}

#[Route('/health')]
class PlatformHealthController extends BaseController
{
    private CacheInterface $cache;
    private int $ttl;

    public function __construct(CacheInterface $cache, int $ttl = 30)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    private function log(string $service, string $status, ?string $error = null): void
    {
        $ts = new DateTimeImmutable();
        error_log(json_encode([
            'timestamp' => $ts->format(DateTimeImmutable::ATOM),
            'service' => $service,
            'status' => $status,
            'error' => $error,
        ]));
    }

    private function checkService(string $service, callable $check): array
    {
        $cacheKey = 'health_' . $service;
        $cached = $this->cache->get($cacheKey, function (ItemInterface $item) use ($check, $service) {
            $item->expiresAfter($this->ttl);
            try {
                $healthy = $check();
                $status = $healthy ? 'healthy' : 'unhealthy';
                $error = null;
            } catch (\Throwable $e) {
                $status = 'unhealthy';
                $error = $e->getMessage();
            }
            $this->log($service, $status, $error ?? null);
            return ['status' => $status, 'service' => $service, 'error' => $error];
        });
        return $cached;
    }

    #[Route('/iam', name: 'health_iam', methods: ['GET'])]
    public function iamHealth(): JsonResponse
    {
        $result = $this->checkService('IAM', function() {
            $secret = $_ENV['JWT_SECRET'] ?? null;
            if (!$secret) {
                throw new \RuntimeException('JWT_SECRET not configured');
            }
            // In a real setup, you would inject the actual IAM service and perform a lightweight check.
            return true;
        });
        $statusCode = $result['status'] === 'healthy' ? 200 : 503;
        return $this->json($result, $statusCode);
    }

    #[Route('/mdm', name: 'health_mdm', methods: ['GET'])]
    public function mdmHealth(): JsonResponse
    {
        $result = $this->checkService('MDM', function() {
            // Placeholder: actual check would query reference data service
            return true;
        });
        $statusCode = $result['status'] === 'healthy' ? 200 : 503;
        return $this->json($result, $statusCode);
    }

    #[Route('/eventing', name: 'health_eventing', methods: ['GET'])]
    public function eventingHealth(): JsonResponse
    {
        $result = $this->checkService('Eventing', function() {
            // Placeholder: actual check would ping message broker
            return true;
        });
        $statusCode = $result['status'] === 'healthy' ? 200 : 503;
        return $this->json($result, $statusCode);
    }

    #[Route('/config', name: 'health_config', methods: ['GET'])]
    public function configHealth(): JsonResponse
    {
        $result = $this->checkService('Config', function() {
            // Placeholder: actual check would read configuration
            return true;
        });
        $statusCode = $result['status'] === 'healthy' ? 200 : 503;
        return $this->json($result, $statusCode);
    }
}