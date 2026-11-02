<?php declare(strict_types=1);
namespace App\Fitness\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Fitness\Repository\WorkoutRepository::class)]
#[ORM\Table(name: "fitness_workouts")]
class Workout {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    /** @readonly Set by Doctrine ORM */
    private int $id;
    #[ORM\Column(type: "string", length: 36)] private string $userId;
    #[ORM\Column(type: "string", length: 50)] private string $type;
    #[ORM\Column(type: "string", length: 100)] private string $name;
    #[ORM\Column(type: "integer")] private int $duration;
    #[ORM\Column(type: "decimal", precision: 5, scale: 2)] private float $caloriesBurned;
    #[ORM\Column(type: "decimal", precision: 5, scale: 2)] private float $avgHeartRate;
    #[ORM\Column(type: "json", nullable: true)] private ?array $exercises = null;
    #[ORM\Column(type: "datetime")] private \DateTime $workoutDate;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    public function __construct() { $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId() { return $this->userId; }
    public function setUserId(string $userId) { $this->userId = $userId; return $this; }
    public function getType() { return $this->type; }
    public function setType(string $type) { $this->type = $type; return $this; }
    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getDuration() { return $this->duration; }
    public function setDuration(int $duration) { $this->duration = $duration; return $this; }
    public function getCaloriesBurned() { return $this->caloriesBurned; }
    public function setCaloriesBurned(float $caloriesBurned) { $this->caloriesBurned = $caloriesBurned; return $this; }
    public function getAvgHeartRate() { return $this->avgHeartRate; }
    public function setAvgHeartRate(float $avgHeartRate) { $this->avgHeartRate = $avgHeartRate; return $this; }
    public function getExercises() { return $this->exercises; }
    public function setExercises(?array $exercises) { $this->exercises = $exercises; return $this; }
    public function getWorkoutDate() { return $this->workoutDate; }
    public function setWorkoutDate(\DateTime $workoutDate) { $this->workoutDate = $workoutDate; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
}

#[ORM\Entity(repositoryClass: \App\Fitness\Repository\GoalRepository::class)]
#[ORM\Table(name: "fitness_goals")]
class Goal {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    /** @readonly Set by Doctrine ORM */
    private int $id;
    #[ORM\Column(type: "string", length: 36)] private string $userId;
    #[ORM\Column(type: "string", length: 50)] private string $goalType;
    #[ORM\Column(type: "string", length: 50)] private string $targetValue;
    #[ORM\Column(type: "string", length: 50)] private string $currentValue;
    #[ORM\Column(type: "datetime")] private \DateTime $startDate;
    #[ORM\Column(type: "datetime")] private \DateTime $targetDate;
    #[ORM\Column(type: "string", length: 20)] private string $status = 'in_progress';
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    public function __construct() { $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId() { return $this->userId; }
    public function setUserId(string $userId) { $this->userId = $userId; return $this; }
    public function getGoalType() { return $this->goalType; }
    public function setGoalType(string $goalType) { $this->goalType = $goalType; return $this; }
    public function getTargetValue() { return $this->targetValue; }
    public function setTargetValue(string $targetValue) { $this->targetValue = $targetValue; return $this; }
    public function getCurrentValue() { return $this->currentValue; }
    public function setCurrentValue(string $currentValue) { $this->currentValue = $currentValue; return $this; }
    public function getStartDate() { return $this->startDate; }
    public function setStartDate(\DateTime $startDate) { $this->startDate = $startDate; return $this; }
    public function getTargetDate() { return $this->targetDate; }
    public function setTargetDate(\DateTime $targetDate) { $this->targetDate = $targetDate; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function isCompleted() { return $this->status === 'completed'; }
}
