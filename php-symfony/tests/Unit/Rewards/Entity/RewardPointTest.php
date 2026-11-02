<?php declare(strict_types=1);

namespace App\Tests\Unit\Rewards\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Rewards\Entity\RewardPoint;
use App\User\Entity\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RewardPointTest extends TestCase
{
    private function createUser(): User
    {
        return new User('user-123', 'test@example.com', []);
    }

    private function createRewardPoint(?User $user = null): RewardPoint
    {
        $user ??= $this->createUser();
        return new RewardPoint($user);
    }

    public function testConstructorInitializesDefaults(): void
    {
        $user = $this->createUser();
        $reward = new RewardPoint($user);
        $this->assertNotNull($reward->getId());
        $this->assertSame($user, $reward->getUser());
        $this->assertSame(0, $reward->getPoints());
        $this->assertSame(1, $reward->getLevel());
        $this->assertSame(0, $reward->getLifetimePoints());
        $this->assertInstanceOf(\DateTime::class, $reward->getCreatedAt());
    }

    public function testSetPoints(): void
    {
        $reward = $this->createRewardPoint();
        $reward->setPoints(150);
        $this->assertSame(150, $reward->getPoints());
        // lifetimePoints unchanged
        $this->assertSame(0, $reward->getLifetimePoints());
        // level remains 1 because lifetimePoints 0
        $this->assertSame(1, $reward->getLevel());
    }

    public function testAddPointsIncreasesPointsAndLifetimeAndLevel(): void
    {
        $reward = $this->createRewardPoint();
        $reward->addPoints(100);
        $this->assertSame(100, $reward->getPoints());
        $this->assertSame(100, $reward->getLifetimePoints());
        $this->assertSame(3, $reward->getLevel()); // >=100 <250

        $reward->addPoints(50); // total 150 lifetime
        $this->assertSame(150, $reward->getPoints());
        $this->assertSame(150, $reward->getLifetimePoints());
        $this->assertSame(3, $reward->getLevel()); // still 3 (>=100 <250)
    }

    public function testRemovePointsDecreasesPointsButNotLifetime(): void
    {
        $reward = $this->createRewardPoint();
        $reward->setPoints(50);
        $this->assertSame(50, $reward->getPoints());

        $reward->removePoints(30);
        $this->assertSame(20, $reward->getPoints());
        $this->assertSame(0, $reward->getLifetimePoints()); // lifetime unchanged by removePoints

        // remove more than current -> clamp to 0
        $reward->removePoints(100);
        $this->assertSame(0, $reward->getPoints());
        $this->assertSame(0, $reward->getLifetimePoints());
    }

    public function testLevelThresholds(): void
    {
        $reward = $this->createRewardPoint();
        $this->assertSame(1, $reward->getLevel());

        $reward->addPoints(50);  // 50 -> level 2
        $this->assertSame(2, $reward->getLevel());

        $reward->addPoints(50);  // 100 -> level 3
        $this->assertSame(3, $reward->getLevel());

        $reward->addPoints(150); // 250 -> level 4
        $this->assertSame(4, $reward->getLevel());

        $reward->addPoints(250); // 500 -> level 5
        $this->assertSame(5, $reward->getLevel());

        $reward->addPoints(500); // 1000 -> level 6
        $this->assertSame(6, $reward->getLevel());

        $reward->addPoints(1000); // 2000 -> level 7
        $this->assertSame(7, $reward->getLevel());

        $reward->addPoints(1000); // 3000 -> level 8
        $this->assertSame(8, $reward->getLevel());

        $reward->addPoints(2000); // 5000 -> level 9
        $this->assertSame(9, $reward->getLevel());

        $reward->addPoints(5000); // 10000 -> level 10
        $this->assertSame(10, $reward->getLevel());
    }

    public function testToArrayStructure(): void
    {
        $user = $this->createUser();
        $reward = new RewardPoint($user);
        $reward->addPoints(200);
        $arr = $reward->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('userId', $arr);
        $this->assertSame($user->getId(), $arr['userId']);
        $this->assertArrayHasKey('points', $arr);
        $this->assertArrayHasKey('level', $arr);
        $this->assertArrayHasKey('lifetimePoints', $arr);
        $this->assertArrayHasKey('createdAt', $arr);
        $this->assertArrayHasKey('updatedAt', $arr);
        $this->assertSame(200, $arr['points']);
        $this->assertSame(200, $arr['lifetimePoints']);
        $this->assertSame(3, $arr['level']); // >=100 <250
    }
}
