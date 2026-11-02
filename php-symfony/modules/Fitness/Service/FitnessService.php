<?php

declare(strict_types=1);

namespace App\Fitness\Service;

use App\Fitness\Entity\Workout;
use App\Fitness\Entity\Goal;
use App\Fitness\Repository\WorkoutRepository;
use App\Fitness\Repository\GoalRepository;

class FitnessService
{
    private const VALID_WORKOUT_TYPES = ['cardio', 'strength', 'flexibility', 'balance', 'hiit', 'yoga', 'swimming', 'cycling', 'running', 'walking', 'other'];
    private const VALID_GOAL_TYPES = ['weight_loss', 'muscle_gain', 'endurance', 'flexibility', 'steps', 'calories', 'distance', 'workouts_per_week'];
    private const MAX_DURATION = 1440; // 24 hours in minutes
    private const MAX_CALORIES = 10000;
    private const MAX_HEART_RATE = 300;

    public function __construct(
        private WorkoutRepository $workoutRepository,
        private GoalRepository $goalRepository,
    ) {
    }

    public function createWorkout(string $userId, string $type, string $name, int $duration, float $caloriesBurned, ?float $avgHeartRate = null, ?array $exercises = null, ?\DateTime $workoutDate = null): Workout
    {
        $this->validateUserId($userId); $this->validateWorkoutType($type); $this->validateWorkoutName($name); $this->validateDuration($duration); $this->validateCalories($caloriesBurned); if ($avgHeartRate !== null) $this->validateHeartRate($avgHeartRate);
        $workout = new Workout();
        $workout->setUserId($userId);
        $workout->setType($type);
        $workout->setName($name);
        $workout->setDuration($duration);
        $workout->setCaloriesBurned($caloriesBurned);
        $workout->setAvgHeartRate($avgHeartRate ?? 0);
        $workout->setExercises($exercises);
        $workout->setWorkoutDate($workoutDate ?? new \DateTime());
        $this->workoutRepository->save($workout);
        return $workout;
    }

    public function getUserWorkouts(string $userId, ?\DateTime $startDate = null, ?\DateTime $endDate = null)
    {
        $this->validateUserId($userId);
        
        if ($startDate && $endDate) {
            $this->validateDateRange($startDate, $endDate);
            return $this->workoutRepository->findByUserAndDateRange($userId, $startDate, $endDate);
        }
        return $this->workoutRepository->findByUser($userId);
    }

    public function getWorkout(int $id)
    {
        $this->validateId($id);
        return $this->workoutRepository->find($id);
    }

    public function deleteWorkout(int $id)
    {
        $this->validateId($id);
        $workout = $this->workoutRepository->find($id);
        if (!$workout) {
            return false;
        }
        $this->workoutRepository->delete($workout);
        return true;
    }

    public function createGoal(
        string $userId,
        string $goalType,
        string $targetValue,
        string $currentValue = '0',
        ?\DateTime $targetDate = null,
    ) {
        // Input validation
        $this->validateUserId($userId);
        $this->validateGoalType($goalType);
        $this->validateGoalValue($targetValue);
        $this->validateGoalValue($currentValue);

        $goal = new Goal();
        $goal->setUserId($userId);
        $goal->setGoalType($goalType);
        $goal->setTargetValue($targetValue);
        $goal->setCurrentValue($currentValue);
        $goal->setTargetDate($targetDate ?? new \DateTime('+30 days'));
        $goal->setStartDate(new \DateTime());

        $this->goalRepository->save($goal);
        return $goal;
    }

    public function getUserGoals(string $userId)
    {
        $this->validateUserId($userId);
        return $this->goalRepository->findByUser($userId);
    }

    public function updateGoalProgress(int $goalId, string $currentValue)
    {
        $this->validateId($goalId);
        $this->validateGoalValue($currentValue);

        $goal = $this->goalRepository->find($goalId);
        if (!$goal) {
            return null;
        }

        $goal->setCurrentValue($currentValue);
        
        // Check if goal is completed using proper comparison
        $current = (float) $currentValue;
        $target = (float) $goal->getTargetValue();
        
        if ($current >= $target && $goal->getStatus() !== 'completed') {
            $goal->setStatus('completed');
        }

        $this->goalRepository->save($goal);
        return $goal;
    }

