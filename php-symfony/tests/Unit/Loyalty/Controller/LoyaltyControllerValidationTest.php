<?php

declare(strict_types=1);

namespace Tests\Unit\Loyalty\Controller;

use App\Loyalty\Controller\LoyaltyController;
use App\Loyalty\Service\LoyaltyService;
use App\Loyalty\Entity\LoyaltyProgram;
use App\Loyalty\Entity\LoyaltyReward;
use Symfony\Component\HttpFoundation\JsonResponse;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use InvalidArgumentException;

class LoyaltyControllerValidationTest extends TestCase
{
    private LoyaltyController $controller;

    protected function setUp(): void
    {
        $serviceMock = $this->createMock(LoyaltyService::class);
        $this->controller = new LoyaltyController($serviceMock);
    }

    private function invokePrivate(string $method, array $args = [])
    {
        $ref = new ReflectionMethod($this->controller, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->controller, ...$args);
    }

    // ==================== validatePoints ====================

    public function testValidatePoints_EmptyArray_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON payload');
        $this->invokePrivate('validatePoints', [[]]);
    }

    public function testValidatePoints_MissingPointsKey_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Points must be positive');
        $this->invokePrivate('validatePoints', [['other' => 'value']]);
    }

    public function testValidatePoints_Zero_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Points must be positive');
        $this->invokePrivate('validatePoints', [['points' => 0]]);
    }

    public function testValidatePoints_Negative_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Points must be positive');
        $this->invokePrivate('validatePoints', [['points' => -5]]);
    }

    public function testValidatePoints_Valid_ReturnsInt(): void
    {
        $result = $this->invokePrivate('validatePoints', [['points' => 100]]);
        $this->assertSame(100, $result);
    }

    // ==================== validateCreateRewardData ====================

    public function testValidateCreateRewardData_MissingProgramId_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Program ID is required');
        $this->invokePrivate('validateCreateRewardData', [['name' => 'Reward', 'description' => 'Desc', 'points_cost' => 50]]);
    }

    public function testValidateCreateRewardData_MissingName_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reward name is required');
        $this->invokePrivate('validateCreateRewardData', [['program_id' => 1, 'description' => 'Desc', 'points_cost' => 50]]);
    }

    public function testValidateCreateRewardData_MissingDescription_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reward description is required');
        $this->invokePrivate('validateCreateRewardData', [['program_id' => 1, 'name' => 'Reward', 'points_cost' => 50]]);
    }

    public function testValidateCreateRewardData_MissingPointsCost_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Points cost must be positive');
        $this->invokePrivate('validateCreateRewardData', [['program_id' => 1, 'name' => 'Reward', 'description' => 'Desc']]);
    }

    public function testValidateCreateRewardData_ZeroPointsCost_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Points cost must be positive');
        $this->invokePrivate('validateCreateRewardData', [['program_id' => 1, 'name' => 'Reward', 'description' => 'Desc', 'points_cost' => 0]]);
    }

    public function testValidateCreateRewardData_NegativePointsCost_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Points cost must be positive');
        $this->invokePrivate('validateCreateRewardData', [['program_id' => 1, 'name' => 'Reward', 'description' => 'Desc', 'points_cost' => -1]]);
    }

    public function testValidateCreateRewardData_Valid_NoException(): void
    {
        $this->invokePrivate('validateCreateRewardData', [['program_id' => 1, 'name' => 'Reward', 'description' => 'Desc', 'points_cost' => 50]]);
        $this->assertTrue(true);
    }

    // ==================== buildListResponse ====================

    public function testBuildListResponse_EmptyList(): void
    {
        $response = $this->invokePrivate('buildListResponse', [[], 0, 20, 0]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertEmpty($data['data']);
        $this->assertSame(0, $data['meta']['total']);
        $this->assertSame(20, $data['meta']['limit']);
        $this->assertSame(0, $data['meta']['offset']);
        $this->assertSame(0, $data['meta']['count']);
    }

    public function testBuildListResponse_WithItems(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Item1'],
            ['id' => 2, 'name' => 'Item2'],
        ];
        $response = $this->invokePrivate('buildListResponse', [$items, 50, 20, 10]);
        $data = json_decode($response->getContent(), true);
        $this->assertSame($items, $data['data']);
        $this->assertSame(50, $data['meta']['total']);
        $this->assertSame(20, $data['meta']['limit']);
        $this->assertSame(10, $data['meta']['offset']);
        $this->assertSame(2, $data['meta']['count']);
    }

    // ==================== applyProgramFields ====================

    public function testApplyProgramFields_SetsTierRules(): void
    {
        $program = $this->createMock(LoyaltyProgram::class);
        $program->expects($this->once())->method('setTierRules')->with(['bronze', 'silver']);
        $this->invokePrivate('applyProgramFields', [$program, ['tier_rules' => ['bronze', 'silver']]]);
    }

    public function testApplyProgramFields_SetsRewards(): void
    {
        $program = $this->createMock(LoyaltyProgram::class);
        $program->expects($this->once())->method('setRewards')->with([['id'=>1]]);
        $this->invokePrivate('applyProgramFields', [$program, ['rewards' => [['id'=>1]]]]);
    }

    public function testApplyProgramFields_IgnoresMissingOptional(): void
    {
        $program = $this->createMock(LoyaltyProgram::class);
        $program->expects($this->never())->method('setTierRules');
        $program->expects($this->never())->method('setRewards');
        $this->invokePrivate('applyProgramFields', [$program, []]);
    }

    // ==================== applyRewardFields ====================

    public function testApplyRewardFields_SetsDescription(): void
    {
        $reward = $this->createMock(LoyaltyReward::class);
        $reward->expects($this->once())->method('setDescription')->with('New desc');
        $this->invokePrivate('applyRewardFields', [$reward, ['description' => 'New desc']]);
    }

    public function testApplyRewardFields_IgnoresMissingOptional(): void
    {
        $reward = $this->createMock(LoyaltyReward::class);
        $reward->expects($this->never())->method('setDescription');
        $this->invokePrivate('applyRewardFields', [$reward, []]);
    }
}
