<?php

declare(strict_types=1);

namespace App\Rewards\Controller;

use App\Rewards\Service\RewardsService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Route('/api/rewards')]
class RewardsController extends BaseController
{
    public function __construct(private RewardsService $rewardsService)
    {
        parent::__construct();
    }

    private const MAX_POINTS_PER_TRANSACTION = 1000000;
    private const MAX_REASON_LENGTH = 500;
    private const MAX_BADGE_NAME_LENGTH = 100;
    private const MAX_BADGE_DESCRIPTION_LENGTH = 500;
    private const ALLOWED_BADGE_TYPES = ['milestone', 'achievement', 'special', 'streak', 'community', 'social'];

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

    private function sanitizeString(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function getCurrentUser(): string
    {
        $user = $this->getUser();
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }
        return (string)$user->getId();
    }

    private function validateAddPointsInput(array $data): ?string
    {
        if (!isset($data['points']) || !is_int($data['points']) || $data['points'] <= 0) {
            return 'Points must be a positive integer';
        }
        if ($data['points'] > self::MAX_POINTS_PER_TRANSACTION) {
            return 'Points exceed maximum allowed (' . self::MAX_POINTS_PER_TRANSACTION . ')';
        }
        if (empty($data['reason']) || !is_string($data['reason'])) {
            return 'Points and reason are required';
        }
        if (strlen($data['reason']) > self::MAX_REASON_LENGTH) {
            return 'Reason too long (max ' . self::MAX_REASON_LENGTH . ' chars)';
        }
        return null;
    }

    private function validateEarnBadgeInput(array $data): ?string
    {
        // Check for missing or empty required fields
        if (empty($data['badge_type']) || empty($data['badge_name'])) {
            return 'Badge type and name are required';
        }
        if (!is_string($data['badge_type']) || !is_string($data['badge_name'])) {
            return 'Badge type and name are required';
        }
        if (!in_array($data['badge_type'], self::ALLOWED_BADGE_TYPES)) {
            return 'Invalid badge type';
        }
        if (strlen($data['badge_name']) > self::MAX_BADGE_NAME_LENGTH) {
            return 'Badge name too long (max ' . self::MAX_BADGE_NAME_LENGTH . ' chars)';
        }
        if (!empty($data['description']) && strlen($data['description']) > self::MAX_BADGE_DESCRIPTION_LENGTH) {
            return 'Description too long (max ' . self::MAX_BADGE_DESCRIPTION_LENGTH . ' chars)';
        }
        return null;
    }

    #[Route('/points', methods: ['GET'])]
    public function getMyPoints(): JsonResponse
    {
        $user = $this->getCurrentUser();
        $points = $this->rewardsService->getUserPoints($user);

        if (!$points) {
            return $this->json(['error' => 'Reward points not found'], 404);
        }

        return $this->json($points->toArray());
    }

    #[Route('/badges', methods: ['GET'])]
    public function getMyBadges(): JsonResponse
    {
        $user = $this->getCurrentUser();
        $badges = $this->rewardsService->getUserBadges($user);

        return $this->json($badges);
    }

    #[Route('/leaderboard', methods: ['GET'])]
    public function getLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 100);
        
        // Validate limit
        if ($limit < 1 || $limit > 1000) {
            $limit = 100;
        }
        
        $leaderboard = $this->rewardsService->getLeaderboard($limit);

        return $this->json($leaderboard);
    }

    #[Route('/points/add', methods: ['POST'])]
    public function addPoints(Request $request): JsonResponse
    {
        $user = $this->getCurrentUser();
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        

        $error = $this->validateAddPointsInput($data);
        if ($error) {
            return $this->json(['error' => $error], 400);
        }

        try {
$this->rewardsService->addPoints(
            userId: $user,
            points: $data['points'],
            reason: $this->sanitizeString($data['reason']),
        );

        $this->logAction('rewards.points.added', ['userId' => $user, 'points' => $data['points']]);
        $this->incrementMetric('rewards.points.added');

        return $this->json(['message' => 'Points added successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/points/withdraw', methods: ['POST'])]
    public function withdrawPoints(Request $request): JsonResponse
    {
        $user = $this->getCurrentUser();
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        

        $error = $this->validateAddPointsInput($data);
        if ($error) {
            return $this->json(['error' => $error], 400);
        }

        try {
$this->rewardsService->removePoints(
            userId: $user,
            points: $data['points'],
            reason: $this->sanitizeString($data['reason']),
        );

        $this->logAction('rewards.points.withdrawn', ['userId' => $user, 'points' => $data['points']]);
        $this->incrementMetric('rewards.points.withdrawn');

        return $this->json(['message' => 'Points withdrawn successfully']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/badges/earn', methods: ['POST'])]
    public function earnBadge(Request $request): JsonResponse
    {
        $user = $this->getCurrentUser();
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        

        $error = $this->validateEarnBadgeInput($data);
        if ($error) {
            return $this->json(['error' => $error], 400);
        }

        try {
$badge = $this->rewardsService->earnBadge(
            userId: $user,
            badgeType: $data['badge_type'],
            badgeName: $this->sanitizeString($data['badge_name']),
            description: $data['description'] ? $this->sanitizeString($data['description']) : null,
            icon: $data['icon'] ? $this->sanitizeString($data['icon']) : null,
            metadata: $data['metadata'] ?? null,
        );

        $this->logAction('rewards.badge.earned', ['userId' => $user, 'badgeType' => $data['badge_type'], 'badgeName' => $data['badge_name']]);
        $this->incrementMetric('rewards.badge.earned', ['type' => $data['badge_type']]);

        return $this->json($badge->toArray(), 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/badges/{type}', methods: ['GET'])]
    public function getBadgesByType(Request $request, string $type): JsonResponse
    {
        // Validate type
        if (!in_array($type, self::ALLOWED_BADGE_TYPES)) {
            return $this->json(['error' => 'Invalid badge type'], 400);
        }
        
        $badges = $this->rewardsService->getBadgesByType($type);

        return $this->json($badges);
    }
}
