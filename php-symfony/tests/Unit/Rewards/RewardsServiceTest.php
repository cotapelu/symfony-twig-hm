<?php declare(strict_types=1);

namespace App\Tests\Unit\Rewards;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Rewards\Entity\RewardPoint;
use App\Rewards\Entity\AchievementBadge;
use App\Rewards\Repository\RewardPointRepository;
use App\Rewards\Repository\AchievementBadgeRepository;
use App\Rewards\Service\RewardsService;
use App\Rewards\Event\PointsEarnedEvent;
use App\Rewards\Event\BadgeEarnedEvent;
use App\User\Entity\User;
use App\User\Repository\UserRepository;
use App\Shared\EventBus\EventBus;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RewardsServiceTest extends TestCase
{
    private RewardPointRepository $pointsRepo;
    private AchievementBadgeRepository $badgeRepo;
    private EventBus $eventBus;
    private UserRepository $userRepo;
    private RewardsService $service;

    private User $userMock;

    protected function setUp(): void
    {
        $this->pointsRepo = $this->createMock(RewardPointRepository::class);
        $this->badgeRepo = $this->createMock(AchievementBadgeRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->userRepo = $this->createMock(UserRepository::class);

        $this->service = new RewardsService(
            $this->pointsRepo,
            $this->badgeRepo,
            $this->eventBus,
            $this->userRepo
        );

        $this->userMock = $this->createMock(User::class);
    }

    public function testAddPointsCreatesNewRewardPointsWhenNoneExists(): void
    {
        $userId = 'user-123';
        $points = 100;
        $reason = 'Test reason';
        $capturedRp = null;

        // No existing reward points
        $this->pointsRepo->expects($this->once())
            ->method('findByUser')
            ->with($userId)
            ->willReturn(null);

        // User exists
        $this->userRepo->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($this->userMock);

        // Save called once after adding points (optimized)
        $this->pointsRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (RewardPoint $rp) use (&$capturedRp) {
                $capturedRp = $rp;
                return true;
            }));

        // Event dispatched
        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (PointsEarnedEvent $event) use ($userId, $points, &$capturedRp) {
                return $event->getUserId() === $userId
                    && $event->getPoints() === $points
                    && $event->getNewTotal() === $capturedRp->getPoints();
            }));

        $this->service->addPoints($userId, $points, $reason);
    }

    public function testAddPointsAddsToExistingRewardPoints(): void
    {
        $userId = 'user-123';
        $initialPoints = 50;
        $add = 30;
        $reason = 'Add more';

        $existing = new RewardPoint($this->userMock);
        // Set initial points via reflection because points property is private and setPoints exists but we can also use setPoints
        $ref = new \ReflectionProperty(RewardPoint::class, 'points');
        $ref->setAccessible(true);
        $ref->setValue($existing, $initialPoints);

        // Also lifetimePoints should be set accordingly
        $refLife = new \ReflectionProperty(RewardPoint::class, 'lifetimePoints');
        $refLife->setAccessible(true);
        $refLife->setValue($existing, $initialPoints);

        $this->pointsRepo->expects($this->once())
            ->method('findByUser')
            ->with($userId)
            ->willReturn($existing);

        $this->pointsRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (RewardPoint $rp) use ($initialPoints, $add) {
                return $rp->getPoints() === $initialPoints + $add;
            }));

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (PointsEarnedEvent $event) use ($userId, $add, $initialPoints) {
                return $event->getUserId() === $userId
                    && $event->getPoints() === $add
                    && $event->getNewTotal() === $initialPoints + $add;
            }));

        $this->service->addPoints($userId, $add, $reason);
    }

    public function testRemovePointsThrowsWhenNotFound(): void
    {
        $userId = 'user-123';
        $points = 10;

        $this->pointsRepo->expects($this->once())
            ->method('findByUser')
            ->with($userId)
            ->willReturn(null);

        $this->userRepo->expects($this->never())
            ->method('find');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Reward points not found');

        $this->service->removePoints($userId, $points, 'reason');
    }

    public function testRemovePointsThrowsWhenInsufficient(): void
    {
        $userId = 'user-123';
        $currentPoints = 5;
        $remove = 10;

        $existing = new RewardPoint($this->userMock);
        $ref = new \ReflectionProperty(RewardPoint::class, 'points');
        $ref->setAccessible(true);
        $ref->setValue($existing, $currentPoints);

        $this->pointsRepo->expects($this->once())
            ->method('findByUser')
            ->with($userId)
            ->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient points');

        $this->service->removePoints($userId, $remove, 'reason');
    }

    public function testRemovePointsDecrementsSuccessfully(): void
    {
        $userId = 'user-123';
        $currentPoints = 50;
        $remove = 20;

        $existing = new RewardPoint($this->userMock);
        $ref = new \ReflectionProperty(RewardPoint::class, 'points');
        $ref->setAccessible(true);
        $ref->setValue($existing, $currentPoints);
        $refLife = new \ReflectionProperty(RewardPoint::class, 'lifetimePoints');
        $refLife->setAccessible(true);
        $refLife->setValue($existing, $currentPoints);

        $this->pointsRepo->expects($this->once())
            ->method('findByUser')
            ->with($userId)
            ->willReturn($existing);

        $this->pointsRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (RewardPoint $rp) use ($currentPoints, $remove) {
                return $rp->getPoints() === $currentPoints - $remove;
            }));

        $this->service->removePoints($userId, $remove, 'reason');
    }

    public function testEarnBadgeCreatesBadgeAndDispatchesEvent(): void
    {
        $userId = 'user-123';
        $badgeType = 'explorer';
        $badgeName = 'World Traveler';
        $description = 'Explored many places';
        $icon = 'globe';
        $metadata = ['level' => 1];
        $capturedBadge = null;

        $this->userRepo->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($this->userMock);

        $this->badgeRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (AchievementBadge $badge) use (&$capturedBadge, $userId, $badgeType, $badgeName, $description, $icon, $metadata) {
                $capturedBadge = $badge;
                // Verify basic attributes
                if ($badge->getUser() !== $this->userMock) {
                    return false;
                }
                if ($badge->getType() !== $badgeType) {
                    return false;
                }
                if ($badge->getName() !== $badgeName) {
                    return false;
                }
                // Check optional fields via reflection
                $refDesc = new \ReflectionProperty(AchievementBadge::class, 'description');
                $refDesc->setAccessible(true);
                if ($refDesc->getValue($badge) !== $description) {
                    return false;
                }
                $refIcon = new \ReflectionProperty(AchievementBadge::class, 'icon');
                $refIcon->setAccessible(true);
                if ($refIcon->getValue($badge) !== $icon) {
                    return false;
                }
                $refMeta = new \ReflectionProperty(AchievementBadge::class, 'metadata');
                $refMeta->setAccessible(true);
                if ($refMeta->getValue($badge) !== $metadata) {
                    return false;
                }
                return true;
            }));

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (BadgeEarnedEvent $event) use ($userId, &$capturedBadge) {
                return $event->getUserId() === $userId
                    && $event->getBadgeId() === $capturedBadge->getId()
                    && $event->getBadgeType() === $capturedBadge->getType()
                    && $event->getBadgeName() === $capturedBadge->getName();
            }));

        $result = $this->service->earnBadge($userId, $badgeType, $badgeName, $description, $icon, $metadata);

        $this->assertInstanceOf(AchievementBadge::class, $result);
    }

    public function testGetUserPointsReturnsRewardPoints(): void
    {
        $userId = 'user-123';
        $rewardPoints = new RewardPoint($this->userMock);

        $this->pointsRepo->expects($this->once())
            ->method('findByUser')
            ->with($userId)
            ->willReturn($rewardPoints);

        $result = $this->service->getUserPoints($userId);
        $this->assertSame($rewardPoints, $result);
    }

    public function testGetUserPointsReturnsNullWhenNotFound(): void
    {
        $userId = 'user-123';

        $this->pointsRepo->expects($this->once())
            ->method('findByUser')
            ->with($userId)
            ->willReturn(null);

        $result = $this->service->getUserPoints($userId);
        $this->assertNull($result);
    }

    public function testGetUserBadgesReturnsArray(): void
    {
        $userId = 'user-123';
        $badges = [
            new AchievementBadge($this->userMock, 'type1', 'Badge1'),
            new AchievementBadge($this->userMock, 'type2', 'Badge2'),
        ];

        $this->badgeRepo->expects($this->once())
            ->method('findByUser')
            ->with($userId)
            ->willReturn($badges);

        $result = $this->service->getUserBadges($userId);
        $this->assertSame($badges, $result);
    }

    public function testGetLeaderboardReturnsTopUsers(): void
    {
        $limit = 50;
        $topUsers = [
            new RewardPoint($this->userMock),
            new RewardPoint($this->userMock),
        ];

        $this->pointsRepo->expects($this->once())
            ->method('findTopUsers')
            ->with($limit)
            ->willReturn($topUsers);

        $result = $this->service->getLeaderboard($limit);
        $this->assertSame($topUsers, $result);
    }

    public function testGetBadgesByTypeReturnsBadges(): void
    {
        $badgeType = 'achievement';
        $badges = [
            new AchievementBadge($this->userMock, $badgeType, 'BadgeA'),
        ];

        $this->badgeRepo->expects($this->once())
            ->method('findByType')
            ->with($badgeType)
            ->willReturn($badges);

        $result = $this->service->getBadgesByType($badgeType);
        $this->assertSame($badges, $result);
    }
}
