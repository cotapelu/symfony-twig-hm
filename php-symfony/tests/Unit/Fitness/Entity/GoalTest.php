<?php declare(strict_types=1);

namespace App\Tests\Unit\Fitness\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Fitness\Entity\Goal;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GoalTest extends TestCase
{
    public function testConstructorSetsCreatedAt(): void
    {
        $goal = new Goal();
        $this->assertInstanceOf(\DateTime::class, $goal->getCreatedAt());
        $this->assertSame('in_progress', $goal->getStatus());
    }

    public function testGettersSetters(): void
    {
        $goal = new Goal();
        $goal->setUserId('user-456');
        $this->assertSame('user-456', $goal->getUserId());

        $goal->setGoalType('weight_loss');
        $this->assertSame('weight_loss', $goal->getGoalType());

        $goal->setTargetValue('10 kg');
        $this->assertSame('10 kg', $goal->getTargetValue());

        $goal->setCurrentValue('3 kg');
        $this->assertSame('3 kg', $goal->getCurrentValue());

        $start = new \DateTime('2025-01-01');
        $goal->setStartDate($start);
        $this->assertSame($start, $goal->getStartDate());

        $target = new \DateTime('2025-06-30');
        $goal->setTargetDate($target);
        $this->assertSame($target, $goal->getTargetDate());

        $goal->setStatus('completed');
        $this->assertSame('completed', $goal->getStatus());
    }

    public function testIsCompleted(): void
    {
        $goal = new Goal();
        $this->assertFalse($goal->isCompleted());
        $goal->setStatus('completed');
        $this->assertTrue($goal->isCompleted());
    }

    public function testIdSetAndGet(): void
    {
        $goal = new Goal();
        $ref = new \ReflectionProperty($goal, 'id');
        $ref->setAccessible(true);
        $ref->setValue($goal, 99);
        $this->assertSame(99, $goal->getId());
    }
}
