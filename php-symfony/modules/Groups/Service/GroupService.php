<?php

namespace App\Groups\Service;

use App\Groups\DomainEvent\GroupCreatedEvent;
use App\Groups\DomainEvent\GroupUpdatedEvent;
use App\Groups\DomainEvent\GroupDeletedEvent;
use App\Groups\Entity\Group;
use App\Groups\Entity\GroupMember;
use App\Groups\Repository\GroupRepository;
use App\Groups\Repository\GroupMemberRepository;
use App\Shared\EventBus\EventBus;
use Doctrine\ORM\EntityManagerInterface;

class GroupService
{
    private GroupRepository $groupRepository;
    private GroupMemberRepository $groupMemberRepository;
    private EntityManagerInterface $entityManager;
    private EventBus $eventBus;

    public function __construct(
        GroupRepository $groupRepository,
        GroupMemberRepository $groupMemberRepository,
        EntityManagerInterface $entityManager,
        EventBus $eventBus
    ) {
        $this->groupRepository = $groupRepository;
        $this->groupMemberRepository = $groupMemberRepository;
        $this->entityManager = $entityManager;
        $this->eventBus = $eventBus;
    }

    public function createGroup(
        string $name,
        string $ownerId,
        ?string $description = null,
        ?string $avatar = null,
        bool $isPublic = false,
        bool $isCommunity = false,
        bool $isChannel = false,
        bool $isForum = false,
        int $maxMembers = 500,
        ?array $settings = null
    ) {
         $group = new Group(
             $name,
             $ownerId,
             $description,
             $avatar,
             $isPublic,
             $isCommunity,
             $isChannel,
             $isForum,
             $maxMembers,
             $settings
         );

        $this->entityManager->persist($group);

        $this->eventBus->dispatch(new GroupCreatedEvent($group->getId(), $ownerId));

        return $group;
    }

    public function updateGroup(string $groupId, array $data)
    {
        $group = $this->groupRepository->find($groupId);
        if (!$group) {
            return null;
        }

        if (isset($data['name'])) {
            $group->setName($data['name']);
        }
        if (isset($data['description'])) {
            $group->setDescription($data['description']);
        }
        if (isset($data['avatar'])) {
            $group->setAvatar($data['avatar']);
        }
        if (isset($data['isPublic'])) {
            $group->setPublic($data['isPublic']);
        }
        if (isset($data['maxMembers'])) {
            $group->setMaxMembers($data['maxMembers']);
        }
        if (isset($data['settings'])) {
            $group->setSettings($data['settings']);
        }

        $this->entityManager->flush();
        $this->eventBus->dispatch(new GroupUpdatedEvent($groupId, $data));

        return $group;
    }

    public function deleteGroup(string $groupId)
    {
        $group = $this->groupRepository->find($groupId);
        if (!$group) {
            return false;
        }

        $group->setActive(false);
        $this->entityManager->flush();

        $this->eventBus->dispatch(new GroupDeletedEvent($groupId));

        return true;
    }

    public function getGroup(string $groupId)
    {
        return $this->groupRepository->find($groupId);
    }

    public function getUserGroups(string $userId)
    {
        return $this->groupRepository->findByUser($userId);
    }

    public function getPublicGroups(int $limit = 50, int $offset = 0)
    {
        return $this->groupRepository->findPublicGroups($limit, $offset);
    }

    public function getCommunities(int $limit = 50, int $offset = 0)
    {
        return $this->groupRepository->findCommunities($limit, $offset);
    }

    public function getChannels(int $limit = 50, int $offset = 0)
    {
        return $this->groupRepository->findChannels($limit, $offset);
    }

    public function searchGroups(string $query, int $limit = 20)
    {
        return $this->groupRepository->searchGroups($query, $limit);
    }

    public function isMember(string $groupId, string $userId)
    {
        $member = $this->groupMemberRepository->findByGroupAndUser($groupId, $userId);
        return $member !== null && $member->isActive();
    }

    public function canManage(string $groupId, string $userId)
    {
        $member = $this->groupMemberRepository->findByGroupAndUser($groupId, $userId);
        return $member !== null && $member->isActive() && $member->canManageGroup();
    }
}