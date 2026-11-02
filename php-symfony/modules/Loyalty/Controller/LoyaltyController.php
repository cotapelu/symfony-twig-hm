<?php

declare(strict_types=1);

namespace App\Loyalty\Controller;

use App\Loyalty\Service\LoyaltyService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use DateTimeImmutable;

#[Route('/api/loyalty')]
class LoyaltyController extends BaseController
{
    public function __construct(
        private readonly LoyaltyService $service
    ) {
        parent::__construct();
    }

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }
        return (string) $user->getId();
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleException('controller', $e);
        }
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("LoyaltyController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        return $this->json(['error' => 'Internal server error'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function logAction(string $action, array $context = []): void
    {
        $logEntry = [
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'action' => $action,
            'context' => $context,
        ];
        error_log(json_encode($logEntry));
    }

    private function incrementMetric(string $metricName): void
    {
        error_log(json_encode([
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'type' => 'metric',
            'name' => $metricName,
            'value' => 1,
        ]));
    }

    private function authorizeProgram(int $programId, string $userId): void
    {
        if (!$this->service->isProgramOwner($programId, (int)$userId)) {
            throw new AccessDeniedHttpException('You do not own this program');
        }
    }

    private function applyProgramFields($program, array $data): void
    {
        if (isset($data['tier_rules'])) {
            $program->setTierRules($data['tier_rules']);
        }
        if (isset($data['rewards'])) {
            $program->setRewards($data['rewards']);
        }
    }

    private function applyRewardFields($reward, array $data): void
    {
        if (isset($data['description'])) {
            $reward->setDescription($data['description']);
        }
        if (isset($data['stock'])) {
            $reward->setStock((int)$data['stock']);
        }
    }

    private function buildListResponse(array $items, int $total, int $limit, int $offset): JsonResponse
    {
        return $this->json([
            'data' => array_values($items),
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($items)
            ]
        ]);
    }

    private function ensureMember(string $userId, int $programId): \App\Loyalty\Entity\LoyaltyMember|JsonResponse
    {
        $members = $this->service->getMemberByUser((int)$userId, 1);
        foreach ($members as $m) {
            if ($m->getProgramId() === $programId) {
                return $m;
            }
        }
        return $this->json(['error' => 'Not a member of this program'], JsonResponse::HTTP_BAD_REQUEST);
    }

    private function getMemberOrAccessDenied(int $id, string $userId): mixed
    {
        $member = $this->service->getMember($id);
        if (!$member) {
            throw new InvalidArgumentException('Member not found');
        }
        if ($member->getUserId() !== (int)$userId) {
            throw new AccessDeniedHttpException('Access denied');
        }
        return $member;
    }

    private function validatePoints(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }
        $points = (int)($data['points'] ?? 0);
        if ($points <= 0) {
            throw new InvalidArgumentException('Points must be positive');
        }
        return $points;
    }

    private function validateCreateRewardData(array $data): void
    {
        $programId = $data['program_id'] ?? 0;
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $pointsCost = (int)($data['points_cost'] ?? 0);

        if ($programId <= 0) {
            throw new InvalidArgumentException('Program ID is required');
        }
        if ($name === '') {
            throw new InvalidArgumentException('Reward name is required');
        }
        if ($description === '') {
            throw new InvalidArgumentException('Reward description is required');
        }
        if ($pointsCost <= 0) {
            throw new InvalidArgumentException('Points cost must be positive');
        }
    }

    private function assertRedemptionPossible(\App\Loyalty\Entity\LoyaltyMember $member, int $rewardId): \App\Loyalty\Entity\LoyaltyReward
    {
        $reward = $this->service->getReward($rewardId);
        if (!$reward) {
            throw new InvalidArgumentException('Reward not found');
        }
        if ($member->getPoints() < $reward->getPointsCost()) {
            throw new InvalidArgumentException('Insufficient points');
        }
        return $reward;
    }

    #[Route('/programs', name: 'loyalty_create_program', methods: ['POST'])]
    public function createProgram(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (string) $userIdOr401;

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            if (empty($data)) {
                throw new InvalidArgumentException('Invalid JSON payload');
            }

            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');

            if ($name === '') {
                throw new InvalidArgumentException('Program name is required');
            }
            if ($description === '') {
                throw new InvalidArgumentException('Program description is required');
            }

            $program = $this->service->createProgram((int)$userId, $name, $description);
            $this->applyProgramFields($program, $data);

            $this->logAction('program_created', ['program_id'=>$program->getId()]); $this->incrementMetric('programs_created');

            return $this->json($program, JsonResponse::HTTP_CREATED);
        });
    }

    #[Route('/programs', name: 'loyalty_get_programs', methods: ['GET'])]
    public function getPrograms(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $limit = max(1, min(100, (int)($request->query->get('limit', 50))));
            $offset = max(0, (int)($request->query->get('offset', 0)));

            $programs = $this->service->getActivePrograms($limit, $offset);
            $total = $this->service->countActivePrograms();

            return $this->buildListResponse($programs, $total, $limit, $offset);
        });
    }

    #[Route('/programs/{id}', name: 'loyalty_get_program', methods: ['GET'])]
    public function getProgram(int $id): JsonResponse
    {
        return $this->handle(function() use ($id) {
            if ($id <= 0) {
                throw new InvalidArgumentException('Program ID must be positive');
            }

            $program = $this->service->getProgram($id);
            if (!$program) {
                return $this->json(['error' => 'Program not found'], JsonResponse::HTTP_NOT_FOUND);
            }

            return $this->json(['data' => $program]);
        });
    }

    #[Route('/programs/{id}', name: 'loyalty_update_program', methods: ['PUT'])]
    public function updateProgram(int $id, Request $request): JsonResponse
    {
        return $this->handle(function() use ($id, $request) {
            if ($id <= 0) {
                throw new InvalidArgumentException('Program ID must be positive');
            }

            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (string) $userIdOr401;
            $this->authorizeProgram($id, $userId);

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            if (empty($data)) {
                throw new InvalidArgumentException('Invalid JSON payload');
            }

            $program = $this->service->updateProgram($id, $data);
            if (!$program) {
                return $this->json(['error' => 'Program not found'], JsonResponse::HTTP_NOT_FOUND);
            }

            $this->logAction('program_updated', ['program_id' => $id]);
            $this->incrementMetric('programs_updated');

            return $this->json(['data' => $program]);
        });
    }

    #[Route('/members', name: 'loyalty_enroll_member', methods: ['POST'])]
    public function enrollMember(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (string) $userIdOr401;

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            if (empty($data)) {
                throw new InvalidArgumentException('Invalid JSON payload');
            }

            $programId = $data['program_id'] ?? 0;
            if ($programId <= 0) {
                throw new InvalidArgumentException('Program ID is required');
            }

            $member = $this->service->enrollMember((int)$userId, $programId);

            $this->logAction('member_enrolled', [
                'member_id' => $member->getId(),
                'user_id' => $userId,
                'program_id' => $programId
            ]);
            $this->incrementMetric('members_enrolled');

            return $this->json(['data' => $member], JsonResponse::HTTP_CREATED);
        });
    }

    #[Route('/members/me', name: 'loyalty_get_my_memberships', methods: ['GET'])]
    public function getMyMemberships(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (string) $userIdOr401;

            $limit = max(1, min(100, (int)($request->query->get('limit', 50))));
            $offset = max(0, (int)($request->query->get('offset', 0)));

            $members = $this->service->getMemberByUser((int)$userId, $limit, $offset);
            $total = $this->service->countMembersByUser((int)$userId);

            return $this->buildListResponse($members, $total, $limit, $offset);
        });
    }

    #[Route('/members/{id}', name: 'loyalty_get_member', methods: ['GET'])]
    public function getMember(int $id, Request $request): JsonResponse
    {
        return $this->handle(function() use ($id, $request) {
            if ($id <= 0) {
                throw new InvalidArgumentException('Member ID must be positive');
            }

            $member = $this->service->getMember($id);
            if (!$member) {
                return $this->json(['error' => 'Member not found'], JsonResponse::HTTP_NOT_FOUND);
            }

            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (string) $userIdOr401;
            if ($member->getUserId() !== (int)$userId) {
                return $this->json(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
            }

            return $this->json(['data' => $member]);
        });
    }

    #[Route('/members/{id}/points', name: 'loyalty_add_points', methods: ['POST'])]
    public function addPoints(int $id, Request $request): JsonResponse
    {
        return $this->handle(function() use ($id, $request) {
            if ($id <= 0) {
                throw new InvalidArgumentException('Member ID must be positive');
            }

            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (string) $userIdOr401;

            $member = $this->getMemberOrAccessDenied($id, $userId);

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            $points = $this->validatePoints($data);

            $updated = $this->service->addPoints($id, $points);
            if (!$updated) {
                return $this->json(['error' => 'Failed to add points'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $this->logAction('points_added', ['member_id'=>$id, 'user_id'=>$userId, 'points'=>$points]); $this->incrementMetric('points_added');

            return $this->json(['data' => $updated]);
        });
    }

    #[Route('/members/{id}/deduct', name: 'loyalty_deduct_points', methods: ['PUT'])]
    public function deductPoints(int $id, Request $request): JsonResponse
    {
        return $this->handle(function() use ($id, $request) {
            if ($id <= 0) {
                throw new InvalidArgumentException('Member ID must be positive');
            }

            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (string) $userIdOr401;

            $member = $this->getMemberOrAccessDenied($id, $userId);

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            $points = $this->validatePoints($data);

            if ($member->getPoints() < $points) {
                return $this->json(['error' => 'Insufficient points'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $updated = $this->service->deductPoints($id, $points);
            if (!$updated) {
                return $this->json(['error' => 'Failed to deduct points'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $this->logAction('points_deducted', ['member_id'=>$id, 'user_id'=>$userId, 'points'=>$points]); $this->incrementMetric('points_deducted');

            return $this->json(['data' => $updated]);
        });
    }

    #[Route('/rewards', name: 'loyalty_create_reward', methods: ['POST'])]
    public function createReward(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (string) $userIdOr401;

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            if (empty($data)) {
                throw new InvalidArgumentException('Invalid JSON payload');
            }

            $this->validateCreateRewardData($data);

            $programId = (int)$data['program_id']; $this->authorizeProgram($programId, $userId);

            $reward = $this->service->createReward($programId, $data['name'], $data['description'], (int)$data['points_cost'], $data['reward_type'] ?? 'discount');
            $this->applyRewardFields($reward, $data);

            $this->logAction('reward_created', ['program_id'=>$programId, 'reward_id'=>$reward->getId()]); $this->incrementMetric('rewards_created');

            return $this->json($reward, JsonResponse::HTTP_CREATED);
        });
    }

    #[Route('/rewards', name: 'loyalty_get_rewards', methods: ['GET'])]
    public function getRewards(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $programId = (int)($request->query->get('program_id', 0));
            if ($programId <= 0) {
                return $this->json(['error' => 'Program ID is required'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $limit = max(1, min(100, (int)($request->query->get('limit', 50))));
            $offset = max(0, (int)($request->query->get('offset', 0)));

            $rewards = $this->service->getRewardsByProgram($programId, $limit, $offset);
            $total = $this->service->countRewardsByProgram($programId);

            return $this->buildListResponse($rewards, $total, $limit, $offset);
        });
    }

    #[Route('/rewards/{id}', name: 'loyalty_get_reward', methods: ['GET'])]
    public function getReward(int $id): JsonResponse
    {
        return $this->handle(function() use ($id) {
            if ($id <= 0) {
                throw new InvalidArgumentException('Reward ID must be positive');
            }

            $reward = $this->service->getReward($id);
            if (!$reward) {
                return $this->json(['error' => 'Reward not found'], JsonResponse::HTTP_NOT_FOUND);
            }

            return $this->json(['data' => $reward]);
        });
    }

    #[Route('/rewards/{id}/redeem', name: 'loyalty_redeem_reward', methods: ['POST'])]
    public function redeemReward(int $id, Request $request): JsonResponse
    {
        return $this->handle(function() use ($id, $request) {
            if ($id <= 0) throw new InvalidArgumentException('Reward ID must be positive');

            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
            $userId = (string) $userIdOr401;

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) return $dataOr400;
            $data = $dataOr400;
            if (empty($data)) throw new InvalidArgumentException('Invalid JSON payload');

            $programId = $data['program_id'] ?? 0; if ($programId <= 0) throw new InvalidArgumentException('Program ID is required');

            $member = $this->ensureMember($userId, $programId); if ($member instanceof JsonResponse) return $member;

            $reward = $this->assertRedemptionPossible($member, $id);

            $this->service->deductPoints($member->getId(), $reward->getPointsCost()); $redemption = $this->service->redeemReward($member->getId(), $id);

            $this->logAction('reward_redeemed', ['member_id'=>$member->getId(),'reward_id'=>$id,'points_spent'=>$reward->getPointsCost()]); $this->incrementMetric('rewards_redeemed');

            return $this->json([
                'message' => 'Reward redeemed successfully',
                'points_remaining' => $member->getPoints() - $reward->getPointsCost()
            ], JsonResponse::HTTP_CREATED);
        });
    }

    #[Route('/redemptions/me', name: 'loyalty_get_my_redemptions', methods: ['GET'])]
    public function getMyRedemptions(Request $request): JsonResponse
    {
        return $this->handle(function() use ($request) {
            $userIdOr401 = $this->requireAuthOr401();
            if ($userIdOr401 instanceof JsonResponse) {
                return $userIdOr401;
            }
            $userId = (string) $userIdOr401;
            $members = $this->service->getMemberByUser((int)$userId, 10);
            if (empty($members)) {
                return $this->json(['data' => []]);
            }

            return $this->json(['data' => []]);
        });
    }
}
