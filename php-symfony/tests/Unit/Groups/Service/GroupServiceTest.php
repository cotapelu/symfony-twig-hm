<?php declare(strict_types=1);

namespace App\Tests\Unit\Groups\Service;

use App\Groups\Entity\Group;
use App\Groups\Entity\GroupMember;
use App\Groups\Repository\GroupMemberRepository;
use App\Groups\Repository\GroupRepository;
use App\Groups\Service\GroupService;
use App\Shared\EventBus\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GroupServiceTest extends TestCase
{
    private GroupRepository&MockObject $groupRepoMock;
    private GroupMemberRepository&MockObject $memberRepoMock;
    private EntityManagerInterface&MockObject $emMock;
    private EventBus&MockObject $eventBusMock;
    private GroupService $service;

    protected function setUp(): void
    {
        $this->groupRepoMock = $this->createMock(GroupRepository::class);
        $this->memberRepoMock = $this->createMock(GroupMemberRepository::class);
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->eventBusMock = $this->createMock(EventBus::class);

        $this->service = new GroupService(
            $this->groupRepoMock,
            $this->memberRepoMock,
            $this->emMock,
            $this->eventBusMock
        );
    }

    public function testCreateGroupPersistsGroupAndDispatchesEvent(): void
    {
        $ownerId = 'user_owner';
        $this->emMock->expects($this->once())->method('persist')->with($this->callback(function(Group $g) use ($ownerId) {
            return $g->getName() === 'Test Group'
                && $g->getOwnerId() === $ownerId
                && $g->isPublic() === false
                && $g->getMaxMembers() === 500;
        }));

        $this->eventBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($event) {
                return method_exists($event, 'getGroupId') && is_string($event->getGroupId());
            }));

        $result = $this->service->createGroup('Test Group', $ownerId);
        $this->assertInstanceOf(Group::class, $result);
    }

    public function testUpdateGroupWhenNotFoundReturnsNull(): void
    {
        $this->groupRepoMock->expects($this->once())->method('find')->with('unknown')->willReturn(null);

        $result = $this->service->updateGroup('unknown', ['name' => 'New']);
        $this->assertNull($result);
    }

    public function testUpdateGroupPartialFields(): void
    {
        $group = $this->createMock(Group::class);
        $group->method('getName')->willReturn('Old Name');
        $group->expects($this->once())->method('setName')->with('New Name');
        $group->expects($this->never())->method('setDescription');

        $this->groupRepoMock->expects($this->once())->method('find')->with('gid')->willReturn($group);
        $this->emMock->expects($this->once())->method('flush');
        $this->eventBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($event) {
                return method_exists($event, 'getGroupId') && $event->getGroupId() === 'gid';
            }));

        $result = $this->service->updateGroup('gid', ['name' => 'New Name']);
        $this->assertSame($group, $result);
    }

    public function testDeleteGroupWhenNotFoundReturnsFalse(): void
    {
        $this->groupRepoMock->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $result = $this->service->deleteGroup('missing');
        $this->assertFalse($result);
    }

    public function testDeleteGroupSoftDeletesAndDispatchesEvent(): void
    {
        $group = $this->createMock(Group::class);
        $group->expects($this->once())->method('setActive')->with(false);
        $this->groupRepoMock->expects($this->once())->method('find')->with('gid')->willReturn($group);
        $this->emMock->expects($this->once())->method('flush');
        $this->eventBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($event) {
                return method_exists($event, 'getGroupId') && $event->getGroupId() === 'gid';
            }));

        $result = $this->service->deleteGroup('gid');
        $this->assertTrue($result);
    }

    public function testGetGroupDelegatesToRepository(): void
    {
        $group = new Group('G', 'u');
        $this->groupRepoMock->expects($this->once())->method('find')->with('gid')->willReturn($group);
        $result = $this->service->getGroup('gid');
        $this->assertSame($group, $result);
    }

    public function testGetUserGroupsDelegatesToRepository(): void
    {
        $groups = [new Group('G1', 'u'), new Group('G2', 'u')];
        $this->groupRepoMock->expects($this->once())->method('findByUser')->with('uid')->willReturn($groups);
        $result = $this->service->getUserGroups('uid');
        $this->assertSame($groups, $result);
    }

    public function testGetPublicGroupsDelegates(): void
    {
        $groups = [new Group('P1', 'u', null, null, true)];
        $this->groupRepoMock->expects($this->once())->method('findPublicGroups')->with(50, 0)->willReturn($groups);
        $result = $this->service->getPublicGroups();
        $this->assertSame($groups, $result);
    }

    public function testGetCommunitiesDelegates(): void
    {
        $groups = [new Group('C1', 'u', null, null, false, true)];
        $this->groupRepoMock->expects($this->once())->method('findCommunities')->with(50, 0)->willReturn($groups);
        $result = $this->service->getCommunities();
        $this->assertSame($groups, $result);
    }

    public function testGetChannelsDelegates(): void
    {
        $groups = [new Group('Ch1', 'u', null, null, false, false, true)];
        $this->groupRepoMock->expects($this->once())->method('findChannels')->with(50, 0)->willReturn($groups);
        $result = $this->service->getChannels();
        $this->assertSame($groups, $result);
    }

    public function testSearchGroupsDelegates(): void
    {
        $groups = [new Group('Match', 'u')];
        $this->groupRepoMock->expects($this->once())->method('searchGroups')->with('query', 20)->willReturn($groups);
        $result = $this->service->searchGroups('query');
        $this->assertSame($groups, $result);
    }

    public function testIsMemberWhenMemberExistsAndActive(): void
    {
        $member = $this->createMock(GroupMember::class);
        $member->method('isActive')->willReturn(true);
        $this->memberRepoMock->expects($this->once())->method('findByGroupAndUser')->with('gid', 'uid')->willReturn($member);
        $result = $this->service->isMember('gid', 'uid');
        $this->assertTrue($result);
    }

    public function testIsMemberWhenMemberInactiveReturnsFalse(): void
    {
        $member = $this->createMock(GroupMember::class);
        $member->method('isActive')->willReturn(false);
        $this->memberRepoMock->expects($this->once())->method('findByGroupAndUser')->with('gid', 'uid')->willReturn($member);
        $result = $this->service->isMember('gid', 'uid');
        $this->assertFalse($result);
    }

    public function testIsMemberWhenNoMemberReturnsFalse(): void
    {
        $this->memberRepoMock->expects($this->once())->method('findByGroupAndUser')->with('gid', 'uid')->willReturn(null);
        $result = $this->service->isMember('gid', 'uid');
        $this->assertFalse($result);
    }

    public function testCanManageWhenMemberHasPermission(): void
    {
        $member = $this->createMock(GroupMember::class);
        $member->method('isActive')->willReturn(true);
        $member->method('canManageGroup')->willReturn(true);
        $this->memberRepoMock->expects($this->once())->method('findByGroupAndUser')->with('gid', 'uid')->willReturn($member);
        $result = $this->service->canManage('gid', 'uid');
        $this->assertTrue($result);
    }

    public function testCanManageWhenMemberCannotManageReturnsFalse(): void
    {
        $member = $this->createMock(GroupMember::class);
        $member->method('isActive')->willReturn(true);
        $member->method('canManageGroup')->willReturn(false);
        $this->memberRepoMock->expects($this->once())->method('findByGroupAndUser')->with('gid', 'uid')->willReturn($member);
        $result = $this->service->canManage('gid', 'uid');
        $this->assertFalse($result);
    }

    public function testCanManageWhenInactiveMemberReturnsFalse(): void
    {
        $member = $this->createMock(GroupMember::class);
        $member->method('isActive')->willReturn(false);
        $member->method('canManageGroup')->willReturn(true); // not called
        $this->memberRepoMock->expects($this->once())->method('findByGroupAndUser')->with('gid', 'uid')->willReturn($member);
        $result = $this->service->canManage('gid', 'uid');
        $this->assertFalse($result);
    }

    public function testCanManageWhenNoMemberReturnsFalse(): void
    {
        $this->memberRepoMock->expects($this->once())->method('findByGroupAndUser')->with('gid', 'uid')->willReturn(null);
        $result = $this->service->canManage('gid', 'uid');
        $this->assertFalse($result);
    }
}
