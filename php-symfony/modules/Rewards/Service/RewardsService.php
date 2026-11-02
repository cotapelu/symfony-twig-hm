<?php

declare(strict_types=1);

namespace App\Rewards\Service;

use App\Rewards\Entity\RewardPoint;
use App\Rewards\Entity\AchievementBadge;
use App\Rewards\Repository\RewardPointRepository;
use App\Rewards\Repository\AchievementBadgeRepository;
use App\Shared\EventBus\EventBus;
use App\Rewards\Event\PointsEarnedEvent;
use App\Rewards\Event\BadgeEarnedEvent;
use App\User\Repository\UserRepository;

class RewardsService
{
    public function __construct(
        private RewardPointRepository $pointsRepository,
        private AchievementBadgeRepository $badgeRepository,
        private EventBus $eventBus,
        private UserRepository $userRepository,
    ) {
    }

    public function addPoints(string $userId, int $points, string $reason)
    {
        $rewardPoints = $this->pointsRepository->findByUser($userId);

        if (!$rewardPoints) {
            $rewardPoints = new RewardPoint(
                user: $this->userRepository->find($userId),
            );
        }

        $rewardPoints->addPoints($points);
        $this->pointsRepository->save($rewardPoints);

        $event = new PointsEarnedEvent(
            userId: $userId,
            points: $points,
            reason: $reason,
            newTotal: $rewardPoints->getPoints(),
            earnedAt: new \DateTime(),
        );

        $this->eventBus->dispatch($event);
    }

    public function removePoints(string $userId, int $points, string $reason)
    {
        $rewardPoints = $this->pointsRepository->findByUser($userId);

        if (!$rewardPoints) {
            throw new \InvalidArgumentException('Reward points not found');
        }

        if ($rewardPoints->getPoints() < $points) {
            throw new \InvalidArgumentException('Insufficient points');
        }

        $rewardPoints->removePoints($points);
        $this->pointsRepository->save($rewardPoints);
    }

    public function earnBadge(string $userId, string $badgeType, string $badgeName, ?string $description = null, ?string $icon = null, ?array $metadata = null)
    {
        $badge = new AchievementBadge(
            user: $this->userRepository->find($userId),
            type: $badgeType,
            name: $badgeName,
            description: $description,
            icon: $icon,
            metadata: $metadata,
        );

        $this->badgeRepository->save($badge);

        $event = new BadgeEarnedEvent(
            badgeId: $badge->getId(),
            userId: $userId,
            badgeType: $badgeType,
            badgeName: $badgeName,
            earnedAt: $badge->getEarnedAt(),
        );

        $this->eventBus->dispatch($event);

        return $badge;
    }

    public function getUserPoints(string $userId)
    {
        return $this->pointsRepository->findByUser($userId);
    }

    public function getUserBadges(string $userId)
    {
        return $this->badgeRepository->findByUser($userId);
    }

    public function getLeaderboard(int $limit = 100)
    {
        return $this->pointsRepository->findTopUsers($limit);
    }

    public function getBadgesByType(string $badgeType)
    {
        return $this->badgeRepository->findByType($badgeType);
    }
}