    public function getUserStatsSummary(string $userId, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $this->validateUserId($userId);
        $workouts = $this->getUserWorkouts($userId, $startDate, $endDate);
        $totalWorkouts = count($workouts);
        $totalDuration = $totalCalories = 0; $workoutTypes = [];
        foreach ($workouts as $workout) {
            $totalDuration += $workout->getDuration();
            $totalCalories += $workout->getCaloriesBurned();
            $type = $workout->getType();
            if (!isset($workoutTypes[$type])) {
                $workoutTypes[$type] = ['count'=>0, 'duration'=>0, 'calories'=>0];
            }
            $workoutTypes[$type]['count']++;
            $workoutTypes[$type]['duration'] += $workout->getDuration();
            $workoutTypes[$type]['calories'] += $workout->getCaloriesBurned();
        }
        return [
            'totalWorkouts' => $totalWorkouts,
            'totalDuration' => $totalDuration,
            'totalCalories' => round($totalCalories, 2),
            'workoutTypes' => $workoutTypes,
        ];
    }

    // ========== VALIDATION METHODS ==========

    private function validateUserId(string $userId)
    {
        if (empty(trim($userId))) {
            throw new \InvalidArgumentException('User ID cannot be empty');
        }
        if (strlen($userId) > 36) {
            throw new \InvalidArgumentException('User ID cannot exceed 36 characters');
        }
    }

    private function validateWorkoutType(string $type)
    {
        if (empty(trim($type))) {
            throw new \InvalidArgumentException('Workout type cannot be empty');
        }
        if (!in_array($type, self::VALID_WORKOUT_TYPES, true)) {
            throw new \InvalidArgumentException(
                'Invalid workout type. Valid types: ' . implode(', ', self::VALID_WORKOUT_TYPES)
            );
        }
    }

    private function validateWorkoutName(string $name)
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Workout name cannot be empty');
        }
        if (strlen($name) > 100) {
            throw new \InvalidArgumentException('Workout name cannot exceed 100 characters');
        }
    }

    private function validateDuration(int $duration)
    {
        if ($duration <= 0) {
            throw new \InvalidArgumentException('Duration must be greater than 0');
        }
        if ($duration > self::MAX_DURATION) {
            throw new \InvalidArgumentException('Duration cannot exceed ' . self::MAX_DURATION . ' minutes');
        }
    }

    private function validateCalories(float $calories)
    {
        if ($calories < 0) {
            throw new \InvalidArgumentException('Calories burned cannot be negative');
        }
        if ($calories > self::MAX_CALORIES) {
            throw new \InvalidArgumentException('Calories burned cannot exceed ' . self::MAX_CALORIES);
        }
    }

    private function validateHeartRate(float $heartRate)
    {
        if ($heartRate < 0) {
            throw new \InvalidArgumentException('Heart rate cannot be negative');
        }
        if ($heartRate > self::MAX_HEART_RATE) {
            throw new \InvalidArgumentException('Heart rate cannot exceed ' . self::MAX_HEART_RATE);
        }
    }

    private function validateGoalType(string $goalType)
    {
        if (empty(trim($goalType))) {
            throw new \InvalidArgumentException('Goal type cannot be empty');
        }
        if (!in_array($goalType, self::VALID_GOAL_TYPES, true)) {
            throw new \InvalidArgumentException(
                'Invalid goal type. Valid types: ' . implode(', ', self::VALID_GOAL_TYPES)
            );
        }
    }

    private function validateGoalValue(string $value)
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException('Goal value must be numeric');
        }
        if ((float) $value < 0) {
            throw new \InvalidArgumentException('Goal value cannot be negative');
        }
    }

    private function validateId(int $id)
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('ID must be a positive integer');
        }
    }

    private function validateDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date cannot be after end date');
        }
    }
}
