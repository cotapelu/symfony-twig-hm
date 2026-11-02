<?php declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Loyalty\Entity\LoyaltyProgram;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class LoyaltyProgramTest extends TestCase
{
    private function createProgram(array $overrides = []): LoyaltyProgram
    {
        $program = new LoyaltyProgram(
            name: 'Points Program',
            userId: 100,
            description: 'Earn points for purchases',
            programType: 'points'
        );
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . ucfirst($prop);
            if (method_exists($program, $setter)) {
                $program->$setter($value);
            }
        }
        return $program;
    }

    public function testConstructorInitializesProperties(): void
    {
        $program = $this->createProgram([]);
        $this->assertNull($program->getId());
        $this->assertSame('Points Program', $program->getName());
        $this->assertSame(100, $program->getUserId());
        $this->assertSame('Earn points for purchases', $program->getDescription());
        $this->assertSame('points', $program->getProgramType());
        $this->assertEmpty($program->getTierRules());
        $this->assertEmpty($program->getRewards());
        $this->assertTrue($program->isActive());
        $this->assertInstanceOf(\DateTime::class, $program->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $program->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $program = $this->createProgram([]);

        $program->setName('Rewards Program');
        $this->assertSame('Rewards Program', $program->getName());

        $program->setDescription('Dollar-based rewards');
        $this->assertSame('Dollar-based rewards', $program->getDescription());

        $program->setUserId(200);
        $this->assertSame(200, $program->getUserId());

        $program->setProgramType('tier');
        $this->assertSame('tier', $program->getProgramType());

        $tierRules = ['bronze' => ['minPoints' => 0], 'silver' => ['minPoints' => 1000]];
        $program->setTierRules($tierRules);
        $this->assertSame($tierRules, $program->getTierRules());

        $rewards = ['reward1' => ['points' => 1000]];
        $program->setRewards($rewards);
        $this->assertSame($rewards, $program->getRewards());

        $program->setActive(false);
        $this->assertFalse($program->isActive());
    }

    public function testUpdatedAtChangesOnMutation(): void
    {
        $program = $this->createProgram([]);
        $original = $program->getUpdatedAt();
        // Small sleep may not be needed; DateTime includes microseconds but setter creates new instance.
        $program->setName('New Name');
        $this->assertNotEquals($original, $program->getUpdatedAt());
    }
}
