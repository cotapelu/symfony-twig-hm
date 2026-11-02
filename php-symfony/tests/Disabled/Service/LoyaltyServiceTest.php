<?php

declare(strict_types=1);

namespace App\Tests\Unit\Modules\Loyalty\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Loyalty\Service\LoyaltyService;
use App\Loyalty\Entity\LoyaltyProgram;
use App\Loyalty\Entity\LoyaltyMember;
use App\Loyalty\Entity\LoyaltyReward;
use App\Loyalty\Repository\LoyaltyProgramRepository;
use App\Loyalty\Repository\LoyaltyMemberRepository;
use App\Loyalty\Repository\LoyaltyRewardRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class LoyaltyServiceTest extends TestCase
{
    private LoyaltyService $service;
    private $em;
    private $programRepo;
    private $memberRepo;
    private $rewardRepo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->programRepo = $this->createMock(LoyaltyProgramRepository::class);
        $this->memberRepo = $this->createMock(LoyaltyMemberRepository::class);
        $this->rewardRepo = $this->createMock(LoyaltyRewardRepository::class);

        $this->service = new LoyaltyService(
            $this->programRepo,
            $this->memberRepo,
            $this->rewardRepo,
            $this->em
        );
    }

    public function testCreateProgram(): void
    {
        $userId = 1;
        $name = 'Test Program';
        $description = 'A test loyalty program';
        $type = 'points';

        $program = $this->service->createProgram($userId, $name, $description, $type);

        $this->assertInstanceOf(LoyaltyProgram::class, $program);
        $this->assertEquals($name, $program->getName());
        $this->assertEquals($userId, $program->getUserId());
    }

    public function testCreateProgramValidationFailsForEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Program name is required');
        $this->service->createProgram(1, '', 'desc', 'points');
    }

    public function testIsProgramOwnerReturnsTrueWhenOwner(): void
    {
        $programId = 1;
        $userId = 5;
        $program = $this->createMock(LoyaltyProgram::class);
        $program->method('getUserId')->willReturn($userId);

        $this->programRepo->method('findById')->with($programId)->willReturn($program);

        $result = $this->service->isProgramOwner($programId, $userId);
        $this->assertTrue($result);
    }

    public function testIsProgramOwnerReturnsFalseWhenNotOwner(): void
    {
        $programId = 1;
        $userId = 5;
        $otherUserId = 6;
        $program = $this->createMock(LoyaltyProgram::class);
        $program->method('getUserId')->willReturn($otherUserId);

        $this->programRepo->method('findById')->with($programId)->willReturn($program);

        $result = $this->service->isProgramOwner($programId, $userId);
        $this->assertFalse($result);
    }

    public function testCreateMember(): void
    {
        $userId = 1;
        $programId = 2;

        $program = $this->createMock(LoyaltyProgram::class);
        $this->programRepo->method('findById')->with($programId)->willReturn($program);
        $this->memberRepo->method('findByUserAndProgram')->willReturn(null);

        $result = $this->service->createMember($userId, $programId);
        $this->assertInstanceOf(LoyaltyMember::class, $result);
    }

    public function testCreateMemberFailsForDuplicate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User already enrolled');

        $userId = 1;
        $programId = 2;

        $this->programRepo->method('findById')->willReturn($this->createMock(LoyaltyProgram::class));
        $this->memberRepo->method('findByUserAndProgram')->willReturn($this->createMock(LoyaltyMember::class));

        $this->service->createMember($userId, $programId);
    }

    public function testAddPointsWithOptimisticLockRetry(): void
    {
        $memberId = 1;
        $points = 100;

        $member = $this->createMock(LoyaltyMember::class);
        $member->method('getPoints')->willReturn(0);
        $member->expects($this->once())->method('addPoints')->with($points);

        $this->memberRepo->method('find')->with($memberId)->willReturn($member);
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->addPoints($memberId, $points);
        $this->assertSame($member, $result);
    }

    public function testDeductPointsInsufficientBalance(): void
    {
        $memberId = 1;
        $points = 100;

        $member = $this->createMock(LoyaltyMember::class);
        $member->method('getPoints')->willReturn(50);

        $this->memberRepo->method('find')->with($memberId)->willReturn($member);
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('rollback');
        $this->em->expects($this->never())->method('commit');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient points');
        $this->service->deductPoints($memberId, $points);
    }

    public function testCreateReward(): void
    {
        $programId = 1;
        $name = 'Free Coffee';
        $description = 'Get a free coffee';
        $pointsCost = 500;
        $rewardType = 'discount';

        $program = $this->createMock(LoyaltyProgram::class);
        $program->method('getUserId')->willReturn(10);
        $this->programRepo->method('findById')->with($programId)->willReturn($program);

        $this->rewardRepo->method('save')->willReturnCallback(function($r) {
            $this->assertInstanceOf(LoyaltyReward::class, $r);
        });

        $result = $this->service->createReward($programId, $name, $description, $pointsCost, $rewardType);
        $this->assertInstanceOf(LoyaltyReward::class, $result);
    }

    public function testCreateRewardFailsForInvalidProgram(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Program not found');
        $this->service->createReward(999, 'Reward', 'Desc', 100, 'discount');
    }
}
