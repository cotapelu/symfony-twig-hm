<?php declare(strict_types=1);

namespace App\Tests\Unit\Groups\Service;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use App\Groups\Service\GroupMemberService;
use App\Groups\Repository\GroupMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Shared\EventBus\EventBus;
use App\Groups\Entity\Group;
use App\Groups\Entity\GroupMember;
use App\User\Entity\User;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GroupMemberServiceTest extends TestCase
{
    private GroupMemberRepository&MockObject $groupMemberRepo;
    private EntityManagerInterface&MockObject $entityManager;
    private EventBus&MockObject $eventBus;
    private GroupMemberService $service;

    protected function setUp(): void
    {
        $this->groupMemberRepo = $this->createMock(GroupMemberRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventBus = $this->createMock(EventBus::class);

        $this->service = new GroupMemberService(
            $this->groupMemberRepo,
            $this->entityManager,
            $this->eventBus
        );
    }

    // ================= joinGroup =================

    public function testJoinGroupReturnsNullWhenGroupCannotJoin(): void
    {
        $group = $this->createMock(Group::class);
        $group->method('canJoin')->willReturn(false);
        $userId = 'user-123';

        $this->groupMemberRepo->expects($this->never())->method('findByGroupAndUser');

        $result = $this->service->joinGroup($group, $userId);
        $this->assertNull($result);
    }

    public function testJoinGroupReturnsExistingMemberWhenAlreadyMember(): void
    {
        $group = $this->createMock(Group::class);
        $group->method('canJoin')->willReturn(true);
        $groupId = 'group-123';
        $userId = 'user-123';
        $group->method('getId')->willReturn($groupId);

        $existingMember = $this->createMock(GroupMember::class);

        $this->groupMemberRepo->expects($this->once())
            ->method('findByGroupAndUser')
            ->with($groupId, $userId)
            ->willReturn($existingMember);

        $result = $this->service->joinGroup($group, $userId);
        $this->assertSame($existingMember, $result);
    }

    public function testJoinGroupReturnsNullWhenUserNotFound(): void
    {
        $group = $this->createMock(Group::class);
        $group->method('canJoin')->willReturn(true);
        $groupId = 'group-123';
        $userId = 'user-456';
        $group->method('getId')->willReturn($groupId);

        $this->groupMemberRepo->expects($this->once())
            ->method('findByGroupAndUser')
            ->with($groupId, $userId)
            ->willReturn(null);

        $userRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepo->method('find')->with($userId)->willReturn(null);
        $this->entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepo);
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->joinGroup($group, $userId);
        $this->assertNull($result);
    }

    public function testJoinGroupSuccess(): void
    {
        $group = $this->createMock(Group::class);
        $group->method('canJoin')->willReturn(true);
        $groupId = 'group-123';
        $userId = 'user-789';
        $role = GroupMember::ROLE_ADMIN;
        $group->method('getId')->willReturn($groupId);

        $this->groupMemberRepo->expects($this->once())
            ->method('findByGroupAndUser')
            ->with($groupId, $userId)
            ->willReturn(null);

        $user = $this->createMock(User::class);

        $userRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepo->method('find')->with($userId)->willReturn($user);
        $this->entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepo);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(GroupMember::class));
        $this->entityManager->expects($this->once())->method('flush');
        $group->expects($this->once())->method('addMember')->with($this->isInstanceOf(GroupMember::class));

        $this->eventBus->expects($this->once())->method('dispatch')
            ->with($this->callback(function ($event) use ($groupId, $userId) {
                return $event->getGroupId() === $groupId
                    && $event->getUserId() === $userId
                    && $event->getMemberId() !== null;
            }));

        $result = $this->service->joinGroup($group, $userId, $role);

        $this->assertInstanceOf(GroupMember::class, $result);
        $this->assertSame($user, $result->getUser());
        $this->assertSame($group, $result->getGroup());
        $this->assertSame($role, $result->getRole());
    }

    // ================= leaveGroup =================

    public function testLeaveGroupReturnsFalseWhenMemberNotFound(): void
    {
        $groupId = 'group-123';
        $userId = 'user-456';

        $this->groupMemberRepo->expects($this->once())
            ->method('findByGroupAndUser')
            ->with($groupId, $userId)
            ->willReturn(null);

        $result = $this->service->leaveGroup($groupId, $userId);
        $this->assertFalse($result);
    }

    public function testLeaveGroupReturnsFalseWhenMemberIsOwner(): void
    {
        $groupId = 'group-123';
        $userId = 'user-789';

        $member = $this->createMock(GroupMember::class);
        $member->method('isOwner')->willReturn(true);

        $this->groupMemberRepo->expects($this->once())
            ->method('findByGroupAndUser')
            ->with($groupId, $userId)
            ->willReturn($member);

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->leaveGroup($groupId, $userId);
        $this->assertFalse($result);
    }

    public function testLeaveGroupSuccess(): void
    {
        $groupId = 'group-123';
        $userId = 'user-999';
        $memberId = 'member-id-123';

        $member = $this->createMock(GroupMember::class);
        $member->method('isOwner')->willReturn(false);
        $member->method('getId')->willReturn($memberId);
        $member->expects($this->once())->method('setActive')->with(false);

        $this->groupMemberRepo->expects($this->once())
            ->method('findByGroupAndUser')
            ->with($groupId, $userId)
            ->willReturn($member);
        $this->entityManager->expects($this->once())->method('flush');
        $this->eventBus->expects($this->once())->method('dispatch')
            ->with($this->callback(function ($event) use ($groupId, $userId, $memberId) {
                return $event->getGroupId() === $groupId
                    && $event->getUserId() === $userId
                    && $event->getMemberId() === $memberId;
            }));

        $result = $this->service->leaveGroup($groupId, $userId);
        $this->assertTrue($result);
    }

    // ================= getMember =================
    public function testGetMemberDelegatesToRepository(): void
    {
        $groupId = 'group-123';
        $userId = 'user-123';
        $member = $this->createMock(GroupMember::class);

        $this->groupMemberRepo->expects($this->once())
            ->method('findByGroupAndUser')
            ->with($groupId, $userId)
            ->willReturn($member);

        $result = $this->service->getMember($groupId, $userId);
        $this->assertSame($member, $result);
    }

    // ================= getGroupMembers =================
    public function testGetGroupMembersDelegatesToRepository(): void
    {
        $groupId = 'group-123';
        $members = [$this->createMock(GroupMember::class), $this->createMock(GroupMember::class)];

        $this->groupMemberRepo->expects($this->once())
            ->method('findByGroup')
            ->with($groupId)
            ->willReturn($members);

        $result = $this->service->getGroupMembers($groupId);
        $this->assertSame($members, $result);
    }

    // ================= getUserGroups =================
    public function testGetUserGroupsDelegatesToRepository(): void
    {
        $userId = 'user-123';
        $memberships = [$this->createMock(GroupMember::class)];

        $this->groupMemberRepo->expects($this->once())
            ->method('findByUser')
            ->with($userId)
            ->willReturn($memberships);

        $result = $this->service->getUserGroups($userId);
        $this->assertSame($memberships, $result);
    }

    // ================= updateLastActive =================
    public function testUpdateLastActiveDelegatesToRepository(): void
    {
        $memberId = 'member-123';
        $this->groupMemberRepo->expects($this->once())
            ->method('updateLastActive')
            ->with($memberId);

        $this->service->updateLastActive($memberId);
        $this->assertTrue(true);
    }
}
