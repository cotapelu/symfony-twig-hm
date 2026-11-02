<?php declare(strict_types=1);

namespace App\Tests\Unit\Fitness\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Fitness\Entity\Workout;
use App\Fitness\Entity\Goal;
use App\Fitness\Repository\WorkoutRepository;
use App\Fitness\Repository\GoalRepository;
use App\Fitness\Service\FitnessService;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class FitnessServiceTest extends TestCase
{
    private WorkoutRepository $workoutRepoMock;
    private GoalRepository $goalRepoMock;
    private FitnessService $service;

    protected function setUp(): void
    {
        $this->workoutRepoMock = $this->createMock(WorkoutRepository::class);
        $this->goalRepoMock = $this->createMock(GoalRepository::class);
        $this->service = new FitnessService(
            $this->workoutRepoMock,
            $this->goalRepoMock
        );
    }

    // ============ HELPERS ============

    private function createWorkoutEntity(array $data = []): Workout
    {
        $workout = new Workout();
        $workout->setUserId($data['userId'] ?? 'user-1');
        $workout->setType($data['type'] ?? 'cardio');
        $workout->setName($data['name'] ?? 'Morning Run');
        $workout->setDuration($data['duration'] ?? 30);
        $workout->setCaloriesBurned($data['calories'] ?? 200.0);
        $workout->setAvgHeartRate($data['heartRate'] ?? 120.0);
        $workout->setExercises($data['exercises'] ?? ['push-ups', 'squats']);
        $workout->setWorkoutDate($data['date'] ?? new \DateTime());

        if (isset($data['id'])) {
            $ref = new \ReflectionClass($workout);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($workout, $data['id']);
        }
        return $workout;
    }

    private function createGoalEntity(array $data = []): Goal
    {
        $goal = new Goal();
        $goal->setUserId($data['userId'] ?? 'user-1');
        $goal->setGoalType($data['type'] ?? 'weight_loss');
        $goal->setTargetValue($data['target'] ?? '10');
        $goal->setCurrentValue($data['current'] ?? '0');
        $goal->setTargetDate($data['targetDate'] ?? new \DateTime('+30 days'));
        $goal->setStartDate($data['startDate'] ?? new \DateTime());

        if (isset($data['id'])) {
            $ref = new \ReflectionClass($goal);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($goal, $data['id']);
        }
        return $goal;
    }

    // ============ CREATE WORKOUT ============

    public function testCreateWorkoutSuccess(): void
    {
        $this->workoutRepoMock->expects($this->once())->method('save')->with($this->isInstanceOf(Workout::class));

        $result = $this->service->createWorkout(
            userId: 'user-1',
            type: 'cardio',
            name: 'Morning Run',
            duration: 30,
            caloriesBurned: 200.5,
            avgHeartRate: 120.0,
            exercises: ['push-ups'],
            workoutDate: new \DateTime('2025-01-01')
        );

        $this->assertInstanceOf(Workout::class, $result);
        $this->assertSame('user-1', $result->getUserId());
        $this->assertSame('cardio', $result->getType());
        $this->assertSame(30, $result->getDuration());
        $this->assertSame(200.5, $result->getCaloriesBurned());
        $this->assertSame('push-ups', $result->getExercises()[0]);
    }

    public function testCreateWorkoutWithDefaults(): void
    {
        $this->workoutRepoMock->expects($this->once())->method('save');

        $result = $this->service->createWorkout(
            userId: 'user-2',
            type: 'yoga',
            name: 'Yoga Session',
            duration: 60,
            caloriesBurned: 150.0
            // avgHeartRate, exercises, workoutDate omitted
        );

        $this->assertSame(0.0, $result->getAvgHeartRate()); // default
    }

    // Validation tests for createWorkout

    public function testCreateWorkoutThrowsIfEmptyUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID cannot be empty');
        $this->service->createWorkout('', 'cardio', 'Run', 30, 200.0);
    }

    public function testCreateWorkoutThrowsIfUserIdTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID cannot exceed 36 characters');
        $this->service->createWorkout(str_repeat('a', 37), 'cardio', 'Run', 30, 200.0);
    }

    public function testCreateWorkoutThrowsIfEmptyType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Workout type cannot be empty');
        $this->service->createWorkout('user-1', '', 'Run', 30, 200.0);
    }

    public function testCreateWorkoutThrowsIfInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid workout type');
        $this->service->createWorkout('user-1', 'invalid', 'Run', 30, 200.0);
    }

    public function testCreateWorkoutThrowsIfEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Workout name cannot be empty');
        $this->service->createWorkout('user-1', 'cardio', '', 30, 200.0);
    }

    public function testCreateWorkoutThrowsIfNameTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Workout name cannot exceed 100 characters');
        $this->service->createWorkout('user-1', 'cardio', str_repeat('a', 101), 30, 200.0);
    }

    public function testCreateWorkoutThrowsIfDurationZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration must be greater than 0');
        $this->service->createWorkout('user-1', 'cardio', 'Run', 0, 200.0);
    }

    public function testCreateWorkoutThrowsIfDurationTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration cannot exceed 1440 minutes');
        $this->service->createWorkout('user-1', 'cardio', 'Run', 1441, 200.0);
    }

    public function testCreateWorkoutThrowsIfCaloriesNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Calories burned cannot be negative');
        $this->service->createWorkout('user-1', 'cardio', 'Run', 30, -10.0);
    }

    public function testCreateWorkoutThrowsIfCaloriesTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Calories burned cannot exceed 10000');
        $this->service->createWorkout('user-1', 'cardio', 'Run', 30, 10001.0);
    }

    public function testCreateWorkoutThrowsIfHeartRateNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Heart rate cannot be negative');
        $this->service->createWorkout('user-1', 'cardio', 'Run', 30, 200.0, -10);
    }

    public function testCreateWorkoutThrowsIfHeartRateTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Heart rate cannot exceed 300');
        $this->service->createWorkout('user-1', 'cardio', 'Run', 30, 200.0, 301);
    }

    // ============ GET USER WORKOUTS ============

    public function testGetUserWorkoutsSuccess(): void
    {
        $workouts = [$this->createWorkoutEntity(), $this->createWorkoutEntity()];
        $this->workoutRepoMock->method('findByUser')->with('user-1')->willReturn($workouts);

        $result = $this->service->getUserWorkouts('user-1');
        $this->assertSame($workouts, $result);
    }

    public function testGetUserWorkoutsWithDateRange(): void
    {
        $start = new \DateTime('2025-01-01');
        $end = new \DateTime('2025-01-31');
        $workouts = [$this->createWorkoutEntity()];
        $this->workoutRepoMock->expects($this->once())->method('findByUserAndDateRange')->with('user-1', $start, $end)->willReturn($workouts);

        $result = $this->service->getUserWorkouts('user-1', $start, $end);
        $this->assertSame($workouts, $result);
    }

    public function testGetUserWorkoutsThrowsIfInvalidDateRange(): void
    {
        $start = new \DateTime('2025-02-01');
        $end = new \DateTime('2025-01-01');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Start date cannot be after end date');
        $this->service->getUserWorkouts('user-1', $start, $end);
    }

    public function testGetUserWorkoutsThrowsIfEmptyUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getUserWorkouts('');
    }

    // ============ GET WORKOUT ============

    public function testGetWorkoutSuccess(): void
    {
        $workout = $this->createWorkoutEntity(['id' => 1]);
        $this->workoutRepoMock->method('find')->with(1)->willReturn($workout);

        $result = $this->service->getWorkout(1);
        $this->assertSame($workout, $result);
    }

    public function testGetWorkoutThrowsIfInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getWorkout(0);
    }

    // ============ DELETE WORKOUT ============

    public function testDeleteWorkoutSuccess(): void
    {
        $workout = $this->createWorkoutEntity(['id' => 1]);
        $this->workoutRepoMock->method('find')->with(1)->willReturn($workout);
        $this->workoutRepoMock->expects($this->once())->method('delete')->with($workout);

        $result = $this->service->deleteWorkout(1);
        $this->assertTrue($result);
    }

    public function testDeleteWorkoutReturnsFalseIfNotFound(): void
    {
        $this->workoutRepoMock->method('find')->with(999)->willReturn(null);
        $result = $this->service->deleteWorkout(999);
        $this->assertFalse($result);
    }

    public function testDeleteWorkoutThrowsIfInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->deleteWorkout(-1);
    }

    // ============ CREATE GOAL ============

    public function testCreateGoalSuccess(): void
    {
        $this->goalRepoMock->expects($this->once())->method('save')->with($this->isInstanceOf(Goal::class));

        $result = $this->service->createGoal(
            userId: 'user-1',
            goalType: 'weight_loss',
            targetValue: '10',
            currentValue: '2',
            targetDate: new \DateTime('+60 days')
        );

        $this->assertInstanceOf(Goal::class, $result);
        $this->assertSame('user-1', $result->getUserId());
        $this->assertSame('weight_loss', $result->getGoalType());
        $this->assertSame('10', $result->getTargetValue());
        $this->assertSame('2', $result->getCurrentValue());
    }

    public function testCreateGoalWithDefaults(): void
    {
        $this->goalRepoMock->expects($this->once())->method('save');
        $result = $this->service->createGoal(
            userId: 'user-2',
            goalType: 'endurance',
            targetValue: '5'
        );
        $this->assertSame('0', $result->getCurrentValue());
        $this->assertNotNull($result->getTargetDate());
    }

    // Validation tests for createGoal

    public function testCreateGoalThrowsIfEmptyUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createGoal('', 'weight_loss', '10');
    }

    public function testCreateGoalThrowsIfInvalidGoalType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createGoal('user-1', 'invalid', '10');
    }

    public function testCreateGoalThrowsIfNonNumericTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createGoal('user-1', 'weight_loss', 'ten');
    }

    public function testCreateGoalThrowsIfNegativeTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createGoal('user-1', 'weight_loss', '-5');
    }

    public function testCreateGoalThrowsIfNonNumericCurrent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createGoal('user-1', 'weight_loss', '10', 'five');
    }

    public function testCreateGoalThrowsIfNegativeCurrent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createGoal('user-1', 'weight_loss', '10', '-1');
    }

    // ============ GET USER GOALS ============

    public function testGetUserGoalsSuccess(): void
    {
        $goals = [$this->createGoalEntity(), $this->createGoalEntity()];
        $this->goalRepoMock->method('findByUser')->with('user-1')->willReturn($goals);

        $result = $this->service->getUserGoals('user-1');
        $this->assertSame($goals, $result);
    }

    public function testGetUserGoalsThrowsIfEmptyUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getUserGoals('');
    }

    // ============ UPDATE GOAL PROGRESS ============

    public function testUpdateGoalProgressSuccess(): void
    {
        $goal = $this->createGoalEntity(['id' => 1, 'target' => '10', 'current' => '2', 'status' => 'in_progress']);
        $this->goalRepoMock->method('find')->with(1)->willReturn($goal);
        $this->goalRepoMock->expects($this->once())->method('save')->with($goal);

        $result = $this->service->updateGoalProgress(1, '12');

        $this->assertSame($goal, $result);
        $this->assertSame('12', $result->getCurrentValue());
        $this->assertSame('completed', $result->getStatus());
        $this->assertTrue($result->isCompleted());
    }

    public function testUpdateGoalProgressDoesNotCompleteIfBelowTarget(): void
    {
        $goal = $this->createGoalEntity(['id' => 1, 'target' => '10', 'current' => '2', 'status' => 'in_progress']);
        $this->goalRepoMock->method('find')->with(1)->willReturn($goal);
        $this->goalRepoMock->expects($this->once())->method('save');

        $result = $this->service->updateGoalProgress(1, '5');

        $this->assertSame('5', $result->getCurrentValue());
        $this->assertSame('in_progress', $result->getStatus());
        $this->assertFalse($result->isCompleted());
    }

    public function testUpdateGoalProgressAlreadyCompleted(): void
    {
        $goal = $this->createGoalEntity(['id' => 1, 'target' => '10', 'current' => '12', 'status' => 'completed']);
        $this->goalRepoMock->method('find')->with(1)->willReturn($goal);
        $this->goalRepoMock->expects($this->once())->method('save');

        $result = $this->service->updateGoalProgress(1, '15');

        $this->assertSame('15', $result->getCurrentValue());
        $this->assertSame('completed', $result->getStatus());
    }

    public function testUpdateGoalProgressReturnsNullIfNotFound(): void
    {
        $this->goalRepoMock->method('find')->with(999)->willReturn(null);
        $result = $this->service->updateGoalProgress(999, '5');
        $this->assertNull($result);
    }

    public function testUpdateGoalProgressThrowsIfInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->updateGoalProgress(0, '5');
    }

    public function testUpdateGoalProgressThrowsIfNonNumericCurrent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->updateGoalProgress(1, 'abc');
    }

    public function testUpdateGoalProgressThrowsIfNegativeCurrent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->updateGoalProgress(1, '-5');
    }

    // ============ GET USER STATS SUMMARY ============

    public function testGetUserStatsSummary(): void
    {
        $now = new \DateTime();
        $w1 = $this->createWorkoutEntity([
            'id' => 1,
            'type' => 'cardio',
            'duration' => 30,
            'calories' => 200.0,
            'date' => $now,
        ]);
        $w2 = $this->createWorkoutEntity([
            'id' => 2,
            'type' => 'cardio',
            'duration' => 20,
            'calories' => 150.0,
            'date' => $now,
        ]);
        $w3 = $this->createWorkoutEntity([
            'id' => 3,
            'type' => 'strength',
            'duration' => 45,
            'calories' => 250.0,
            'date' => $now,
        ]);

        $this->workoutRepoMock->method('findByUser')->with('user-1')->willReturn([$w1, $w2, $w3]);

        $summary = $this->service->getUserStatsSummary('user-1');

        $this->assertSame(3, $summary['totalWorkouts']);
        $this->assertSame(95, $summary['totalDuration']);
        $this->assertSame(600.0, $summary['totalCalories']);
        $this->assertCount(2, $summary['workoutTypes']);
        $this->assertSame(2, $summary['workoutTypes']['cardio']['count']);
        $this->assertSame(50, $summary['workoutTypes']['cardio']['duration']);
        $this->assertSame(350.0, $summary['workoutTypes']['cardio']['calories']);
        $this->assertSame(1, $summary['workoutTypes']['strength']['count']);
        $this->assertSame(45, $summary['workoutTypes']['strength']['duration']);
        $this->assertSame(250.0, $summary['workoutTypes']['strength']['calories']);
    }

    public function testGetUserStatsSummaryEmpty(): void
    {
        $this->workoutRepoMock->method('findByUser')->with('user-1')->willReturn([]);
        $summary = $this->service->getUserStatsSummary('user-1');
        $this->assertSame(0, $summary['totalWorkouts']);
        $this->assertSame(0, $summary['totalDuration']);
        $this->assertSame(0.0, $summary['totalCalories']);
        $this->assertEmpty($summary['workoutTypes']);
    }
}
