<?php declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Loyalty\Entity\LoyaltyMember;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class LoyaltyMemberTest extends TestCase
{
    private function createMember(?int $userId = null, ?int $programId = null): LoyaltyMember
    {
        return new LoyaltyMember(
            userId: $userId ?? 100,
            programId: $programId ?? 1
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $member = $this->createMember();
        $this->assertNull($member->getId());
        $this->assertSame(100, $member->getUserId());
        $this->assertSame(1, $member->getProgramId());
        $this->assertSame('bronze', $member->getTier());
        $this->assertSame(0, $member->getPoints());
        $this->assertSame(0, $member->getLifetimePoints());
        $this->assertInstanceOf(\DateTime::class, $member->getJoinedAt());
        $this->assertNull($member->getLastActivityAt());
        $this->assertSame(0, $member->getVersion());
    }

    public function testAddPoints(): void
    {
        $member = $this->createMember();
        $before = $member->getLastActivityAt(); // should be null initially

        $member->addPoints(100);
        $this->assertSame(100, $member->getPoints());
        $this->assertSame(100, $member->getLifetimePoints());
        $this->assertInstanceOf(\DateTime::class, $member->getLastActivityAt());
        $this->assertNotEquals($before, $member->getLastActivityAt());

        $member->addPoints(50);
        $this->assertSame(150, $member->getPoints());
        $this->assertSame(150, $member->getLifetimePoints());
    }

    public function testDeductPoints(): void
    {
        $member = $this->createMember();
        $member->addPoints(200);
        $member->deductPoints(50);
        $this->assertSame(150, $member->getPoints());
        $this->assertSame(200, $member->getLifetimePoints()); // unchanged

        // Deduct more than available should floor at 0
        $member->deductPoints(500);
        $this->assertSame(0, $member->getPoints());
        $this->assertSame(200, $member->getLifetimePoints());
    }

    public function testSetTier(): void
    {
        $member = $this->createMember();
        $member->setTier('gold');
        $this->assertSame('gold', $member->getTier());
    }
}
