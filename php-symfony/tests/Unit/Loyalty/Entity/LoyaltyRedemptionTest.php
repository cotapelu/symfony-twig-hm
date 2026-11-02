<?php declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Loyalty\Entity\LoyaltyRedemption;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class LoyaltyRedemptionTest extends TestCase
{
    private function createRedemption(?int $memberId = null, ?int $rewardId = null, ?int $pointsSpent = null): LoyaltyRedemption
    {
        return new LoyaltyRedemption(
            memberId: $memberId ?? 1,
            rewardId: $rewardId ?? 1,
            pointsSpent: $pointsSpent ?? 100
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $redemption = $this->createRedemption();
        $this->assertNull($redemption->getId());
        $this->assertSame(1, $redemption->getMemberId());
        $this->assertSame(1, $redemption->getRewardId());
        $this->assertSame(100, $redemption->getPointsSpent());
        $this->assertSame('pending', $redemption->getStatus());
        $this->assertNull($redemption->getCode());
        $this->assertInstanceOf(\DateTime::class, $redemption->getRedeemedAt());
        $this->assertNull($redemption->getExpiresAt());
        $this->assertNull($redemption->getUsedAt());
    }

    public function testSettersAndStateChanges(): void
    {
        $redemption = $this->createRedemption();

        $redemption->setStatus('approved');
        $this->assertSame('approved', $redemption->getStatus());

        $redemption->setCode('ABC123');
        $this->assertSame('ABC123', $redemption->getCode());

        $expires = new \DateTime('+30 days');
        $redemption->setExpiresAt($expires);
        $this->assertSame($expires, $redemption->getExpiresAt());

        $redemption->markAsUsed();
        $this->assertInstanceOf(\DateTime::class, $redemption->getUsedAt());
        $this->assertSame('used', $redemption->getStatus());
    }
}
