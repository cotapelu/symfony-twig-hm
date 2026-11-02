<?php declare(strict_types=1);

namespace Tests\Unit\Groups\Controller;

use App\Groups\Controller\GroupMemberController;
use App\Groups\Service\GroupMemberService;
use App\Groups\Service\GroupService;
use App\Groups\Entity\GroupMember;
use App\Groups\Entity\Group;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use PHPUnit\Framework\TestCase;

class GroupMemberControllerTest extends TestCase
{
    private GroupMemberService $memberServiceMock;
    private GroupService $groupServiceMock;
    private GroupMemberController $controller;

    protected function setUp(): void
    {
        $this->memberServiceMock = $this->createMock(GroupMemberService::class);
        $this->groupServiceMock = $this->createMock(GroupService::class);
        $this->controller = new GroupMemberController($this->memberServiceMock, $this->groupServiceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setAuthenticatedUser(string $userId): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn($userId);
        $this->controller->setTestUser($user);
    }

    private function createGroupMemberMock(array $data): GroupMember
    {
        $member = $this->createMock(GroupMember::class);
        $member->method('toArray')->willReturn($data);
        $member->method('getRole')->willReturn($data['role'] ?? GroupMember::ROLE_MEMBER);
        return $member;
    }

    private function createGroupMock(string $id, ?int $maxSize = null): Group
    {
        $group = $this->createMock(Group::class);
        $group->method('getId')->willReturn($id);
        $group->method('getMaxMembers')->willReturn($maxSize ?? 100);
        return $group;
    }

    private function jsonRequest(string $method, string $uri, array $query = [], array $body = []): Request
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode($body);
        return Request::create($uri, $method, $query, [], [], $server, $content);
    }

    // ==================== join ====================

