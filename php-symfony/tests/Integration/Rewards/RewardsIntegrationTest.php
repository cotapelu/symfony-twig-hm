<?php

declare(strict_types=1);

namespace App\Tests\Integration\Rewards;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Rewards\Entity\RewardPoint;
use App\User\Entity\User;
use App\Rewards\Repository\RewardPointRepository;

#[AllowMockObjectsWithoutExpectations]
class RewardsIntegrationTest extends DatabaseIntegrationTestCase
{
    private RewardPointRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $em = self::getEntityManager();
        $this->repository = $em->getRepository(RewardPoint::class);
    }

    private function createUser(string $id, string $email): User
    {
        $user = new User($id, $email, ['ROLE_USER']);
        // Ensure verification exists to satisfy DB foreign key constraint
        $verification = new \App\User\Entity\UserVerification($id);
        $user->setVerification($verification);
        $em = self::getEntityManager();
        $em->persist($user);
        $em->flush();
        return $user;
    }

    public function testCreateRewardPoints(): void
    {
        $user = $this->createUser('user-rewards-1', 'rewards1@example.com');

        $reward = new RewardPoint($user);
        $this->repository->save($reward);

        $this->assertNotNull($reward->getId());
        $this->assertSame(0, $reward->getPoints());
        $this->assertSame(1, $reward->getLevel());
    }

    public function testAddPoints(): void
    {
        $user = $this->createUser('user-rewards-2', 'rewards2@example.com');
        $reward = new RewardPoint($user);
        $this->repository->save($reward);

        $reward->addPoints(150);
        $this->repository->save($reward);

        $found = $this->repository->findByUser($user->getId());
        $this->assertNotNull($found);
        $this->assertSame(150, $found->getPoints());
        $this->assertSame(3, $found->getLevel()); // >=100 points => level 3
    }

    public function testFindTopUsers(): void
    {
        // Create users with different points
        $userA = $this->createUser('top-user-A', 'a@example.com');
        $userB = $this->createUser('top-user-B', 'b@example.com');
        $userC = $this->createUser('top-user-C', 'c@example.com');

        $rewardA = new RewardPoint($userA);
        $rewardA->addPoints(250);
        $this->repository->save($rewardA);

        $rewardB = new RewardPoint($userB);
        $rewardB->addPoints(1000);
        $this->repository->save($rewardB);

        $rewardC = new RewardPoint($userC);
        $rewardC->addPoints(500);
        $this->repository->save($rewardC);

        $top = $this->repository->findTopUsers(2);
        $this->assertCount(2, $top);
        // Highest first: B (1000), C (500)
        $this->assertSame('top-user-B', $top[0]->getUser()->getId());
        $this->assertSame(1000, $top[0]->getPoints());
        $this->assertSame('top-user-C', $top[1]->getUser()->getId());
        $this->assertSame(500, $top[1]->getPoints());
    }

    public function testRemovePoints(): void
    {
        $user = $this->createUser('user-rewards-3', 'rewards3@example.com');
        $reward = new RewardPoint($user);
        $reward->addPoints(50);
        $this->repository->save($reward);

        $reward->removePoints(20);
        $this->repository->save($reward);

        $found = $this->repository->findByUser($user->getId());
        $this->assertSame(30, $found->getPoints());
    }

    public function testLifetimePointsAccumulate(): void
    {
        $user = $this->createUser('user-rewards-4', 'rewards4@example.com');
        $reward = new RewardPoint($user);
        $this->repository->save($reward);

        $reward->addPoints(100);
        $reward->addPoints(50);
        $this->repository->save($reward);

        $found = $this->repository->findByUser($user->getId());
        $this->assertSame(150, $found->getPoints());
        $this->assertSame(150, $found->toArray()['lifetimePoints']);
    }
}
