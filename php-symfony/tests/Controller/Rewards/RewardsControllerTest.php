<?php

declare(strict_types=1);

namespace App\Tests\Controller\Rewards;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Rewards\Controller\RewardsController;
use App\Rewards\Entity\Points;
use App\Rewards\Entity\Badge;
use App\Rewards\Service\RewardsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\User\Entity\User;

#[AllowMockObjectsWithoutExpectations]
class RewardsControllerTest extends TestCase
{
    private RewardsController $controller;
    private RewardsService|MockObject $rewardsService;
    private User|MockObject $currentUser;

    protected function setUp(): void
    {
        $this->rewardsService = $this->createMock(RewardsService::class);
        $this->currentUser = $this->createMock(User::class);
        $this->currentUser->method('getId')->willReturn('user123');

        $this->controller = new RewardsController($this->rewardsService);
        $this->setTestUser();
    }

    private function setTestUser(): void
    {
        $this->controller->setTestUser($this->currentUser);
    }

    public function testGetMyPointsSuccess(): void
    {
        $points = $this->createMock(Points::class);
        $points->method('toArray')->willReturn([
            'userId' => 'user123',
            'balance' => 1000,
            'totalEarned' => 2000,
            'totalWithdrawn' => 1000,
        ]);

        $this->rewardsService
            ->expects($this->once())
            ->method('getUserPoints')
            ->with('user123')
            ->willReturn($points);

        $result = $this->controller->getMyPoints();

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals(1000, $data['balance']);
        $this->assertEquals('user123', $data['userId']);
    }

    public function testGetMyPointsNotFound(): void
    {
        $this->rewardsService
            ->expects($this->once())
            ->method('getUserPoints')
            ->with('user123')
            ->willReturn(null);

        $result = $this->controller->getMyPoints();

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Reward points not found', $data['error']);
    }

    public function testGetMyBadges(): void
    {
        $badges = [
            ['id' => 'badge1', 'name' => 'First Post', 'type' => 'social'],
            ['id' => 'badge2', 'name' => '100 Points', 'type' => 'points'],
        ];

        $this->rewardsService
            ->expects($this->once())
            ->method('getUserBadges')
            ->with('user123')
            ->willReturn($badges);

        $result = $this->controller->getMyBadges();

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertEquals('First Post', $data[0]['name']);
    }

    public function testGetLeaderboard(): void
    {
        $request = new Request(['limit' => '50']);
        $leaderboard = [
            ['userId' => 'user1', 'points' => 5000, 'rank' => 1],
            ['userId' => 'user2', 'points' => 4000, 'rank' => 2],
        ];

        $this->rewardsService
            ->expects($this->once())
            ->method('getLeaderboard')
            ->with(50)
            ->willReturn($leaderboard);

        $result = $this->controller->getLeaderboard($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertEquals('user1', $data[0]['userId']);
        $this->assertEquals(5000, $data[0]['points']);
    }

    public function testGetLeaderboardDefaultLimit(): void
    {
        $request = new Request();
        $leaderboard = [];

        $this->rewardsService
            ->expects($this->once())
            ->method('getLeaderboard')
            ->with(100)
            ->willReturn($leaderboard);

        $result = $this->controller->getLeaderboard($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testAddPointsSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'points' => 100,
                'reason' => 'Completed activity',
            ])
        );

        $this->rewardsService
            ->expects($this->once())
            ->method('addPoints')
            ->with('user123', 100, 'Completed activity');

        $result = $this->controller->addPoints($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Points added successfully', $data['message']);
    }

    public function testAddPointsInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->addPoints($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testAddPointsMissingFields(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['points' => 100])
        );

        $result = $this->controller->addPoints($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Points and reason are required', $data['error']);
    }

    public function testWithdrawPointsSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'points' => 50,
                'reason' => 'Redeemed reward',
            ])
        );

        $this->rewardsService
            ->expects($this->once())
            ->method('removePoints')
            ->with('user123', 50, 'Redeemed reward');

        $result = $this->controller->withdrawPoints($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Points withdrawn successfully', $data['message']);
    }

    public function testWithdrawPointsInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->withdrawPoints($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testWithdrawPointsMissingFields(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['points' => 50])
        );

        $result = $this->controller->withdrawPoints($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Points and reason are required', $data['error']);
    }

    public function testWithdrawPointsInsufficientBalance(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'points' => 9999,
                'reason' => 'Redeemed reward',
            ])
        );

        $this->rewardsService
            ->expects($this->once())
            ->method('removePoints')
            ->with('user123', 9999, 'Redeemed reward')
            ->willThrowException(new \InvalidArgumentException('Insufficient points balance'));

        $result = $this->controller->withdrawPoints($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Insufficient points balance', $data['error']);
    }

    public function testEarnBadgeSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'badge_type' => 'social',
                'badge_name' => 'First Post Badge',
                'description' => 'Awarded for first post',
                'icon' => '🏆',
            ])
        );

        $badge = $this->createMock(Badge::class);
        $badge->method('toArray')->willReturn([
            'id' => 'badge1',
            'userId' => 'user123',
            'type' => 'social',
            'name' => 'First Post Badge',
            'description' => 'Awarded for first post',
            'icon' => '🏆',
        ]);

        $this->rewardsService
            ->expects($this->once())
            ->method('earnBadge')
            ->with('user123', 'social', 'First Post Badge', 'Awarded for first post', '🏆', null)
            ->willReturn($badge);

        $result = $this->controller->earnBadge($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('badge1', $data['id']);
        $this->assertEquals('First Post Badge', $data['name']);
    }

    public function testEarnBadgeInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->earnBadge($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testEarnBadgeMissingFields(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['badge_type' => 'social'])
        );

        $result = $this->controller->earnBadge($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Badge type and name are required', $data['error']);
    }

    public function testEarnBadgeEmptyFields(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['badge_type' => '', 'badge_name' => ''])
        );

        $result = $this->controller->earnBadge($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Badge type and name are required', $data['error']);
    }

    public function testGetBadgesByType(): void
    {
        $request = new Request();
        $badges = [
            ['id' => 'badge1', 'name' => 'First Post', 'type' => 'social'],
            ['id' => 'badge2', 'name' => 'Popular Post', 'type' => 'social'],
        ];

        $this->rewardsService
            ->expects($this->once())
            ->method('getBadgesByType')
            ->with('social')
            ->willReturn($badges);

        $result = $this->controller->getBadgesByType($request, 'social');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertEquals('social', $data[0]['type']);
    }
}
