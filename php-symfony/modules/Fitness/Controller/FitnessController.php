<?php

declare(strict_types=1);

namespace App\Fitness\Controller;

use App\Fitness\Service\FitnessService;
use App\Fitness\Entity\Workout;
use App\Fitness\Entity\Goal;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/fitness')]
class FitnessController extends BaseController
{
    public function __construct(private FitnessService $fitnessService) {}

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['success'=>false, 'error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function validateRequiredFields(array $data, array $fields): void
    {
        foreach ($fields as $f) {
            if (!isset($data[$f]) || (is_string($data[$f]) && trim($data[$f]) === '')) {
                throw new InvalidArgumentException("Field '$f' is required");
            }
        }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("FitnessController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['success'=>false, 'error'=>'Internal server error'], 500);
    }

    private function logAction(string $action, array $ctx=[]): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$ctx]));
    }

    private function incrementMetric(string $m): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$m, 'value'=>1]));
    }

    // ==================== Endpoints ====================

    #[Route('/workouts', methods: ['POST'])]
    public function createWorkout(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createWorkoutAction($request));
    }

    private function createWorkoutAction(Request $request): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        $this->validateRequiredFields($data, ['userId','type','name','duration','caloriesBurned']);

        $workout = $this->fitnessService->createWorkout(
            userId: $data['userId'],
            type: $data['type'],
            name: $data['name'],
            duration: (int)$data['duration'],
            caloriesBurned: (float)$data['caloriesBurned'],
            avgHeartRate: $data['avgHeartRate'] ?? null,
            exercises: $data['exercises'] ?? null,
            workoutDate: isset($data['workoutDate']) ? new \DateTime($data['workoutDate']) : null,
        );
        $this->logAction('workout_created', ['workout_id'=>$workout->getId(),'user_id'=>$data['userId'],'type'=>$data['type']]); $this->incrementMetric('workouts_created');
        return $this->json(['success'=>true, 'data'=>['workoutId'=>$workout->getId(),'type'=>$workout->getType(),'name'=>$workout->getName()]], 201);
    }

    #[Route('/workouts', methods: ['GET'])]
    public function getWorkouts(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getWorkoutsAction($request));
    }

    private function getWorkoutsAction(Request $request): JsonResponse
    {
        $userId = $request->query->get('userId');
        if (!$userId) return $this->json(['success'=>false, 'error'=>'userId query parameter is required'], 400);
        $startDate = $request->query->get('startDate'); $endDate = $request->query->get('endDate');
        $workouts = $this->fitnessService->getUserWorkouts(
            userId: $userId,
            startDate: $startDate ? new \DateTime($startDate) : null,
            endDate: $endDate ? new \DateTime($endDate) : null,
        );
        return $this->json(['success'=>true, 'data'=>array_map(fn(Workout $w)=>['id'=>$w->getId(),'type'=>$w->getType(),'name'=>$w->getName(),'duration'=>$w->getDuration(),'caloriesBurned'=>$w->getCaloriesBurned(),'avgHeartRate'=>$w->getAvgHeartRate(),'workoutDate'=>$w->getWorkoutDate()->format('c')], $workouts)]);
    }

    #[Route('/workouts/{id}', methods: ['GET'])]
    public function getWorkout(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getWorkoutAction($id));
    }

    private function getWorkoutAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Workout ID must be a positive integer');
        $workout = $this->fitnessService->getWorkout($id);
        if (!$workout) return $this->json(['success'=>false, 'error'=>'Workout not found'], 404);
        return $this->json(['success'=>true, 'data'=>['id'=>$workout->getId(),'type'=>$workout->getType(),'name'=>$workout->getName(),'duration'=>$workout->getDuration(),'caloriesBurned'=>$workout->getCaloriesBurned(),'avgHeartRate'=>$workout->getAvgHeartRate(),'exercises'=>$workout->getExercises(),'workoutDate'=>$workout->getWorkoutDate()->format('c')]]);
    }

    #[Route('/workouts/{id}', methods: ['DELETE'])]
    public function deleteWorkout(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->deleteWorkoutAction($id));
    }

    private function deleteWorkoutAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Workout ID must be a positive integer');
        $result = $this->fitnessService->deleteWorkout($id);
        if (!$result) return $this->json(['success'=>false, 'error'=>'Workout not found'], 404);
        $this->logAction('workout_deleted', ['workout_id'=>$id]); $this->incrementMetric('workouts_deleted');
        return $this->json(['success'=>true, 'message'=>'Workout deleted successfully']);
    }

    #[Route('/goals', methods: ['POST'])]
    public function createGoal(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createGoalAction($request));
    }

    private function createGoalAction(Request $request): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        $this->validateRequiredFields($data, ['userId','goalType','targetValue']);
        $goal = $this->fitnessService->createGoal(
            userId: $data['userId'],
            goalType: $data['goalType'],
            targetValue: $data['targetValue'],
            currentValue: $data['currentValue'] ?? '0',
            targetDate: isset($data['targetDate']) ? new \DateTime($data['targetDate']) : null,
        );
        $this->logAction('goal_created', ['goal_id'=>$goal->getId(),'user_id'=>$data['userId'],'goal_type'=>$data['goalType']]); $this->incrementMetric('goals_created');
        return $this->json(['success'=>true, 'data'=>['goalId'=>$goal->getId(),'goalType'=>$goal->getGoalType(),'targetValue'=>$goal->getTargetValue()]], 201);
    }

    #[Route('/goals', methods: ['GET'])]
    public function getGoals(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getGoalsAction($request));
    }

    private function getGoalsAction(Request $request): JsonResponse
    {
        $userId = $request->query->get('userId');
        if (!$userId) return $this->json(['success'=>false, 'error'=>'userId query parameter is required'], 400);
        $goals = $this->fitnessService->getUserGoals($userId);
        return $this->json(['success'=>true, 'data'=>array_map(fn(Goal $g)=>['id'=>$g->getId(),'goalType'=>$g->getGoalType(),'targetValue'=>$g->getTargetValue(),'currentValue'=>$g->getCurrentValue(),'targetDate'=>$g->getTargetDate()->format('c'),'status'=>$g->getStatus(),'isCompleted'=>$g->isCompleted()], $goals)]);
    }

    #[Route('/goals/{id}/progress', methods: ['POST'])]
    public function updateGoalProgress(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->updateGoalProgressAction($request, $id));
    }

    private function updateGoalProgressAction(Request $request, int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Goal ID must be a positive integer');
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        if (!is_numeric($data['currentValue'])) throw new InvalidArgumentException('currentValue must be numeric');
        $goal = $this->fitnessService->updateGoalProgress($id, (string)$data['currentValue']);
        if (!$goal) return $this->json(['success'=>false, 'error'=>'Goal not found'], 404);
        $this->logAction('goal_progress_updated', ['goal_id'=>$id, 'current_value'=>$data['currentValue'], 'is_completed'=>$goal->isCompleted()]);
        return $this->json(['success'=>true, 'data'=>['goalId'=>$goal->getId(),'currentValue'=>$goal->getCurrentValue(),'isCompleted'=>$goal->isCompleted(),'status'=>$goal->getStatus()]]);
    }

    #[Route('/stats/summary', methods: ['GET'])]
    public function getStatsSummary(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getStatsSummaryAction($request));
    }

    private function getStatsSummaryAction(Request $request): JsonResponse
    {
        $userId = $request->query->get('userId');
        if (!$userId) return $this->json(['success'=>false, 'error'=>'userId query parameter is required'], 400);
        $startDate = $request->query->get('startDate'); $endDate = $request->query->get('endDate');
        $summary = $this->fitnessService->getUserStatsSummary(userId: $userId, startDate: $startDate ? new \DateTime($startDate) : null, endDate: $endDate ? new \DateTime($endDate) : null);
        return $this->json(['success'=>true, 'data'=>$summary]);
    }
}
