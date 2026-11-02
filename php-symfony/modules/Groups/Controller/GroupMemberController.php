<?php

declare(strict_types=1);

namespace App\Groups\Controller;

use App\Groups\Service\GroupMemberService;
use App\Groups\Service\GroupService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/groups/{groupId}/members')]
class GroupMemberController extends BaseController
{
    private GroupMemberService $groupMemberService;
    private GroupService $groupService;

    public function __construct(
        GroupMemberService $groupMemberService,
        GroupService $groupService
    ) {
        $this->groupMemberService = $groupMemberService;
        $this->groupService = $groupService;
    }

    private function getAuthenticatedUserId(): string
    {
        $user = $this->getUser();
        if (!$user) {
            throw new InvalidArgumentException('Authentication required');
        }
        return $user->getUserIdentifier();
    }

    private function validateId(string $id): void
    {
        if (empty(trim($id))) {
            throw new InvalidArgumentException('ID cannot be empty');
        }
        if (strlen($id) > 36) {
            throw new InvalidArgumentException('ID cannot exceed 36 characters');
        }
    }

    private function validateRole(?string $role): void
    {
        if ($role !== null) {
            $validRoles = [
                \App\Groups\Entity\GroupMember::ROLE_OWNER,
                \App\Groups\Entity\GroupMember::ROLE_ADMIN,
                \App\Groups\Entity\GroupMember::ROLE_MODERATOR,
                \App\Groups\Entity\GroupMember::ROLE_MEMBER,
            ];
            if (!in_array($role, $validRoles, true)) {
                throw new InvalidArgumentException('Invalid role. Valid roles: ' . implode(', ', $validRoles));
            }
        }
    }

