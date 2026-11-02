<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use DateTimeImmutable;
use App\Government\Contracts\DTO\CreateRewardRequest;
use App\Government\Contracts\DTO\CreateWorkHistoryRequest;
use App\Government\Contracts\DTO\RewardResponse;
use App\Government\Contracts\DTO\WorkHistoryResponse;
use App\Government\Domain\Entity\Reward;
use App\Government\Domain\Entity\WorkHistory;
use App\Government\Domain\Repository\RewardRepositoryInterface;
use App\Government\Domain\Repository\WorkHistoryRepositoryInterface;
use App\Government\Domain\ValueObject\OfficerId;
use Ramsey\Uuid\Uuid;

class OfficerHistoryService
{
    public function __construct(
        private WorkHistoryRepositoryInterface $workHistoryRepository,
        private RewardRepositoryInterface $rewardRepository
    ) {
    }

    public function addWorkHistory(CreateWorkHistoryRequest $request): WorkHistoryResponse
    {
        $workHistory = new WorkHistory(
            id: Uuid::uuid4()->toString(),
            officerId: new OfficerId($request->officerId),
            title: $request->title,
            description: $request->description,
            startDate: new DateTimeImmutable($request->startDate),
            departmentCode: $request->departmentCode,
            positionCode: $request->positionCode
        );

        if ($request->endDate) {
            $workHistory->setEndDate(new DateTimeImmutable($request->endDate));
        }
        
        if ($request->notes) {
            $workHistory->setNotes($request->notes);
        }

        $this->workHistoryRepository->save($workHistory);

        return WorkHistoryResponse::fromEntity($workHistory);
    }

    public function getWorkHistoryByOfficer(string $officerId): array
    {
        $histories = $this->workHistoryRepository->findByOfficerId(new OfficerId($officerId));
        
        return array_map(
            fn(WorkHistory $history) => WorkHistoryResponse::fromEntity($history),
            $histories
        );
    }

    public function addReward(CreateRewardRequest $request): RewardResponse
    {
        $reward = new Reward(
            id: Uuid::uuid4()->toString(),
            officerId: new OfficerId($request->officerId),
            type: $request->type,
            title: $request->title,
            description: $request->description,
            date: new DateTimeImmutable($request->date),
            decisionNumber: $request->decisionNumber
        );

        if ($request->issuedBy) {
            $reward->setIssuedBy($request->issuedBy);
        }
        
        if ($request->level) {
            $reward->setLevel($request->level);
        }
        
        if ($request->notes) {
            $reward->setNotes($request->notes);
        }

        $this->rewardRepository->save($reward);

        return RewardResponse::fromEntity($reward);
    }

    public function getRewardsByOfficer(string $officerId, ?string $type = null): array
    {
        $rewards = $this->rewardRepository->findByOfficerId(new OfficerId($officerId), $type);
        
        return array_map(
            fn(Reward $reward) => RewardResponse::fromEntity($reward),
            $rewards
        );
    }

    public function getRewardStatistics(string $officerId): array
    {
        $rewardCount = $this->rewardRepository->countByOfficerAndType(
            new OfficerId($officerId), 
            Reward::TYPE_REWARD
        );
        
        $disciplineCount = $this->rewardRepository->countByOfficerAndType(
            new OfficerId($officerId), 
            Reward::TYPE_DISCIPLINE
        );

        return [
            'rewards' => $rewardCount,
            'disciplines' => $disciplineCount,
            'total' => $rewardCount + $disciplineCount
        ];
    }
}
