<?php

declare(strict_types=1);

namespace Tests\Unit\Fitness\Controller;

use App\Fitness\Controller\FitnessController;
use App\Fitness\Service\FitnessService;
use App\Fitness\Entity\Workout;
use App\Fitness\Entity\Goal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class FitnessControllerTest extends TestCase
{
    private FitnessService $serviceMock;
    private FitnessController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(FitnessService::class);
        $this->controller = new FitnessController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createWorkoutMock(?int $id, string $userId, string $type = 'cardio', string $name = 'Workout', int $duration = 60, float $caloriesBurned = 200.0, ?float $avgHeartRate = null, ?array $exercises = null, ?\DateTime $workoutDate = null): Workout
    {
        $mock = $this->createMock(Workout::class);
        $mock->method('getId')->willReturn($id);
        $mock->method('getUserId')->willReturn($userId);
        $mock->method('getType')->willReturn($type);
        $mock->method('getName')->willReturn($name);
        $mock->method('getDuration')->willReturn($duration);
        $mock->method('getCaloriesBurned')->willReturn($caloriesBurned);
        $mock->method('getAvgHeartRate')->willReturn($avgHeartRate ?? 0);
        $mock->method('getExercises')->willReturn($exercises);
        $mock->method('getWorkoutDate')->willReturn($workoutDate ?? new \DateTime());
        return $mock;
    }

    private function createGoalMock(?int $id, string $userId, string $goalType = 'weight_loss', string $targetValue = '10', string $currentValue = '0', ?\DateTime $targetDate = null, string $status = 'active', bool $isCompleted = false): Goal
    {
        $mock = $this->createMock(Goal::class);
        $mock->method('getId')->willReturn($id);
        $mock->method('getUserId')->willReturn($userId);
        $mock->method('getGoalType')->willReturn($goalType);
        $mock->method('getTargetValue')->willReturn($targetValue);
        $mock->method('getCurrentValue')->willReturn($currentValue);
        $mock->method('getTargetDate')->willReturn($targetDate ?? new \DateTime('+30 days'));
        $mock->method('getStatus')->willReturn($status);
        $mock->method('isCompleted')->willReturn($isCompleted);
        return $mock;
    }

    // ==================== Workouts ====================

    public function testCreateWorkout_InvalidJson_Returns400(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->createWorkout($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateWorkout_MissingFields_Returns400(): void
    {
        $request = Request::create('/api/fitness/workouts', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['userId' => 'u1']));
        $response = $this->controller->createWorkout($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateWorkout_Success_Returns201(): void
    {
        $workout = $this->createWorkoutMock(1, 'u1');
        $this->serviceMock->expects($this->once())->method('createWorkout')->with(
            'u1', 'cardio', 'Workout', 60, 200.0, null, null, $this->anything()
        )->willReturn($workout);
        $payload = ['userId' => 'u1', 'type' => 'cardio', 'name' => 'Workout', 'duration' => 60, 'caloriesBurned' => 200];
        $request = Request::create('/api/fitness/workouts', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        $response = $this->controller->createWorkout($request);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('workoutId', $content['data']);
    }

    public function testGetWorkouts_MissingUserId_Returns400(): void
    {
        $request = new Request();
        $response = $this->controller->getWorkouts($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetWorkouts_Success_Returns200(): void
    {
        $workouts = [$this->createWorkoutMock(1, 'u1')];
        $this->serviceMock->expects($this->once())->method('getUserWorkouts')->with('u1', null, null)->willReturn($workouts);
        $request = Request::create('/api/fitness/workouts', 'GET', ['userId' => 'u1']);
        $response = $this->controller->getWorkouts($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetWorkout_InvalidId_Returns400(): void
    {
        $response = $this->controller->getWorkout(0);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetWorkout_NotFound_Returns404(): void
    {
        $this->serviceMock->method('getWorkout')->with(1)->willReturn(null);
        $response = $this->controller->getWorkout(1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetWorkout_Success_Returns200(): void
    {
        $workout = $this->createWorkoutMock(1, 'u1');
        $this->serviceMock->method('getWorkout')->with(1)->willReturn($workout);
        $response = $this->controller->getWorkout(1);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDeleteWorkout_InvalidId_Returns400(): void
    {
        $response = $this->controller->deleteWorkout(0);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testDeleteWorkout_NotFound_Returns404(): void
    {
        $this->serviceMock->method('deleteWorkout')->with(1)->willReturn(false);
        $response = $this->controller->deleteWorkout(1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDeleteWorkout_Success_Returns200(): void
    {
        $this->serviceMock->method('deleteWorkout')->with(1)->willReturn(true);
        $response = $this->controller->deleteWorkout(1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== Goals ====================

    public function testCreateGoal_InvalidJson_Returns400(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->createGoal($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateGoal_MissingFields_Returns400(): void
    {
        $request = Request::create('/api/fitness/goals', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['userId' => 'u1']));
        $response = $this->controller->createGoal($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateGoal_Success_Returns201(): void
    {
        $goal = $this->createGoalMock(1, 'u1');
        $this->serviceMock->expects($this->once())->method('createGoal')->with(
            'u1', 'weight_loss', '10', '0', $this->anything()
        )->willReturn($goal);
        $payload = ['userId' => 'u1', 'goalType' => 'weight_loss', 'targetValue' => '10'];
        $request = Request::create('/api/fitness/goals', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        $response = $this->controller->createGoal($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testGetGoals_MissingUserId_Returns400(): void
    {
        $request = new Request();
        $response = $this->controller->getGoals($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetGoals_Success_Returns200(): void
    {
        $goals = [$this->createGoalMock(1, 'u1')];
        $this->serviceMock->expects($this->once())->method('getUserGoals')->with('u1')->willReturn($goals);
        $request = Request::create('/api/fitness/goals', 'GET', ['userId' => 'u1']);
        $response = $this->controller->getGoals($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateGoalProgress_InvalidId_Returns400(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentValue' => '5']));
        $response = $this->controller->updateGoalProgress($request, 0);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateGoalProgress_InvalidCurrentValue_Returns400(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentValue' => 'abc']));
        $response = $this->controller->updateGoalProgress($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateGoalProgress_NotFound_Returns404(): void
    {
        $this->serviceMock->method('updateGoalProgress')->with(1, '5')->willReturn(null);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentValue' => '5']));
        $response = $this->controller->updateGoalProgress($request, 1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateGoalProgress_Success_Returns200(): void
    {
        $goal = $this->createGoalMock(1, 'u1', currentValue: '5', isCompleted: false);
        $this->serviceMock->method('updateGoalProgress')->with(1, '5')->willReturn($goal);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentValue' => '5']));
        $response = $this->controller->updateGoalProgress($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetStatsSummary_MissingUserId_Returns400(): void
    {
        $request = new Request();
        $response = $this->controller->getStatsSummary($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetStatsSummary_Success_Returns200(): void
    {
        $summary = ['totalWorkouts' => 5, 'totalDuration' => 300, 'totalCalories' => 500.0, 'workoutTypes' => []];
        $this->serviceMock->expects($this->once())->method('getUserStatsSummary')->with('u1', null, null)->willReturn($summary);
        $request = Request::create('/api/fitness/stats/summary', 'GET', ['userId' => 'u1']);
        $response = $this->controller->getStatsSummary($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals($summary, $content['data']);
    }
}