    public function testJoin_RequiresAuth(): void
    {
        $request = $this->jsonRequest('POST', '/api/groups/g1/members', [], []);
        $response = $this->controller->join('g1', $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Authentication required', $data['error']);
    }

    public function testJoin_InvalidGroupId(): void
    {
        $this->setAuthenticatedUser('user1');
        $response = $this->controller->join('', $this->jsonRequest('POST', '/api/groups//members', [], []));
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('ID cannot be empty', $response->getContent());
    }

    public function testJoin_GroupNotFound(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->expects($this->once())
            ->method('getGroup')
            ->with($this->equalTo('g1'))
            ->willReturn(null);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members', [], []);
        $response = $this->controller->join('g1', $request);
        $this->assertSame(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Group not found', $data['error']);
    }

    public function testJoin_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $group = $this->createGroupMock('g1');
        $this->groupServiceMock->expects($this->once())
            ->method('getGroup')
            ->with($this->equalTo('g1'))
            ->willReturn($group);
        $member = $this->createGroupMemberMock(['user_id' => 'user1', 'role' => GroupMember::ROLE_MEMBER]);
        $this->memberServiceMock->expects($this->once())
            ->method('joinGroup')
            ->with($group, 'user1', GroupMember::ROLE_MEMBER)
            ->willReturn($member);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members', [], []);
        $response = $this->controller->join('g1', $request);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('member', $data);
    }

    public function testJoin_WithRole(): void
    {
        $this->setAuthenticatedUser('user1');
        $group = $this->createGroupMock('g1');
        $this->groupServiceMock->method('getGroup')->willReturn($group);
        $member = $this->createGroupMemberMock(['user_id' => 'user1', 'role' => GroupMember::ROLE_ADMIN]);
        $this->memberServiceMock->expects($this->once())
            ->method('joinGroup')
            ->with($group, 'user1', GroupMember::ROLE_ADMIN)
            ->willReturn($member);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members', [], ['role' => 'admin']);
        $response = $this->controller->join('g1', $request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testJoin_InvalidRole(): void
    {
        $this->setAuthenticatedUser('user1');
        $group = $this->createGroupMock('g1');
        $this->groupServiceMock->method('getGroup')->willReturn($group);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members', [], ['role' => 'invalid']);
        $response = $this->controller->join('g1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid role', $response->getContent());
    }

    public function testJoin_AlreadyMemberOrFull(): void
    {
        $this->setAuthenticatedUser('user1');
        $group = $this->createGroupMock('g1');
        $this->groupServiceMock->method('getGroup')->willReturn($group);
        $this->memberServiceMock->expects($this->once())
            ->method('joinGroup')
            ->willReturn(false);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members', [], []);
        $response = $this->controller->join('g1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Cannot join group (maybe already a member or group full)', $data['error']);
    }

    // ==================== leave ====================

    public function testLeave_RequiresAuth(): void
    {
        $request = $this->jsonRequest('DELETE', '/api/groups/g1/members/user1', []);
        $response = $this->controller->leave('g1', 'user1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testLeave_CannotLeaveForAnotherUser(): void
    {
        $this->setAuthenticatedUser('user1');
        $request = $this->jsonRequest('DELETE', '/api/groups/g1/members/user2', []);
        $response = $this->controller->leave('g1', 'user2', $request);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Cannot leave for another user', json_decode($response->getContent(), true)['error']);
    }

    public function testLeave_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->memberServiceMock->expects($this->once())
            ->method('leaveGroup')
            ->with('g1', 'user1')
            ->willReturn(true);
        $request = $this->jsonRequest('DELETE', '/api/groups/g1/members/user1', []);
        $response = $this->controller->leave('g1', 'user1', $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Left group successfully', json_decode($response->getContent(), true)['message']);
    }

    public function testLeave_NotMember(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->memberServiceMock->expects($this->once())
            ->method('leaveGroup')
            ->willReturn(false);
        $request = $this->jsonRequest('DELETE', '/api/groups/g1/members/user1', []);
        $response = $this->controller->leave('g1', 'user1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Cannot leave group (maybe not a member)', $response->getContent());
    }

    // ==================== list members ====================

    public function testList_InvalidGroupId(): void
    {
        $this->setAuthenticatedUser('user1');
        $response = $this->controller->list('');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testList_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $member1 = $this->createGroupMemberMock(['user_id' => 'u1', 'role' => GroupMember::ROLE_ADMIN]);
        $member2 = $this->createGroupMemberMock(['user_id' => 'u2', 'role' => GroupMember::ROLE_MEMBER]);
        $this->memberServiceMock->expects($this->once())
            ->method('getGroupMembers')
            ->with($this->equalTo('g1'))
            ->willReturn([$member1, $member2]);
        $response = $this->controller->list('g1');
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['members']);
        $this->assertSame(2, $data['total']);
    }

    // ==================== changeRole ====================

    public function testChangeRole_RequiresAuth(): void
    {
        $request = $this->jsonRequest('PUT', '/api/groups/g1/members/u1/role', [], ['role' => 'admin']);
        $response = $this->controller->changeRole('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testChangeRole_AccessDenied(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->expects($this->once())
            ->method('canManage')
            ->with($this->equalTo('g1'), $this->equalTo('user1'))
            ->willReturn(false);
        $request = $this->jsonRequest('PUT', '/api/groups/g1/members/u1/role', [], ['role' => 'admin']);
        $response = $this->controller->changeRole('g1', 'u1', $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testChangeRole_MissingRole(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $request = $this->jsonRequest('PUT', '/api/groups/g1/members/u1/role', [], []);
        $response = $this->controller->changeRole('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Role is required', json_decode($response->getContent(), true)['error']);
    }

    public function testChangeRole_InvalidRole(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $request = $this->jsonRequest('PUT', '/api/groups/g1/members/u1/role', [], ['role' => 'badrole']);
        $response = $this->controller->changeRole('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid role', $response->getContent());
    }

    public function testChangeRole_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $this->memberServiceMock->expects($this->once())
            ->method('changeRole')
            ->with('g1', 'u1', GroupMember::ROLE_MODERATOR, 'user1')
            ->willReturn(true);
        $request = $this->jsonRequest('PUT', '/api/groups/g1/members/u1/role', [], ['role' => 'moderator']);
        $response = $this->controller->changeRole('g1', 'u1', $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testChangeRole_Failure(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $this->memberServiceMock->expects($this->once())
            ->method('changeRole')
            ->willReturn(false);
        $request = $this->jsonRequest('PUT', '/api/groups/g1/members/u1/role', [], ['role' => 'member']);
        $response = $this->controller->changeRole('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== ban ====================

    public function testBan_RequiresAuth(): void
    {
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/ban', []);
        $response = $this->controller->ban('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testBan_AccessDenied(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->expects($this->once())
            ->method('canManage')
            ->with('g1', 'user1')
            ->willReturn(false);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/ban', []);
        $response = $this->controller->ban('g1', 'u1', $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testBan_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $this->memberServiceMock->expects($this->once())
            ->method('banMember')
            ->with('g1', 'u1', 'user1')
            ->willReturn(true);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/ban', []);
        $response = $this->controller->ban('g1', 'u1', $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testBan_Failure(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $this->memberServiceMock->expects($this->once())
            ->method('banMember')
            ->willReturn(false);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/ban', []);
        $response = $this->controller->ban('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== unban ====================

    public function testUnban_RequiresAuth(): void
    {
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/unban', []);
        $response = $this->controller->unban('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUnban_AccessDenied(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(false);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/unban', []);
        $response = $this->controller->unban('g1', 'u1', $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUnban_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $this->memberServiceMock->expects($this->once())
            ->method('unbanMember')
            ->with('g1', 'u1', 'user1')
            ->willReturn(true);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/unban', []);
        $response = $this->controller->unban('g1', 'u1', $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUnban_Failure(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $this->memberServiceMock->expects($this->once())
            ->method('unbanMember')
            ->willReturn(false);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/unban', []);
        $response = $this->controller->unban('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== mute ====================

    public function testMute_RequiresAuth(): void
    {
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/mute', []);
        $response = $this->controller->mute('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testMute_AccessDenied(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(false);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/mute', []);
        $response = $this->controller->mute('g1', 'u1', $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testMute_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $this->memberServiceMock->expects($this->once())
            ->method('muteMember')
            ->with('g1', 'u1', 'user1')
            ->willReturn(true);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/mute', []);
        $response = $this->controller->mute('g1', 'u1', $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMute_Failure(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $this->memberServiceMock->expects($this->once())
            ->method('muteMember')
            ->willReturn(false);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/mute', []);
        $response = $this->controller->mute('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== unmute ====================

    public function testUnmute_RequiresAuth(): void
    {
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/unmute', []);
        $response = $this->controller->unmute('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUnmute_AccessDenied(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(false);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/unmute', []);
        $response = $this->controller->unmute('g1', 'u1', $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUnmute_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $this->memberServiceMock->expects($this->once())
            ->method('unmuteMember')
            ->with('g1', 'u1', 'user1')
            ->willReturn(true);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/unmute', []);
        $response = $this->controller->unmute('g1', 'u1', $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUnmute_Failure(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->groupServiceMock->method('canManage')->willReturn(true);
        $this->memberServiceMock->expects($this->once())
            ->method('unmuteMember')
            ->willReturn(false);
        $request = $this->jsonRequest('POST', '/api/groups/g1/members/u1/unmute', []);
        $response = $this->controller->unmute('g1', 'u1', $request);
        $this->assertSame(400, $response->getStatusCode());
    }
}
