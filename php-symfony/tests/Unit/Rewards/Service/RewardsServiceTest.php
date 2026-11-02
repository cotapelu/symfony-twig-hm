<?php declare(strict_types=1);

namespace App\Tests\Unit\Rewards\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Rewards\Entity\AchievementBadge;
use App\Rewards\Entity\RewardPoint;
use App\Rewards\Repository\AchievementBadgeRepository;
use App\Rewards\Repository\RewardPointRepository;
use App\Rewards\Service\RewardsService;
use App\Shared\EventBus\EventBus;
use App\User\Repository\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use App\User\Entity\User;

#[AllowMockObjectsWithoutExpectations]

class RewardsServiceTest extends TestCase
{
    private RewardPointRepository&MockObject $pointsRepoMock;
    private AchievementBadgeRepository&MockObject $badgeRepoMock;
    private EventBus&MockObject $eventBusMock;
    private UserRepository&MockObject $userRepoMock;
    private RewardsService $service;

    protected function setUp(): void
    {
        $this->pointsRepoMock = $this->createMock(RewardPointRepository::class);
        $this->badgeRepoMock = $this->createMock(AchievementBadgeRepository::class);
        $this->eventBusMock = $this->createMock(EventBus::class);
        $this->userRepoMock = $this->createMock(UserRepository::class);

        $this->service = new RewardsService(
            $this->pointsRepoMock,
            $this->badgeRepoMock,
            $this->eventBusMock,
            $this->userRepoMock
        );
    }

