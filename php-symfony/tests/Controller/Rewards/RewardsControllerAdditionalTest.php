<?php

declare(strict_types=1);

namespace App\Tests\Controller\Rewards;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use App\Rewards\Controller\RewardsController;
use App\Rewards\Service\RewardsService;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[AllowMockObjectsWithoutExpectations]
class RewardsControllerAdditionalTest extends \PHPUnit\Framework\TestCase
{
    private RewardsController $controller;
    private RewardsService| \PHPUnit\Framework\MockObject\MockObject $rewardsService;
    private User|\PHPUnit\Framework\MockObject\MockObject $currentUser;

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

    private function jsonRequest(array $data = []): Request
    {
        return Request::create('/api/rewards/test', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    // ==================== Additional Validation Tests ====================

    public function testAddPoints_PointsExceedsMaximum(): void
    {
        $request = $this->jsonRequest([
            'points' => 2000000, // exceeds MAX_POINTS_PER_TRANSACTION (1,000,000)
            'reason' => 'Test reason',
        ]);
        $response = $this->controller->addPoints($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Points exceed maximum allowed (1000000)', $data['error']);
    }

    public function testAddPoints_ReasonTooLong(): void
    {
        $reason = str_repeat('a', 501); // > 500
        $request = $this->jsonRequest([
            'points' => 100,
            'reason' => $reason,
        ]);
        $response = $this->controller->addPoints($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Reason too long', $data['error']);
        $this->assertStringContainsString('500', $data['error']);
    }

    public function testEarnBadge_InvalidBadgeType(): void
    {
        $request = $this->jsonRequest([
            'badge_type' => 'invalid',
            'badge_name' => 'Test Badge',
        ]);
        $response = $this->controller->earnBadge($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Invalid badge type', $data['error']);
    }

    public function testEarnBadge_BadgeNameTooLong(): void
    {
        $name = str_repeat('b', 101); // > 100
        $request = $this->jsonRequest([
            'badge_type' => 'milestone',
            'badge_name' => $name,
        ]);
        $response = $this->controller->earnBadge($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Badge name too long', $data['error']);
        $this->assertStringContainsString('100', $data['error']);
    }

    public function testEarnBadge_DescriptionTooLong(): void
    {
        $desc = str_repeat('c', 501); // > 500
        $request = $this->jsonRequest([
            'badge_type' => 'milestone',
            'badge_name' => 'Test Badge',
            'description' => $desc,
        ]);
        $response = $this->controller->earnBadge($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Description too long', $data['error']);
        $this->assertStringContainsString('500', $data['error']);
    }
}
