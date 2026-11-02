<?php

declare(strict_types=1);

namespace App\FeatureFlags\Controller;

use App\FeatureFlags\Entity\FeatureFlag;
use App\FeatureFlags\Repository\FeatureFlagRepositoryInterface;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class FlagController extends BaseController
{
    public function __construct(private FeatureFlagRepositoryInterface $flags)
    {
    }

    #[Route('/api/feature-flags/{name}', name: 'feature_flag_get', methods: ['GET'])]
    public function getFlag(string $name): JsonResponse
    {
        $flag = $this->flags->findByName($name);
        if (!$flag) {
            return $this->json(['error' => 'Flag not found'], 404);
        }
        return $this->json([
            'name' => $flag->getName(),
            'enabled' => $flag->isEnabled(),
            'description' => $flag->getDescription(),
        ]);
    }

    #[Route('/api/feature-flags', name: 'feature_flag_list', methods: ['GET'])]
    public function listFlags(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $all = $this->flags->findAll();
        $data = [];
        foreach ($all as $flag) {
            $data[] = [
                'name' => $flag->getName(),
                'enabled' => $flag->isEnabled(),
                'description' => $flag->getDescription(),
                'createdAt' => $flag->getCreatedAt()->format('c'),
            ];
        }
        return $this->json($data);
    }

    #[Route('/api/feature-flags', name: 'feature_flag_upsert', methods: ['POST'])]
    public function upsertFlag(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $payloadOr400 = $this->decodeJsonOr400($request);
        if ($payloadOr400 instanceof JsonResponse) return $payloadOr400;
        $payload = $payloadOr400;
        if (!isset($payload['name'])) return $this->json(['error' => 'Name required'], 400);
        $flag = $this->upsertFlagData($payload);
        return $this->json([
            'name' => $flag->getName(),
            'enabled' => $flag->isEnabled(),
            'description' => $flag->getDescription(),
        ], 201);
    }

    private function upsertFlagData(array $payload): FeatureFlag
    {
        $name = (string)$payload['name'];
        $enabled = (bool)($payload['enabled'] ?? false);
        $description = $payload['description'] ?? null;
        $existing = $this->flags->findByName($name);
        if ($existing) {
            $existing->setEnabled($enabled);
            $existing->setDescription($description);
            $this->flags->save($existing);
            return $existing;
        }
        $flag = new FeatureFlag($name, $enabled, $description);
        $this->flags->save($flag);
        return $flag;
    }
}