    private function createUser(string $id = 'user_1'): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    public function testAddPointsCreatesIfNotExistsAndDispatchesEvent(): void
    {
        $userId = 'user_1';
        $user = $this->createUser($userId);
        $this->userRepoMock->expects($this->once())->method('find')->with($userId)->willReturn($user);

        $this->pointsRepoMock->expects($this->once())->method('findByUser')->with($userId)->willReturn(null);
        $this->pointsRepoMock->expects($this->once())->method('save')->with($this->callback(function(RewardPoint $rp) use ($userId) {
            return $rp->getUser()->getId() === $userId && $rp->getPoints() === 100;
        }));

        $this->eventBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($event) use ($userId) {
                return method_exists($event, 'getUserId') && $event->getUserId() === $userId
                    && $event->getPoints() === 100
                    && $event->getNewTotal() === 100;
            }));

        $this->service->addPoints($userId, 100, 'test');
    }

    public function testAddPointsIncrementsExisting(): void
    {
        $userId = 'user_1';
        $user = $this->createUser($userId);
        $existingPoints = new RewardPoint($user);
        // Set initial points to 50 so adding 100 yields 150
        $existingPoints->addPoints(50);
        $refId = new \ReflectionProperty(RewardPoint::class, 'id');
        $refId->setAccessible(true);
        $refId->setValue($existingPoints, 'rp_123');

        // When points exist, user is not fetched again
        $this->userRepoMock->expects($this->never())->method('find');
        $this->pointsRepoMock->expects($this->once())->method('findByUser')->with($userId)->willReturn($existingPoints);
        $this->pointsRepoMock->expects($this->once())->method('save')->with($this->callback(function(RewardPoint $rp) use ($userId) {
            return $rp->getUser()->getId() === $userId && $rp->getPoints() === 150;
        }));

        $this->eventBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($event) use ($userId) {
                return method_exists($event, 'getUserId') && $event->getUserId() === $userId
                    && $event->getPoints() === 100
                    && $event->getNewTotal() === 150;
            }));

        $this->service->addPoints($userId, 100, 'test');
    }

    public function testRemovePointsThrowsWhenNotFound(): void
    {
        $this->pointsRepoMock->expects($this->once())->method('findByUser')->with('unknown')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->removePoints('unknown', 50, 'reason');
    }

    public function testRemovePointsThrowsWhenInsufficient(): void
    {
        $user = $this->createUser('u');
        $rp = new RewardPoint($user);
        $rp->addPoints(30); // now 30

        $this->pointsRepoMock->expects($this->once())->method('findByUser')->with('u')->willReturn($rp);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->removePoints('u', 50, 'reason');
    }

    public function testRemovePointsSuccess(): void
    {
        $user = $this->createUser('u');
        $rp = new RewardPoint($user);
        $rp->addPoints(100);

        $this->pointsRepoMock->expects($this->once())->method('findByUser')->with('u')->willReturn($rp);
        $this->pointsRepoMock->expects($this->once())->method('save')->with($rp);

        $this->service->removePoints('u', 30, 'reason');
        $this->assertEquals(70, $rp->getPoints());
    }

    public function testEarnBadgeCreatesAndDispatchesEvent(): void
    {
        $userId = 'user_1';
        $user = $this->createUser($userId);
        $this->userRepoMock->expects($this->once())->method('find')->with($userId)->willReturn($user);

        $badge = $this->createMock(AchievementBadge::class);
        $badge->method('getId')->willReturn('badge_123');
        $badge->method('getEarnedAt')->willReturn(new \DateTime());

        // Mock the constructor to return our badge
        $this->badgeRepoMock->expects($this->once())
            ->method('save')
            ->with($this->callback(function(AchievementBadge $b) use ($userId) {
                return $b->getUser()->getId() === $userId
                    && $b->getType() === 'type1'
                    && $b->getName() === 'Badge Name';
            }))
            ->willReturnArgument(0);

        $this->eventBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($event) use ($userId) {
                return method_exists($event, 'getBadgeId') && is_string($event->getBadgeId())
                    && $event->getUserId() === $userId
                    && $event->getBadgeType() === 'type1'
                    && $event->getBadgeName() === 'Badge Name';
            }));

        $result = $this->service->earnBadge($userId, 'type1', 'Badge Name');
        $this->assertInstanceOf(AchievementBadge::class, $result);
    }

    public function testGetUserPointsDelegates(): void
    {
        $points = new RewardPoint($this->createUser('u'));
        $this->pointsRepoMock->expects($this->once())->method('findByUser')->with('u')->willReturn($points);
        $result = $this->service->getUserPoints('u');
        $this->assertSame($points, $result);
    }

    public function testGetUserBadgesDelegates(): void
    {
        $badges = [new AchievementBadge($this->createUser('u'), 'type', 'name')];
        $this->badgeRepoMock->expects($this->once())->method('findByUser')->with('u')->willReturn($badges);
        $result = $this->service->getUserBadges('u');
        $this->assertSame($badges, $result);
    }

    public function testGetLeaderboardDelegates(): void
    {
        $users = [new RewardPoint($this->createUser('u1')), new RewardPoint($this->createUser('u2'))];
        $this->pointsRepoMock->expects($this->once())->method('findTopUsers')->with(100)->willReturn($users);
        $result = $this->service->getLeaderboard();
        $this->assertSame($users, $result);
    }

    public function testGetBadgesByTypeDelegates(): void
    {
        $badges = [new AchievementBadge($this->createUser('u'), 'typeA', 'name')];
        $this->badgeRepoMock->expects($this->once())->method('findByType')->with('typeA')->willReturn($badges);
        $result = $this->service->getBadgesByType('typeA');
        $this->assertSame($badges, $result);
    }

    public function testGetUserPointsReturnsNullWhenNone(): void
    {
        $this->pointsRepoMock->expects($this->once())->method('findByUser')->with('u')->willReturn(null);
        $result = $this->service->getUserPoints('u');
        $this->assertNull($result);
    }

    public function testGetUserBadgesReturnsEmptyArrayWhenNone(): void
    {
        $this->badgeRepoMock->expects($this->once())->method('findByUser')->with('u')->willReturn([]);
        $result = $this->service->getUserBadges('u');
        $this->assertSame([], $result);
    }
}
