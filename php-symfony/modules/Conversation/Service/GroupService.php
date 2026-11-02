<?php

declare(strict_types=1);

namespace App\Conversation\Service;

use App\Conversation\Entity\Group;
use App\Conversation\Repository\GroupRepository;
use App\User\Repository\UserRepository;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class GroupService
{
    private GroupRepository $groupRepository;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(
        GroupRepository $groupRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ) {
        $this->groupRepository = $groupRepository;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    public function getUserGroups(UserInterface $user, int $page = 1, int $limit = 20)
    {
        $userEntity = $this->userRepository->find($user->getUserIdentifier());
        if (!$userEntity) {
            throw new \Exception('User not found');
        }
        return $this->groupRepository->findUserGroups($userEntity, $page, $limit);
    }

    public function getPublicGroups(int $page = 1, int $limit = 20)
    {
        return $this->groupRepository->findPublicGroups($page, $limit);
    }

    public function getGroup(int $id)
    {
        $group = $this->groupRepository->find($id);
        if (!$group) {
            throw new \Exception('Group not found');
        }

        return $group;
    }

    public function createGroup(
        string $name,
        string $description,
        UserInterface $createdBy,
        bool $isPrivate = false,
        int $maxMembers = 0,
    ) {
        $userEntity = $this->userRepository->find($createdBy->getUserIdentifier());
        if (!$userEntity) {
            throw new \Exception('User not found');
        }

        $group = new Group();
        $group->setName($name);
        $group->setDescription($description);
        $group->setCreatedBy($userEntity);
        $group->setIsPrivate($isPrivate);
        $group->setMaxMembers($maxMembers);

        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $group;
    }

    public function updateGroup(Group $group, array $data)
    {
        if (isset($data['name'])) {
            $group->setName($data['name']);
        }
        if (isset($data['description'])) {
            $group->setDescription($data['description']);
        }
        if (isset($data['isPrivate'])) {
            $group->setIsPrivate($data['isPrivate']);
        }
        if (isset($data['maxMembers'])) {
            $group->setMaxMembers($data['maxMembers']);
        }
        if (isset($data['isActive'])) {
            $group->setIsActive($data['isActive']);
        }

        $this->entityManager->flush();

        return $group;
    }

    public function deleteGroup(Group $group)
    {
        $this->entityManager->remove($group);
        $this->entityManager->flush();
    }

    public function getGroupMembers(Group $group)
    {
        return $group->getMembers()->toArray();
    }

    public function addMember(Group $group, string $userId)
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        if ($this->groupRepository->isUserMember($group, $user)) {
            throw new \Exception('User is already a member');
        }

        $maxMembers = $group->getMaxMembers();
        if ($maxMembers > 0 && $this->groupRepository->countMembers($group) >= $maxMembers) {
            throw new \Exception('Group is full');
        }

        $group->addMember($user);
        $this->entityManager->flush();
    }

    public function removeMember(Group $group, string $userId)
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        if (!$this->groupRepository->isUserMember($group, $user)) {
            throw new \Exception('User is not a member');
        }

        $group->removeMember($user);
        $this->entityManager->flush();
    }

    public function isUserMember(Group $group, UserInterface $user)
    {
        $userEntity = $this->userRepository->find($user->getUserIdentifier());
        if (!$userEntity) {
            return false;
        }
        return $this->groupRepository->isUserMember($group, $userEntity);
    }
}
