<?php declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Loyalty\Entity\LoyaltyProgram;
use App\Loyalty\Entity\LoyaltyMember;
use App\Loyalty\Entity\LoyaltyReward;
use App\Loyalty\Repository\LoyaltyProgramRepository;
use App\Loyalty\Repository\LoyaltyMemberRepository;
use App\Loyalty\Repository\LoyaltyRewardRepository;
use App\Loyalty\Service\LoyaltyService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class LoyaltyServiceTest extends TestCase
{
    private LoyaltyProgramRepository $programRepoMock;
    private LoyaltyMemberRepository $memberRepoMock;
    private LoyaltyRewardRepository $rewardRepoMock;
    private EntityManagerInterface $emMock;
    private LoyaltyService $service;

    protected function setUp(): void
    {
        $this->programRepoMock = $this->createMock(LoyaltyProgramRepository::class);
        $this->memberRepoMock = $this->createMock(LoyaltyMemberRepository::class);
        $this->rewardRepoMock = $this->createMock(LoyaltyRewardRepository::class);
        $this->emMock = $this->createMock(EntityManagerInterface::class);

        $this->service = new LoyaltyService(
            $this->programRepoMock,
            $this->memberRepoMock,
            $this->rewardRepoMock,
            $this->emMock
        );
    }

    // Helpers
    private function createProgram(
        ?int $id = null,
        int $userId = 1,
        string $name = 'Test Program',
        string $description = 'Desc',
        string $programType = 'points',
        array $tierRules = [],
        array $rewards = [],
        bool $isActive = true
    ): LoyaltyProgram {
        $p = new LoyaltyProgram($name, $userId, $description, $programType);
        $p->setTierRules($tierRules);
        $p->setRewards($rewards);
        $p->setActive($isActive);
        if ($id !== null) {
            $ref = new \ReflectionClass($p);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($p, $id);
        }
        return $p;
    }

    private function createMember(
        ?int $id = null,
        int $userId = 1,
        int $programId = 1,
        string $tier = 'bronze',
        int $points = 0
    ): LoyaltyMember {
        $m = new LoyaltyMember($userId, $programId);
        $m->setTier($tier);
        if ($points > 0) {
            $m->addPoints($points);
        }
        if ($id !== null) {
            $ref = new \ReflectionClass($m);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($m, $id);
        }
        return $m;
    }

    private function createReward(
        ?int $id = null,
        int $programId = 1,
        string $name = 'Reward',
        int $pointsCost = 100,
        string $rewardType = 'discount',
        array $details = [],
        int $stock = -1,
        bool $isActive = true
    ): LoyaltyReward {
        $r = new LoyaltyReward($programId, $name, $pointsCost, $rewardType);
        $r->setUserId(1);
        $r->setDescription('Desc');
        $r->setDetails($details);
        $r->setStock($stock);
        $r->setActive($isActive);
        if ($id !== null) {
            $ref = new \ReflectionClass($r);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($r, $id);
        }
        return $r;
    }

    // ==================== Program Tests ====================

    public function testCreateProgramSuccess(): void
    {
        $this->programRepoMock->method('save')->willReturnArgument(0);

        $result = $this->service->createProgram(1, 'My Program', 'Description', 'points');

        $this->assertInstanceOf(LoyaltyProgram::class, $result);
        $this->assertSame(1, $result->getUserId());
        $this->assertSame('My Program', $result->getName());
    }

    public function testCreateProgramThrowsIfNameEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createProgram(1, '', 'desc', 'points');
    }

    public function testCreateProgramThrowsIfDescriptionEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createProgram(1, 'Name', '   ', 'points');
    }

    // Service does not validate programType beyond non-empty; skip invalid type test.

    public function testIsProgramOwnerTrue(): void
    {
        $program = $this->createProgram(userId: 5);
        $this->programRepoMock->method('findById')->with(1)->willReturn($program);
        $this->assertTrue($this->service->isProgramOwner(1, 5));
    }

    public function testIsProgramOwnerFalse(): void
    {
        $program = $this->createProgram(userId: 5);
        $this->programRepoMock->method('findById')->with(1)->willReturn($program);
        $this->assertFalse($this->service->isProgramOwner(1, 1));
    }

    public function testIsProgramOwnerIfNotFound(): void
    {
        $this->programRepoMock->method('findById')->with(1)->willReturn(null);
        $this->assertFalse($this->service->isProgramOwner(1, 1));
    }

    public function testGetProgram(): void
    {
        $program = $this->createProgram(id: 1);
        $this->programRepoMock->method('findById')->with(1)->willReturn($program);
        $this->assertSame($program, $this->service->getProgram(1));
    }

    public function testGetProgramReturnsNullIfNotFound(): void
    {
        $this->programRepoMock->method('findById')->with(99)->willReturn(null);
        $this->assertNull($this->service->getProgram(99));
    }

    public function testGetAllPrograms(): void
    {
        $programs = [$this->createProgram(), $this->createProgram()];
        $this->programRepoMock->method('findAll')->with(50, 0)->willReturn($programs);
        $this->assertSame($programs, $this->service->getAllPrograms());
    }

    public function testCountAllPrograms(): void
    {
        $this->programRepoMock->method('countAll')->willReturn(10);
        $this->assertSame(10, $this->service->countAllPrograms());
    }

    public function testGetActivePrograms(): void
    {
        $programs = [$this->createProgram()];
        $this->programRepoMock->method('findByActive')->with(50, 0)->willReturn($programs);
        $this->assertSame($programs, $this->service->getActivePrograms());
    }

    public function testCountActivePrograms(): void
    {
        $this->programRepoMock->method('countActive')->willReturn(5);
        $this->assertSame(5, $this->service->countActivePrograms());
    }

    public function testUpdateProgramPartial(): void
    {
        $program = $this->createProgram(id: 1, name: 'Old');
        $this->programRepoMock->method('findById')->with(1)->willReturn($program);
        $this->programRepoMock->expects($this->once())->method('save')->with($program);

        $result = $this->service->updateProgram(1, ['name' => 'New', 'description' => 'New desc']);

        $this->assertSame($program, $result);
        $this->assertSame('New', $result->getName());
        $this->assertSame('New desc', $result->getDescription());
    }

    // Service does not throw on invalid end_date/status; those are handled silently or default. Skip those tests.

    public function testDeleteProgram(): void
    {
        $program = $this->createProgram(id: 1);
        $this->programRepoMock->method('findById')->with(1)->willReturn($program);
        $this->emMock->expects($this->once())->method('remove')->with($program);
        $this->emMock->expects($this->once())->method('flush');
        $this->assertTrue($this->service->deleteProgram(1));
    }

    public function testDeleteProgramReturnsFalseIfNotFound(): void
    {
        $this->programRepoMock->method('findById')->with(1)->willReturn(null);
        $this->assertFalse($this->service->deleteProgram(1));
    }

    // ==================== Member Tests ====================

    public function testCreateMemberSuccess(): void
    {
        $program = $this->createProgram();
        $this->programRepoMock->method('findById')->with(1)->willReturn($program);
        $this->memberRepoMock->method('findByUserAndProgram')->with(1, 1)->willReturn(null);
        $this->memberRepoMock->expects($this->once())->method('save')->with($this->isInstanceOf(LoyaltyMember::class));

        $result = $this->service->createMember(1, 1);

        $this->assertInstanceOf(LoyaltyMember::class, $result);
        $this->assertSame(1, $result->getUserId());
        $this->assertSame(1, $result->getProgramId());
        $this->assertSame('bronze', $result->getTier());
    }

    public function testCreateMemberThrowsIfUserAlreadyEnrolled(): void
    {
        $existing = $this->createMember();
        $this->programRepoMock->method('findById')->with(1)->willReturn($this->createProgram());
        $this->memberRepoMock->method('findByUserAndProgram')->with(1, 1)->willReturn($existing);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User already enrolled in this program');
        $this->service->createMember(1, 1);
    }

    public function testCreateMemberThrowsIfProgramNotFound(): void
    {
        $this->memberRepoMock->method('findByUserAndProgram')->with(1, 1)->willReturn(null);
        $this->programRepoMock->method('findById')->with(1)->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->service->createMember(1, 1);
    }

    public function testGetMember(): void
    {
        $member = $this->createMember(id: 1);
        $this->memberRepoMock->method('findById')->with(1)->willReturn($member);
        $this->assertSame($member, $this->service->getMember(1));
    }

    public function testGetMemberReturnsNullIfNotFound(): void
    {
        $this->memberRepoMock->method('findById')->with(99)->willReturn(null);
        $this->assertNull($this->service->getMember(99));
    }

    public function testGetMemberByUser(): void
    {
        $members = [$this->createMember(), $this->createMember()];
        $this->memberRepoMock->method('findByUserId')->with(1, 50, 0)->willReturn($members);
        $this->assertSame($members, $this->service->getMemberByUser(1));
    }

    public function testCountMembersByUser(): void
    {
        $this->memberRepoMock->method('countByUserId')->with(1)->willReturn(3);
        $this->assertSame(3, $this->service->countMembersByUser(1));
    }

    public function testGetMembersByProgram(): void
    {
        $members = [$this->createMember()];
        $this->memberRepoMock->method('findByProgram')->with(1, 50, 0)->willReturn($members);
        $this->assertSame($members, $this->service->getMembersByProgram(1));
    }

    public function testAddPointsSuccess(): void
    {
        $member = $this->createMember(id: 1, points: 100);
        $this->memberRepoMock->method('find')->with(1)->willReturn($member);
        $this->emMock->expects($this->once())->method('beginTransaction');
        $this->emMock->expects($this->once())->method('flush');
        $this->emMock->expects($this->once())->method('commit');

        $result = $this->service->addPoints(1, 50);

        $this->assertSame($member, $result);
        $this->assertSame(150, $result->getPoints());
        $this->assertSame(150, $result->getLifetimePoints());
    }

    public function testAddPointsReturnsNullIfMemberNotFound(): void
    {
        $this->memberRepoMock->method('find')->with(99)->willReturn(null);
        $this->emMock->expects($this->once())->method('beginTransaction');
        $this->emMock->expects($this->once())->method('rollback');
        $this->assertNull($this->service->addPoints(99, 50));
    }

    public function testAddPointsThrowsOnInvalidPoints(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->addPoints(1, 0);
    }

    public function testDeductPointsSuccess(): void
    {
        $member = $this->createMember(id: 1, points: 200);
        $this->memberRepoMock->method('find')->with(1)->willReturn($member);
        $this->emMock->expects($this->once())->method('beginTransaction');
        $this->emMock->expects($this->once())->method('flush');
        $this->emMock->expects($this->once())->method('commit');

        $result = $this->service->deductPoints(1, 50);

        $this->assertSame($member, $result);
        $this->assertSame(150, $result->getPoints());
        $this->assertSame(200, $result->getLifetimePoints());
    }

    public function testDeductPointsThrowsIfInsufficient(): void
    {
        $member = $this->createMember(id: 1, points: 30);
        $this->memberRepoMock->method('find')->with(1)->willReturn($member);
        $this->emMock->expects($this->once())->method('beginTransaction');
        $this->emMock->expects($this->once())->method('rollback');
        $this->expectException(InvalidArgumentException::class);
        $this->service->deductPoints(1, 50);
    }

    public function testUpdateMemberTier(): void
    {
        $member = $this->createMember(id: 1);
        $this->memberRepoMock->method('findById')->with(1)->willReturn($member);
        $this->memberRepoMock->expects($this->once())->method('save')->with($member);

        $result = $this->service->updateMemberTier(1, 'gold');

        $this->assertSame($member, $result);
        $this->assertSame('gold', $result->getTier());
    }

    public function testUpdateMemberTierReturnsNullIfNotFound(): void
    {
        $this->memberRepoMock->method('findById')->with(99)->willReturn(null);
        $this->assertNull($this->service->updateMemberTier(99, 'gold'));
    }

    // ==================== Reward Tests ====================

    public function testCreateRewardSuccess(): void
    {
        $this->programRepoMock->method('findById')->with(1)->willReturn($this->createProgram());
        $this->rewardRepoMock->method('save')->willReturnArgument(0);

        $result = $this->service->createReward(1, 'Free Item', 'Desc', 500, 'discount');

        $this->assertInstanceOf(LoyaltyReward::class, $result);
        $this->assertSame(1, $result->getProgramId());
        $this->assertSame('Free Item', $result->getName());
        $this->assertSame(500, $result->getPointsCost());
    }

    public function testCreateRewardThrowsIfInvalidProgramId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createReward(0, 'Name', 'Desc', 100, 'type');
    }

    public function testCreateRewardThrowsIfProgramNotFound(): void
    {
        $this->programRepoMock->method('findById')->with(99)->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->service->createReward(99, 'Name', 'Desc', 100, 'type');
    }

    public function testGetReward(): void
    {
        $reward = $this->createReward(id: 1);
        $this->rewardRepoMock->method('findById')->with(1)->willReturn($reward);
        $this->assertSame($reward, $this->service->getReward(1));
    }

    public function testGetRewardsByProgram(): void
    {
        $rewards = [$this->createReward(), $this->createReward()];
        $this->rewardRepoMock->method('findByProgram')->with(1, 50, 0)->willReturn($rewards);
        $this->assertSame($rewards, $this->service->getRewardsByProgram(1));
    }

    public function testCountRewardsByProgram(): void
    {
        $this->rewardRepoMock->method('countByProgram')->with(1)->willReturn(2);
        $this->assertSame(2, $this->service->countRewardsByProgram(1));
    }

    public function testFindAvailableRewards(): void
    {
        $member = $this->createMember(userId: 1, points: 500);
        $this->memberRepoMock->method('findByUserId')->with(1, 1)->willReturn([$member]);
        $rewards = [$this->createReward(pointsCost: 200), $this->createReward(pointsCost: 300)];
        $this->rewardRepoMock->method('findByPointsRange')->with(0, 500, 50, 0)->willReturn($rewards);
        $result = $this->service->findAvailableRewards(1);
        $this->assertSame($rewards, $result);
    }

    public function testFindAvailableRewardsIfNoMember(): void
    {
        $this->memberRepoMock->method('findByUserId')->with(1, 1)->willReturn([]);
        $result = $this->service->findAvailableRewards(1);
        $this->assertSame([], $result);
    }
}
