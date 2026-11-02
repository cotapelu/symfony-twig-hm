<?php

declare(strict_types=1);

namespace Tests\Unit\Loyalty\Controller;

use App\Loyalty\Controller\LoyaltyController;
use App\Loyalty\Service\LoyaltyService;
use App\Loyalty\Entity\LoyaltyProgram;
use App\Loyalty\Entity\LoyaltyMember;
use App\Loyalty\Entity\LoyaltyReward;
use App\Loyalty\Entity\LoyaltyRedemption;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class LoyaltyControllerTest extends TestCase
{
    private LoyaltyService $serviceMock;
    private LoyaltyController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(LoyaltyService::class);
        $this->controller = new LoyaltyController($this->serviceMock);
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function setAuthenticatedUser(string $userId, int $userIdInt = 1): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn($userId);
        $user->method('getId')->willReturn((string)$userIdInt);
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createProgramMock(int $id, int $userId, string $name = 'Program', string $description = 'Desc', string $programType = 'standard'): LoyaltyProgram
    {
        $mock = $this->createMock(LoyaltyProgram::class);
        $mock->method('getId')->willReturn($id);
        $mock->method('getUserId')->willReturn($userId);
        $mock->method('getName')->willReturn($name);
        $mock->method('getDescription')->willReturn($description);
        $mock->method('getProgramType')->willReturn($programType);
        $mock->method('getTierRules')->willReturn([]);
        $mock->method('getRewards')->willReturn([]);
        $mock->method('isActive')->willReturn(true);
        return $mock;
    }

    private function createMemberMock(int $id, int $userId, int $points = 0, int $programId = 1, string $tier = 'bronze'): LoyaltyMember
    {
        $mock = $this->createMock(LoyaltyMember::class);
        $mock->method('getId')->willReturn($id);
        $mock->method('getUserId')->willReturn($userId);
        $mock->method('getProgramId')->willReturn($programId);
        $mock->method('getPoints')->willReturn($points);
        $mock->method('getTier')->willReturn($tier);
        return $mock;
    }

    private function createRewardMock(int $id, int $programId, int $pointsCost = 100, string $name = 'Reward', string $description = 'Desc', string $rewardType = 'discount'): LoyaltyReward
    {
        $mock = $this->createMock(LoyaltyReward::class);
        $mock->method('getId')->willReturn($id);
        $mock->method('getProgramId')->willReturn($programId);
        $mock->method('getPointsCost')->willReturn($pointsCost);
        $mock->method('getName')->willReturn($name);
        $mock->method('getDescription')->willReturn($description);
        $mock->method('getRewardType')->willReturn($rewardType);
        // setters
        $mock->method('setDescription')->willReturnSelf();
        $mock->method('setStock')->willReturnSelf();
        return $mock;
    }

    private function createRedemptionMock(?int $id, int $memberId, int $rewardId, int $pointsSpent): LoyaltyRedemption
    {
        $mock = $this->createMock(LoyaltyRedemption::class);
        $mock->method('getId')->willReturn($id);
        $mock->method('getMemberId')->willReturn($memberId);
        $mock->method('getRewardId')->willReturn($rewardId);
        $mock->method('getPointsSpent')->willReturn($pointsSpent);
        return $mock;
    }

    // ==================== PROGRAMS ====================

    public function testCreateProgram_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/loyalty/programs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Test', 'description' => 'Desc']));
        $response = $this->controller->createProgram($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCreateProgram_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->createProgram($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateProgram_MissingName_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $request = Request::create('/api/loyalty/programs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['description' => 'Desc']));
        $response = $this->controller->createProgram($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateProgram_Success_Returns201(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $program = $this->createProgramMock(1, 1);
        $this->serviceMock->expects($this->once())->method('createProgram')->with(1, 'Test Program', 'Description', 'standard', $this->anything(), $this->anything())->willReturn($program);
        $request = Request::create('/api/loyalty/programs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Test Program', 'description' => 'Description']));
        $response = $this->controller->createProgram($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testGetPrograms_Success_Returns200(): void
    {
        $this->serviceMock->expects($this->once())->method('getActivePrograms')->with(50, 0)->willReturn([$this->createProgramMock(1, 1)]);
        $this->serviceMock->expects($this->once())->method('countActivePrograms')->willReturn(1);
        $response = $this->controller->getPrograms(new Request());
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('meta', $content);
    }

    public function testGetProgram_InvalidId_Returns400(): void
    {
        $response = $this->controller->getProgram(0);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetProgram_NotFound_Returns404(): void
    {
        $this->serviceMock->method('getProgram')->with(1)->willReturn(null);
        $response = $this->controller->getProgram(1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetProgram_Success_Returns200(): void
    {
        $program = $this->createProgramMock(1, 1);
        $this->serviceMock->method('getProgram')->with(1)->willReturn($program);
        $response = $this->controller->getProgram(1);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateProgram_Unauthenticated_Returns401(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Updated']));
        $response = $this->controller->updateProgram(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUpdateProgram_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Updated']));
        $response = $this->controller->updateProgram(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateProgram_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $this->serviceMock->method('isProgramOwner')->with(1, 1)->willReturn(true);
        $this->serviceMock->method('getProgram')->with(1)->willReturn(null);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Updated']));
        $response = $this->controller->updateProgram(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateProgram_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $program = $this->createProgramMock(1, 1);
        $this->serviceMock->method('isProgramOwner')->with(1, 1)->willReturn(true);
        $this->serviceMock->method('getProgram')->with(1)->willReturn($program);
        $this->serviceMock->method('updateProgram')->willReturn($program);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Updated']));
        $response = $this->controller->updateProgram(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== MEMBERS ====================

    public function testEnrollMember_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/loyalty/members', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['program_id' => 1]));
        $response = $this->controller->enrollMember($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testEnrollMember_MissingProgramId_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $response = $this->controller->enrollMember($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testEnrollMember_Success_Returns201(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 1);
        $this->serviceMock->expects($this->once())->method('enrollMember')->with(1, 1)->willReturn($member);
        $request = Request::create('/api/loyalty/members', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['program_id' => 1]));
        $response = $this->controller->enrollMember($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testGetMyMemberships_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getMyMemberships(new Request());
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetMyMemberships_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $this->serviceMock->expects($this->once())->method('getMemberByUser')->with(1, 50, 0)->willReturn([$this->createMemberMock(1, 1)]);
        $this->serviceMock->expects($this->once())->method('countMembersByUser')->with(1)->willReturn(1);
        $response = $this->controller->getMyMemberships(new Request());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetMember_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $response = $this->controller->getMember(0, new Request());
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetMember_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $this->serviceMock->method('getMember')->with(1)->willReturn(null);
        $response = $this->controller->getMember(1, new Request());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetMember_AccessDenied_Returns403(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 2); // different userId
        $this->serviceMock->method('getMember')->with(1)->willReturn($member);
        $response = $this->controller->getMember(1, new Request());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetMember_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 1);
        $this->serviceMock->method('getMember')->with(1)->willReturn($member);
        $response = $this->controller->getMember(1, new Request());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testAddPoints_Unauthenticated_Returns401(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['points' => 100]));
        $response = $this->controller->addPoints(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAddPoints_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['points' => 100]));
        $response = $this->controller->addPoints(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddPoints_MemberNotFound_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $this->serviceMock->method('getMember')->with(1)->willReturn(null);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['points' => 100]));
        $response = $this->controller->addPoints(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddPoints_AccessDenied_Returns403(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 2); // different userId
        $this->serviceMock->method('getMember')->with(1)->willReturn($member);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['points' => 100]));
        $response = $this->controller->addPoints(1, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAddPoints_InvalidPoints_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 1);
        $this->serviceMock->method('getMember')->with(1)->willReturn($member);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['points' => -10]));
        $response = $this->controller->addPoints(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddPoints_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 1, 100);
        $this->serviceMock->method('getMember')->with(1)->willReturn($member);
        $updated = $this->createMemberMock(1, 1, 150);
        $this->serviceMock->method('addPoints')->with(1, 50)->willReturn($updated);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['points' => 50]));
        $response = $this->controller->addPoints(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== DEDUCT POINTS ====================

    public function testDeductPoints_Unauthenticated_Returns401(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['points' => 50]));
        $response = $this->controller->deductPoints(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testDeductPoints_Insufficient_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 1, 30);
        $this->serviceMock->method('getMember')->with(1)->willReturn($member);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['points' => 50]));
        $response = $this->controller->deductPoints(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testDeductPoints_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 1, 100);
        $this->serviceMock->method('getMember')->with(1)->willReturn($member);
        $updated = $this->createMemberMock(1, 1, 50);
        $this->serviceMock->method('deductPoints')->with(1, 50)->willReturn($updated);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['points' => 50]));
        $response = $this->controller->deductPoints(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== REWARDS ====================

    public function testCreateReward_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/loyalty/rewards', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'program_id' => 1, 'name' => 'Reward', 'description' => 'Desc', 'points_cost' => 100, 'reward_type' => 'discount'
        ]));
        $response = $this->controller->createReward($request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCreateReward_ValidationErrors_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $request = Request::create('/api/loyalty/rewards', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'program_id' => 0, 'name' => '', 'description' => '', 'points_cost' => 0, 'reward_type' => ''
        ]));
        $response = $this->controller->createReward($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateReward_NotOwner_Returns403(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $this->serviceMock->method('isProgramOwner')->with(1, 1)->willReturn(false);
        $request = Request::create('/api/loyalty/rewards', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'program_id' => 1, 'name' => 'Reward', 'description' => 'Desc', 'points_cost' => 100, 'reward_type' => 'discount'
        ]));
        $response = $this->controller->createReward($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCreateReward_Success_Returns201(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $this->serviceMock->method('isProgramOwner')->with(1, 1)->willReturn(true);
        $reward = $this->createRewardMock(1, 1);
        $this->serviceMock->expects($this->once())->method('createReward')->with(1, 'Reward', 'Desc', 100, 'discount', $this->anything(), $this->anything())->willReturn($reward);
        $request = Request::create('/api/loyalty/rewards', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'program_id' => 1, 'name' => 'Reward', 'description' => 'Desc', 'points_cost' => 100, 'reward_type' => 'discount'
        ]));
        $response = $this->controller->createReward($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testGetRewards_MissingProgramId_Returns400(): void
    {
        $request = new Request();
        $response = $this->controller->getRewards($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetRewards_Success_Returns200(): void
    {
        $this->serviceMock->expects($this->once())->method('getRewardsByProgram')->with(1, 50, 0)->willReturn([$this->createRewardMock(1, 1)]);
        $this->serviceMock->expects($this->once())->method('countRewardsByProgram')->with(1)->willReturn(1);
        $request = Request::create('/api/loyalty/rewards', 'GET', ['program_id' => '1']);
        $response = $this->controller->getRewards($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetReward_InvalidId_Returns400(): void
    {
        $response = $this->controller->getReward(0);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetReward_NotFound_Returns404(): void
    {
        $this->serviceMock->method('getReward')->with(1)->willReturn(null);
        $response = $this->controller->getReward(1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetReward_Success_Returns200(): void
    {
        $reward = $this->createRewardMock(1, 1);
        $this->serviceMock->method('getReward')->with(1)->willReturn($reward);
        $response = $this->controller->getReward(1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== REDEEM ====================

    public function testRedeemReward_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['program_id' => 1]));
        $response = $this->controller->redeemReward(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRedeemReward_MissingProgramId_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $response = $this->controller->redeemReward(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRedeemReward_NotMember_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $this->serviceMock->method('getMemberByUser')->with(1, 1)->willReturn([]);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['program_id' => 1]));
        $response = $this->controller->redeemReward(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRedeemReward_RewardNotFound_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 1, 100, 1);
        $this->serviceMock->method('getMemberByUser')->with(1, 1)->willReturn([$member]);
        $this->serviceMock->method('getReward')->with(1)->willReturn(null);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['program_id' => 1]));
        $response = $this->controller->redeemReward(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRedeemReward_InsufficientPoints_Returns400(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 1, 30, 1);
        $reward = $this->createRewardMock(1, 1, 100);
        $this->serviceMock->method('getMemberByUser')->with(1, 1)->willReturn([$member]);
        $this->serviceMock->method('getReward')->with(1)->willReturn($reward);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['program_id' => 1]));
        $response = $this->controller->redeemReward(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRedeemReward_Success_Returns201(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $member = $this->createMemberMock(1, 1, 200, 1);
        $reward = $this->createRewardMock(1, 1, 100);
        $updatedMember = $this->createMemberMock(1, 1, 100, 1);
        $redemption = $this->createRedemptionMock(1, 1, 1, 100);
        $this->serviceMock->method('getMemberByUser')->with(1, 1)->willReturn([$member]);
        $this->serviceMock->method('getReward')->with(1)->willReturn($reward);
        $this->serviceMock->method('deductPoints')->with(1, 100)->willReturn($updatedMember);
        $this->serviceMock->expects($this->once())->method('redeemReward')->with(1, 1)->willReturn($redemption);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['program_id' => 1]));
        $response = $this->controller->redeemReward(1, $request);
        $this->assertSame(201, $response->getStatusCode());
    }

    // ==================== MY REDEMPTIONS ====================

    public function testGetMyRedemptions_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getMyRedemptions(new Request());
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetMyRedemptions_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user1', 1);
        $this->serviceMock->expects($this->once())->method('getMemberByUser')->with(1, 10)->willReturn([]);
        $response = $this->controller->getMyRedemptions(new Request());
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEmpty($content['data']);
    }
}