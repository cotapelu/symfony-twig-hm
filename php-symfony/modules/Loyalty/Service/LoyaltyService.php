<?php

declare(strict_types=1);

namespace App\Loyalty\Service;

use App\Loyalty\Entity\LoyaltyProgram;
use App\Loyalty\Entity\LoyaltyMember;
use App\Loyalty\Entity\LoyaltyReward;
use App\Loyalty\Entity\LoyaltyRedemption;
use App\Loyalty\Repository\LoyaltyProgramRepository;
use App\Loyalty\Repository\LoyaltyMemberRepository;
use App\Loyalty\Repository\LoyaltyRewardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

class LoyaltyService
{
    private LoyaltyProgramRepository $programRepository;
    private LoyaltyMemberRepository $memberRepository;
    private LoyaltyRewardRepository $rewardRepository;
    private EntityManagerInterface $em;
    private const MAX_RETRIES = 3;

    public function __construct(
        LoyaltyProgramRepository $programRepository,
        LoyaltyMemberRepository $memberRepository,
        LoyaltyRewardRepository $rewardRepository,
        EntityManagerInterface $em
    ) {
        $this->programRepository = $programRepository;
        $this->memberRepository = $memberRepository;
        $this->rewardRepository = $rewardRepository;
        $this->em = $em;
    }

    // ========== PROGRAMS ==========

    public function createProgram(
        int $userId,
        string $name,
        string $description,
        string $programType = 'standard',
        array $tierRules = [],
        array $rewards = []
    ) {
        $this->validateProgramCreation($name, $description, $programType);
        $program = $this->buildProgram($userId, $name, $description, $programType, $tierRules, $rewards);
        $this->programRepository->save($program);
        return $program;
    }

    public function isProgramOwner(int $programId, int $userId)
    {
        $program = $this->getProgram($programId);
        return $program && $program->getUserId() === $userId;
    }

    public function getProgram(int $id)
    {
        return $this->programRepository->findById($id);
    }

    public function getAllPrograms(int $limit = 50, int $offset = 0)
    {
        return $this->programRepository->findAll($limit, $offset);
    }

    public function countAllPrograms()
    {
        return $this->programRepository->countAll();
    }

    public function getActivePrograms(int $limit = 50, int $offset = 0)
    {
        return $this->programRepository->findByActive($limit, $offset);
    }

    public function countActivePrograms()
    {
        return $this->programRepository->countActive();
    }

    public function updateProgram(int $id, array $data)
    {
        $this->validateProgramUpdate($id, $data);
        $program = $this->getProgram($id);
        if (!$program) {
            return null;
        }
        $this->applyProgramUpdate($program, $data);
        $this->programRepository->save($program);
        return $program;
    }

    public function deleteProgram(int $id)
    {
        $program = $this->getProgram($id);
        if (!$program) {
            return false;
        }
        $this->em->remove($program);
        $this->em->flush();
        return true;
    }

    // ========== MEMBERS ==========

    public function createMember(int $userId, int $programId)
    {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }
        if ($programId <= 0) {
            throw new \InvalidArgumentException('Program ID must be positive');
        }

        $program = $this->getProgram($programId);
        if (!$program) {
            throw new \InvalidArgumentException('Program not found');
        }

        $existing = $this->memberRepository->findByUserAndProgram($userId, $programId);
        if ($existing) {
            throw new \InvalidArgumentException('User already enrolled in this program');
        }

