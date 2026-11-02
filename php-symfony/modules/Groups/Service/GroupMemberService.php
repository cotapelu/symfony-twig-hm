<?php

namespace App\Groups\Service;

use App\Groups\DomainEvent\GroupJoinedEvent;
use App\Groups\DomainEvent\GroupLeftEvent;
use App\Groups\DomainEvent\MemberRoleChangedEvent;
use App\Groups\Entity\Group;
use App\Groups\Entity\GroupMember;
use App\Groups\Repository\GroupMemberRepository;
use App\User\Entity\User;
use App\Shared\EventBus\EventBus;
use Doctrine\ORM\EntityManagerInterface;

class GroupMemberService
{
    private GroupMemberRepository $groupMemberRepository;
    private EntityManagerInterface $entityManager;
    private EventBus $eventBus;

    public function __construct(
        GroupMemberRepository $groupMemberRepository,
        EntityManagerInterface $entityManager,
        EventBus $eventBus
    ) {
        $this->groupMemberRepository = $groupMemberRepository;
        $this->entityManager = $entityManager;
        $this->eventBus = $eventBus;
    }

    public function joinGroup(Group $group, string $userId, ?string $role = null)
    {
        if (!$group->canJoin()) {
            return null;
        }

        $existingMember = $this->groupMemberRepository->findByGroupAndUser($group->getId(), $userId);
        if ($existingMember) {
            return $existingMember;
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return null;
        }

        $member = new GroupMember(
            $user,
            $group,
            $role ?? GroupMember::ROLE_MEMBER
        );

        $this->entityManager->persist($member);
        $group->addMember($member);
        $this->entityManager->flush();

        $this->eventBus->dispatch(new GroupJoinedEvent($group->getId(), $userId, $member->getId()));

        return $member;
    }

    public function leaveGroup(string $groupId, string $userId)
    {
        $member = $this->groupMemberRepository->findByGroupAndUser($groupId, $userId);
        if (!$member) {
            return false;
        }

        if ($member->isOwner()) {
            return false;
        }

        $member->setActive(false);
        $this->entityManager->flush();

        $this->eventBus->dispatch(new GroupLeftEvent($groupId, $userId, $member->getId()));

        return true;
    }

    public function changeRole(string $groupId, string $userId, string $newRole, string $actorId)
    {
        $member = $this->groupMemberRepository->findByGroupAndUser($groupId, $userId);
        if (!$member) {
            return false;
        }

        $actor = $this->groupMemberRepository->findByGroupAndUser($groupId, $actorId);
        if (!$actor || !$actor->canManageGroup()) {
            return false;
        }

        $oldRole = $member->getRole();
        $member->setRole($newRole);
        $this->entityManager->flush();

        $this->eventBus->dispatch(new MemberRoleChangedEvent($groupId, $userId, $member->getId(), $oldRole, $newRole, $actorId));

        return true;
    }

    public function muteMember(string $groupId, string $userId, string $actorId)
    {
        $member = $this->groupMemberRepository->findByGroupAndUser($groupId, $userId);
        if (!$member) {
            return false;
        }

        $actor = $this->groupMemberRepository->findByGroupAndUser($groupId, $actorId);
        if (!$actor || !$actor->canManageGroup()) {
            return false;
        }

        $member->setMuted(true);
        $this->entityManager->flush();

        return true;
    }

    public function unmuteMember(string $groupId, string $userId, string $actorId)
    {
        $member = $this->groupMemberRepository->findByGroupAndUser($groupId, $userId);
        if (!$member) {
            return false;
        }

        $actor = $this->groupMemberRepository->findByGroupAndUser($groupId, $actorId);
        if (!$actor || !$actor->canManageGroup()) {
            return false;
        }

        $member->setMuted(false);
        $this->entityManager->flush();

        return true;
    }

    public function banMember(string $groupId, string $userId, string $actorId)
    {
        $member = $this->groupMemberRepository->findByGroupAndUser($groupId, $userId);
        if (!$member) {
            return false;
        }

        $actor = $this->groupMemberRepository->findByGroupAndUser($groupId, $actorId);
        if (!$actor || !$actor->canManageGroup()) {
            return false;
        }

        $member->setBanned(true);
        $member->setActive(false);
        $this->entityManager->flush();

        return true;
    }

    public function unbanMember(string $groupId, string $userId, string $actorId)
    {
        $member = $this->groupMemberRepository->findByGroupAndUser($groupId, $userId);
        if (!$member) {
            return false;
        }

        $actor = $this->groupMemberRepository->findByGroupAndUser($groupId, $actorId);
        if (!$actor || !$actor->canManageGroup()) {
            return false;
        }

        $member->setBanned(false);
        $member->setActive(true);
        $this->entityManager->flush();

        return true;
    }

    public function getGroupMembers(string $groupId)
    {
        return $this->groupMemberRepository->findByGroup($groupId);
    }

    public function getMember(string $groupId, string $userId)
    {
        return $this->groupMemberRepository->findByGroupAndUser($groupId, $userId);
    }

    public function getUserGroups(string $userId)
    {
        return $this->groupMemberRepository->findByUser($userId);
    }

    public function updateLastActive(string $memberId)
    {
        $this->groupMemberRepository->updateLastActive($memberId);
    }
}