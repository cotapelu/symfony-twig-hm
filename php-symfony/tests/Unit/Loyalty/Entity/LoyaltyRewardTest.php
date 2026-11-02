<?php declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Loyalty\Entity\LoyaltyReward;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class LoyaltyRewardTest extends TestCase
{
    private function createReward(array $overrides = []): LoyaltyReward
    {
        $reward = new LoyaltyReward(
            programId: 1,
            name: 'Test Reward',
            pointsCost: 100,
            rewardType: 'discount'
        );
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . ucfirst($prop);
            if (method_exists($reward, $setter)) {
                $reward->$setter($value);
            }
        }
        return $reward;
    }

    public function testConstructorInitializesProperties(): void
    {
        $reward = $this->createReward([]);
        $this->assertNull($reward->getId());
        $this->assertSame(1, $reward->getProgramId());
        $this->assertSame('Test Reward', $reward->getName());
        $this->assertSame('', $reward->getDescription()); // default empty string
        $this->assertSame(100, $reward->getPointsCost());
        $this->assertSame('discount', $reward->getRewardType());
        $this->assertEmpty($reward->getDetails());
        $this->assertSame(-1, $reward->getStock());
        $this->assertTrue($reward->isActive());
    }

    public function testGettersAndSetters(): void
    {
        $reward = $this->createReward([]);

        $reward->setUserId(200);
        $this->assertSame(200, $reward->getUserId());

        $reward->setDescription('A nice reward');
        $this->assertSame('A nice reward', $reward->getDescription());

        $reward->setDetails(['code' => 'SAVE20']);
        $this->assertSame(['code' => 'SAVE20'], $reward->getDetails());

        $reward->setStock(50);
        $this->assertSame(50, $reward->getStock());

        $reward->setActive(false);
        $this->assertFalse($reward->isActive());
    }
}