        $member = new LoyaltyMember($userId, $programId);
        $this->memberRepository->save($member);
        return $member;
    }

    public function enrollMember(int $userId, int $programId)
    {
        return $this->createMember($userId, $programId);
    }

    public function getMember(int $id)
    {
        return $this->memberRepository->findById($id);
    }

    public function getMemberByUser(int $userId, int $limit = 50, int $offset = 0)
    {
        return $this->memberRepository->findByUserId($userId, $limit, $offset);
    }

    public function countMembersByUser(int $userId)
    {
        return $this->memberRepository->countByUserId($userId);
    }

    public function getMembersByProgram(int $programId, int $limit = 50, int $offset = 0)
    {
        return $this->memberRepository->findByProgram($programId, $limit, $offset);
    }

    public function addPoints(int $memberId, int $points)
    {
        $this->validatePoints($points);
        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            try {
                $this->em->beginTransaction();
                if (!($member = $this->memberRepository->find($memberId))) { $this->em->rollback(); return null; }
                $member->addPoints($points);
                $this->em->flush(); $this->em->commit();
                return $member;
            } catch (OptimisticLockException $e) {
                $this->em->rollback();
                if ($attempt === self::MAX_RETRIES - 1) throw new \Exception('Failed to add points due to concurrent updates. Please try again.', 0, $e);
                usleep(10000 * ($attempt + 1));
            } catch (\Exception $e) {
                $this->em->rollback();
                throw $e;
            }
        }
        return null;
    }

    public function deductPoints(int $memberId, int $points)
    {
        $this->validatePoints($points);
        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            try {
                $this->em->beginTransaction();
                if (!($member = $this->memberRepository->find($memberId))) { $this->em->rollback(); return null; }
                if ($member->getPoints() < $points) throw new \InvalidArgumentException('Insufficient points');
                $member->deductPoints($points);
                $this->em->flush(); $this->em->commit();
                return $member;
            } catch (OptimisticLockException $e) {
                $this->em->rollback();
                if ($attempt === self::MAX_RETRIES - 1) throw new \Exception('Failed to deduct points due to concurrent updates. Please try again.', 0, $e);
                usleep(10000 * ($attempt + 1));
            } catch (\Exception $e) {
                $this->em->rollback();
                throw $e;
            }
        }
        return null;
    }

    public function updateMemberTier(int $memberId, string $tier)
    {
        $member = $this->getMember($memberId);
        if (!$member) {
            return null;
        }
        $member->setTier($tier);
        $this->memberRepository->save($member);
        return $member;
    }

    // ========== REWARDS ==========

    public function createReward(
        int $programId,
        string $name,
        string $description,
        int $pointsCost,
        string $rewardType,
        array $details = [],
        int $stock = -1
    ) {
        $this->validateRewardCreation($programId, $name, $description, $pointsCost);
        $reward = $this->buildReward($programId, $name, $description, $pointsCost, $rewardType, $details, $stock);
        $this->rewardRepository->save($reward);
        return $reward;
    }

    public function getReward(int $id)
    {
        return $this->rewardRepository->findById($id);
    }

    public function getRewardsByProgram(int $programId, int $limit = 50, int $offset = 0)
    {
        return $this->rewardRepository->findByProgram($programId, $limit, $offset);
    }

    public function countRewardsByProgram(int $programId)
    {
        return $this->rewardRepository->countByProgram($programId);
    }

    public function findAvailableRewards(int $userId, int $limit = 50, int $offset = 0)
    {
        $members = $this->memberRepository->findByUserId($userId, 1);
        if (empty($members)) {
            return [];
        }
        $member = $members[0];
        $points = $member->getPoints();
        return $this->rewardRepository->findByPointsRange(0, $points, $limit, $offset);
    }

    // ========== PRIVATE HELPERS ==========

    private function validatePoints(int $points)
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Points must be positive');
        }
    }

    private function validateProgramCreation(string $name, string $description, string $programType)
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Program name is required');
        }
        if (empty(trim($description))) {
            throw new \InvalidArgumentException('Program description is required');
        }
        if (empty(trim($programType))) {
            throw new \InvalidArgumentException('Program type is required');
        }
    }

    private function buildProgram(
        int $userId,
        string $name,
        string $description,
        string $programType,
        array $tierRules,
        array $rewards
    ) {
        $program = new LoyaltyProgram($name, $userId, $description, $programType);
        if (!empty($tierRules)) {
            $program->setTierRules($tierRules);
        }
        if (!empty($rewards)) {
            $program->setRewards($rewards);
        }
        return $program;
    }

    private function validateProgramUpdate(int $id, array $data)
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Program ID must be positive');
        }
        if (isset($data['name']) && trim($data['name']) === '') {
            throw new \InvalidArgumentException('Program name cannot be empty');
        }
        if (isset($data['description']) && trim($data['description']) === '') {
            throw new \InvalidArgumentException('Program description cannot be empty');
        }
        if (isset($data['programType']) && trim($data['programType']) === '') {
            throw new \InvalidArgumentException('Program type cannot be empty');
        }
    }

    private function applyProgramUpdate(LoyaltyProgram $program, array $data)
    {
        if (isset($data['name'])) {
            $program->setName(trim($data['name']));
        }
        if (isset($data['description'])) {
            $program->setDescription(trim($data['description']));
        }
        if (isset($data['programType'])) {
            $program->setProgramType(trim($data['programType']));
        }
        if (isset($data['tierRules'])) {
            $program->setTierRules($data['tierRules']);
        }
        if (isset($data['rewards'])) {
            $program->setRewards($data['rewards']);
        }
        if (isset($data['isActive'])) {
            $program->setActive(filter_var($data['isActive'], FILTER_VALIDATE_BOOLEAN));
        }
    }

    private function validateRewardCreation(int $programId, string $name, string $description, int $pointsCost)
    {
        if ($programId <= 0) {
            throw new \InvalidArgumentException('Program ID must be positive');
        }
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Reward name is required');
        }
        if (empty(trim($description))) {
            throw new \InvalidArgumentException('Reward description is required');
        }
        if ($pointsCost <= 0) {
            throw new \InvalidArgumentException('Points cost must be positive');
        }

        $program = $this->getProgram($programId);
        if (!$program) {
            throw new \InvalidArgumentException('Program not found');
        }
    }

    private function buildReward(
        int $programId,
        string $name,
        string $description,
        int $pointsCost,
        string $rewardType,
        array $details = [],
        int $stock = -1
    ) {
        $program = $this->getProgram($programId);
        if (!$program) {
            throw new \InvalidArgumentException('Program not found');
        }
        $reward = new LoyaltyReward($programId, $name, $pointsCost, $rewardType);
        $reward->setUserId($program->getUserId());
        $reward->setDescription($description);
        $reward->setDetails($details);
        $reward->setStock($stock);
        return $reward;
    }

    public function redeemReward(int $memberId, int $rewardId): LoyaltyRedemption
    {
        $reward = $this->getReward($rewardId);
        if (!$reward) {
            throw new \InvalidArgumentException('Reward not found');
        }
        $redemption = new LoyaltyRedemption($memberId, $rewardId, $reward->getPointsCost());
        $this->em->persist($redemption);
        $this->em->flush();
        return $redemption;
    }
}
