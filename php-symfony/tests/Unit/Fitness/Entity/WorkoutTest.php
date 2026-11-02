<?php declare(strict_types=1);

namespace App\Tests\Unit\Fitness\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Fitness\Entity\Workout;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WorkoutTest extends TestCase
{
    public function testConstructorSetsCreatedAt(): void
    {
        $workout = new Workout();
        $this->assertInstanceOf(\DateTime::class, $workout->getCreatedAt());
    }

    public function testGettersSetters(): void
    {
        $workout = new Workout();
        $workout->setUserId('user-123');
        $this->assertSame('user-123', $workout->getUserId());

        $workout->setType('running');
        $this->assertSame('running', $workout->getType());

        $workout->setName('Morning Run');
        $this->assertSame('Morning Run', $workout->getName());

        $workout->setDuration(30);
        $this->assertSame(30, $workout->getDuration());

        $workout->setCaloriesBurned(250.5);
        $this->assertSame(250.5, $workout->getCaloriesBurned());

        $workout->setAvgHeartRate(120.0);
        $this->assertSame(120.0, $workout->getAvgHeartRate());

        $exercises = ['pushups', 'squats'];
        $workout->setExercises($exercises);
        $this->assertSame($exercises, $workout->getExercises());

        $date = new \DateTime('2025-01-01 08:00:00');
        $workout->setWorkoutDate($date);
        $this->assertSame($date, $workout->getWorkoutDate());
    }

    public function testIdSetAndGet(): void
    {
        $workout = new Workout();
        $ref = new \ReflectionProperty($workout, 'id');
        $ref->setAccessible(true);
        $ref->setValue($workout, 10);
        $this->assertSame(10, $workout->getId());
    }
}
