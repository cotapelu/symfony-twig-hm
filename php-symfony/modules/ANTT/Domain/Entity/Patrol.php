<?php

declare(strict_types=1);

namespace App\ANTT\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * ANTT Patrol Entity  
 * Represents a security patrol operation
 */
#[ORM\Entity]
#[ORM\Table(name: 'antt_patrols')]
#[ORM\Index(name: 'idx_patrol_status', columns: ['status'])]
#[ORM\Index(name: 'idx_patrol_date', columns: ['patrol_date'])]
#[ORM\Index(name: 'idx_patrol_team', columns: ['team_id'])]
class Patrol
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $patrolNumber;

    #[ORM\Column(name: 'team_id', type: 'string', length: 36)]
    private string $teamId;

    #[ORM\Column(name: 'team_name', type: 'string', length: 255)]
    private string $teamName;

    #[ORM\Column(name: 'team_leader_id', type: 'string', length: 36, nullable: true)]
    private ?string $teamLeaderId = null;

    #[ORM\Column(name: 'team_leader_name', type: 'string', length: 255, nullable: true)]
    private ?string $teamLeaderName = null;

    #[ORM\Column(name: 'patrol_date', type: 'datetime_immutable')]
    private DateTimeImmutable $patrolDate;

    #[ORM\Column(name: 'start_time', type: 'datetime_immutable')]
    private DateTimeImmutable $startTime;

    #[ORM\Column(name: 'end_time', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $endTime = null;

    #[ORM\Column(name: 'patrol_type', type: 'string', length: 50)]
    private string $patrolType;

    #[ORM\Column(name: 'patrol_route', type: 'text', nullable: true)]
    private ?string $patrolRoute = null;

    #[ORM\Column(name: 'area_covered', type: 'string', length: 500, nullable: true)]
    private ?string $areaCovered = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $checkpoints = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(name: 'total_members', type: 'integer', options: ['default' => 1])]
    private int $totalMembers = 1;

    #[ORM\Column(name: 'findings_summary', type: 'text', nullable: true)]
    private ?string $findingsSummary = null;

    #[ORM\Column(name: 'incidents_reported', type: 'integer', options: ['default' => 0])]
    private int $incidentsReported = 0;

    #[ORM\Column(name: 'violations_recorded', type: 'integer', options: ['default' => 0])]
    private int $violationsRecorded = 0;

    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'ward_code', type: 'string', length: 20, nullable: true)]
    private ?string $wardCode = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->patrolNumber = $this->generatePatrolNumber();
        $this->status = 'scheduled';
        $this->patrolDate = new DateTimeImmutable();
        $this->startTime = new DateTimeImmutable();
    }

    private function generatePatrolNumber(): string
    {
        return 'PT' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 5));
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatrolNumber(): string
    {
        return $this->patrolNumber;
    }

    public function getTeamId(): string
    {
        return $this->teamId;
    }

    public function getTeamName(): string
    {
        return $this->teamName;
    }

    public function getTeamLeaderId(): ?string
    {
        return $this->teamLeaderId;
    }

    public function getTeamLeaderName(): ?string
    {
        return $this->teamLeaderName;
    }

    public function getPatrolDate(): DateTimeImmutable
    {
        return $this->patrolDate;
    }

    public function getStartTime(): DateTimeImmutable
    {
        return $this->startTime;
    }

    public function getEndTime(): ?DateTimeImmutable
    {
        return $this->endTime;
    }

    public function getPatrolType(): string
    {
        return $this->patrolType;
    }

    public function getPatrolRoute(): ?string
    {
        return $this->patrolRoute;
    }

    public function getAreaCovered(): ?string
    {
        return $this->areaCovered;
    }

    public function getCheckpoints(): ?array
    {
        return $this->checkpoints;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotalMembers(): int
    {
        return $this->totalMembers;
    }

    public function getFindingsSummary(): ?string
    {
        return $this->findingsSummary;
    }

    public function getIncidentsReported(): int
    {
        return $this->incidentsReported;
    }

    public function getViolationsRecorded(): int
    {
        return $this->violationsRecorded;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getWardCode(): ?string
    {
        return $this->wardCode;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Setters
    public function setTeamName(string $teamName): self
    {
        $this->teamName = $teamName;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setTeamLeader(?string $leaderId, ?string $leaderName): self
    {
        $this->teamLeaderId = $leaderId;
        $this->teamLeaderName = $leaderName;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setPatrolDate(DateTimeImmutable $date): self
    {
        $this->patrolDate = $date;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setTime(DateTimeImmutable $start, ?DateTimeImmutable $end = null): self
    {
        $this->startTime = $start;
        $this->endTime = $end;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function startPatrol(): self
    {
        $this->status = 'in_progress';
        $this->startTime = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function completePatrol(string $findings): self
    {
        $this->status = 'completed';
        $this->endTime = new DateTimeImmutable();
        $this->findingsSummary = $findings;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function cancelPatrol(string $reason): self
    {
        $this->status = 'cancelled';
        $this->notes = $reason;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function recordIncident(): self
    {
        $this->incidentsReported++;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function recordViolation(): self
    {
        $this->violationsRecorded++;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setWardCode(?string $wardCode): self
    {
        $this->wardCode = $wardCode;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setTeamId(string $teamId): self
    {
        $this->teamId = $teamId;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setTeamLeaderId(?string $leaderId): self
    {
        $this->teamLeaderId = $leaderId;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setTeamLeaderName(?string $leaderName): self
    {
        $this->teamLeaderName = $leaderName;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setStartTime(DateTimeImmutable $time): self
    {
        $this->startTime = $time;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setEndTime(?DateTimeImmutable $time): self
    {
        $this->endTime = $time;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setPatrolType(string $type): self
    {
        $this->patrolType = $type;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setPatrolRoute(?string $route): self
    {
        $this->patrolRoute = $route;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setAreaCovered(?string $area): self
    {
        $this->areaCovered = $area;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setCheckpoints(?array $checkpoints): self
    {
        $this->checkpoints = $checkpoints;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setTotalMembers(int $total): self
    {
        $this->totalMembers = $total;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setFindingsSummary(?string $summary): self
    {
        $this->findingsSummary = $summary;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setIncidentsReported(int $count): self
    {
        $this->incidentsReported = $count;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setViolationsRecorded(int $count): self
    {
        $this->violationsRecorded = $count;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'patrol_number' => $this->patrolNumber,
            'team_id' => $this->teamId,
            'team_name' => $this->teamName,
            'team_leader' => [
                'id' => $this->teamLeaderId,
                'name' => $this->teamLeaderName,
            ],
            'patrol_date' => $this->patrolDate->format('Y-m-d'),
            'start_time' => $this->startTime->format('H:i:s'),
            'end_time' => $this->endTime?->format('H:i:s'),
            'patrol_type' => $this->patrolType,
            'route' => $this->patrolRoute,
            'area_covered' => $this->areaCovered,
            'status' => $this->status,
            'findings_summary' => $this->findingsSummary,
            'incidents_reported' => $this->incidentsReported,
            'violations_recorded' => $this->violationsRecorded,
            'ward_code' => $this->wardCode,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