    #[Route('', name: 'group_members_join', methods: ['POST'])]
    public function join(string $groupId, Request $request): JsonResponse
    {
        try {
            $this->validateId($groupId); $authUserId = $this->getAuthenticatedUserId();
            $dataOr400 = $this->decodeJsonOr400($request); if ($dataOr400 instanceof JsonResponse) return $dataOr400; $data = $dataOr400;
            $requestedUserId = $data['userId'] ?? $authUserId;
            if ($requestedUserId !== $authUserId) return $this->json(['error' => 'Cannot join as different user'], Response::HTTP_FORBIDDEN);
            $role = $data['role'] ?? null; $this->validateRole($role);
            $group = $this->groupService->getGroup($groupId); if (!$group) return $this->json(['error' => 'Group not found'], Response::HTTP_NOT_FOUND);
            $member = $this->groupMemberService->joinGroup($group, $authUserId, $role ?? \App\Groups\Entity\GroupMember::ROLE_MEMBER);
            if (!$member) return $this->json(['error' => 'Cannot join group (maybe already a member or group full)'], Response::HTTP_BAD_REQUEST);
            $this->logAction('member_joined', ['group_id' => $groupId, 'user_id' => $authUserId, 'role' => $member->getRole()]);
            $this->incrementMetric('group_joins');
            return $this->json(['member' => $member->toArray()], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('join_group', $e);
        }
    }

    #[Route('/{userId}', name: 'group_members_leave', methods: ['DELETE'])]
    public function leave(string $groupId, string $userId, Request $request): JsonResponse
    {
        try {
            $this->validateId($groupId);
            $this->validateId($userId);
            $authUserId = $this->getAuthenticatedUserId();

            if ($userId !== $authUserId) {
                return $this->json(['error' => 'Cannot leave for another user'], Response::HTTP_FORBIDDEN);
            }

            $success = $this->groupMemberService->leaveGroup($groupId, $authUserId);
            if (!$success) {
                return $this->json(['error' => 'Cannot leave group (maybe not a member)'], Response::HTTP_BAD_REQUEST);
            }

            $this->logAction('member_left', ['group_id' => $groupId, 'user_id' => $authUserId]);
            $this->incrementMetric('group_leaves');

            return $this->json(['message' => 'Left group successfully']);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('leave_group', $e);
        }
    }

    #[Route('', name: 'group_members_list', methods: ['GET'])]
    public function list(string $groupId): JsonResponse
    {
        try {
            $this->validateId($groupId);
            $members = $this->groupMemberService->getGroupMembers($groupId);

            $this->logAction('members_listed', ['group_id' => $groupId, 'count' => count($members)]);

            return $this->json([
                'members' => array_map(fn($m) => $m->toArray(), $members),
                'total' => count($members),
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('list_members', $e);
        }
    }

    #[Route('/{userId}/role', name: 'group_members_change_role', methods: ['PUT'])]
    public function changeRole(string $groupId, string $userId, Request $request): JsonResponse
    {
        try {
            $this->validateId($groupId); $this->validateId($userId); $authUserId = $this->getAuthenticatedUserId();
            if (!$this->groupService->canManage($groupId, $authUserId)) return $this->json(['error' => 'Access denied: only admins or owners can change roles'], Response::HTTP_FORBIDDEN);
            $dataOr400 = $this->decodeJsonOr400($request); if ($dataOr400 instanceof JsonResponse) return $dataOr400; $data = $dataOr400;
            $newRole = $data['role'] ?? null; $actorId = $authUserId;
            if (!$newRole) return $this->json(['error' => 'Role is required'], Response::HTTP_BAD_REQUEST);
            $this->validateRole($newRole);
            $success = $this->groupMemberService->changeRole($groupId, $userId, $newRole, $actorId);
            if (!$success) return $this->json(['error' => 'Cannot change role (maybe member not found or invalid transition)'], Response::HTTP_BAD_REQUEST);
            $this->logAction('role_changed', ['group_id' => $groupId, 'user_id' => $userId, 'new_role' => $newRole, 'actor_id' => $actorId]);
            $this->incrementMetric('role_changes');
            return $this->json(['message' => 'Role changed successfully']);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('change_role', $e);
        }
    }

    #[Route('/{userId}/ban', name: 'group_members_ban', methods: ['POST'])]
    public function ban(string $groupId, string $userId, Request $request): JsonResponse
    {
        try {
            $this->validateId($groupId);
            $this->validateId($userId);
            $authUserId = $this->getAuthenticatedUserId();

            if (!$this->groupService->canManage($groupId, $authUserId)) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $success = $this->groupMemberService->banMember($groupId, $userId, $authUserId);
            if (!$success) {
                return $this->json(['error' => 'Cannot ban member'], Response::HTTP_BAD_REQUEST);
            }

            $this->logAction('member_banned', ['group_id' => $groupId, 'user_id' => $userId, 'banned_by' => $authUserId]);
            $this->incrementMetric('member_bans');

            return $this->json(['message' => 'Member banned']);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('ban_member', $e);
        }
    }

    #[Route('/{userId}/unban', name: 'group_members_unban', methods: ['POST'])]
    public function unban(string $groupId, string $userId, Request $request): JsonResponse
    {
        try {
            $this->validateId($groupId);
            $this->validateId($userId);
            $authUserId = $this->getAuthenticatedUserId();

            if (!$this->groupService->canManage($groupId, $authUserId)) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $success = $this->groupMemberService->unbanMember($groupId, $userId, $authUserId);
            if (!$success) {
                return $this->json(['error' => 'Cannot unban member'], Response::HTTP_BAD_REQUEST);
            }

            $this->logAction('member_unbanned', ['group_id' => $groupId, 'user_id' => $userId, 'unbanned_by' => $authUserId]);
            $this->incrementMetric('member_unbans');

            return $this->json(['message' => 'Member unbanned']);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('unban_member', $e);
        }
    }

    #[Route('/{userId}/mute', name: 'group_members_mute', methods: ['POST'])]
    public function mute(string $groupId, string $userId, Request $request): JsonResponse
    {
        try {
            $this->validateId($groupId);
            $this->validateId($userId);
            $authUserId = $this->getAuthenticatedUserId();

            if (!$this->groupService->canManage($groupId, $authUserId)) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $success = $this->groupMemberService->muteMember($groupId, $userId, $authUserId);
            if (!$success) {
                return $this->json(['error' => 'Cannot mute member'], Response::HTTP_BAD_REQUEST);
            }

            $this->logAction('member_muted', ['group_id' => $groupId, 'user_id' => $userId, 'muted_by' => $authUserId]);
            $this->incrementMetric('member_mutes');

            return $this->json(['message' => 'Member muted']);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('mute_member', $e);
        }
    }

    #[Route('/{userId}/unmute', name: 'group_members_unmute', methods: ['POST'])]
    public function unmute(string $groupId, string $userId, Request $request): JsonResponse
    {
        try {
            $this->validateId($groupId);
            $this->validateId($userId);
            $authUserId = $this->getAuthenticatedUserId();

            if (!$this->groupService->canManage($groupId, $authUserId)) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $success = $this->groupMemberService->unmuteMember($groupId, $userId, $authUserId);
            if (!$success) {
                return $this->json(['error' => 'Cannot unmute member'], Response::HTTP_BAD_REQUEST);
            }

            $this->logAction('member_unmuted', ['group_id' => $groupId, 'user_id' => $userId, 'unmuted_by' => $authUserId]);
            $this->incrementMetric('member_unmutes');

            return $this->json(['message' => 'Member unmuted']);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->handleException('unmute_member', $e);
        }
    }

    // ========== PRIVATE METHODS ==========

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("GroupMemberController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        $this->incrementMetric("{$context}_errors");

        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
}
