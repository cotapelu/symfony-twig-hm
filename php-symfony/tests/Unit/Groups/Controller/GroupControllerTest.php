<?php

declare(strict_types=1);

namespace Tests\Unit\Groups\Controller;

use App\Groups\Controller\GroupController;
use App\Groups\Entity\Group;
use App\Groups\Service\GroupService;
use App\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class GroupControllerTest extends TestCase
{
    private GroupService $groupServiceMock;
    private GroupController $controller;
    private TokenStorageInterface $tokenStorageMock;

    protected function setUp(): void
    {
        $this->groupServiceMock = $this->createMock(GroupService::class);
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->controller = new GroupController($this->groupServiceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setAuthenticatedUser(?User $user = null): void
    {
        $this->controller->setTestUser($user);
        if ($user) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);
            $this->tokenStorageMock->method('getToken')->willReturn($token);
        } else {
            $this->tokenStorageMock->method('getToken')->willReturn(null);
        }
    }

    private function createUser(int $id, string $email): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn((string)$id);
        $user->method('getUserIdentifier')->willReturn($email);
        return $user;
    }

    private function createGroupMock(array $data): Group
    {
        $mock = $this->createMock(Group::class);
        $mock->method('toArray')->willReturn($data);
        if (array_key_exists('id', $data)) {
            $mock->method('getId')->willReturn($data['id']);
        }
        if (array_key_exists('ownerId', $data)) {
            $mock->method('getOwnerId')->willReturn($data['ownerId']);
        }
        return $mock;
    }

    // ==================== create ====================

    public function testCreate_Success(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $group = $this->createGroupMock([
            'id' => 'group-123',
            'name' => 'Test Group',
            'description' => 'A test group',
            'ownerId' => '1',
            'isPublic' => true,
            'isCommunity' => false,
            'isChannel' => false,
            'isForum' => false,
            'maxMembers' => 500,
            'avatar' => null,
            'settings' => null,
            'createdAt' => null,
        ]);

        $this->groupServiceMock->expects($this->once())
            ->method('createGroup')
            ->with(
                $this->equalTo('Test Group'),
                $this->equalTo('1'),
                $this->equalTo('A test group'),
                $this->equalTo(null),
                $this->equalTo(true),
                $this->equalTo(false),
                $this->equalTo(false),
                $this->equalTo(false),
                $this->equalTo(500),
                $this->equalTo(null)
            )
            ->willReturn($group);

        $request = Request::create('/api/groups', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test Group',
            'description' => 'A test group',
            'isPublic' => true,
        ]));

        $response = $this->controller->create($request);
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('group-123', $data['group']['id']);
    }

    public function testCreate_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);

        $request = Request::create('/api/groups', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Test']));

        $response = $this->controller->create($request);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCreate_InvalidJson_Returns400(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/groups', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $response = $this->controller->create($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCreate_MissingName_Returns400(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/groups', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['description' => 'No name']));

        $response = $this->controller->create($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Group name is required', $data['error']);
    }

    public function testCreate_NameTooLong_Returns400(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $longName = str_repeat('a', 256);

        $request = Request::create('/api/groups', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => $longName]));

        $response = $this->controller->create($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('cannot exceed 255 characters', json_decode($response->getContent(), true)['error']);
    }

    public function testCreate_InvalidMaxMembers_Returns400(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/groups', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test',
            'maxMembers' => 0,
        ]));

        $response = $this->controller->create($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('at least 1', json_decode($response->getContent(), true)['error']);
    }

    public function testCreate_InvalidAvatarUrl_Returns400(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/groups', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test',
            'avatar' => 'not-a-url',
        ]));

        $response = $this->controller->create($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('valid URL', json_decode($response->getContent(), true)['error']);
    }

    public function testCreate_ServiceException_Returns500(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $this->groupServiceMock->method('createGroup')
            ->willThrowException(new \Exception('DB error'));

        $request = Request::create('/api/groups', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Test']));

        $response = $this->controller->create($request);
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    // ==================== update ====================

    public function testUpdate_Success(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $group = $this->createGroupMock([
            'id' => 'group-123',
            'name' => 'Updated Group',
            'description' => 'Updated desc',
            'ownerId' => '1',
            'maxMembers' => 1000,
        ]);

        $this->groupServiceMock->expects($this->once())
            ->method('getGroup')
            ->with('group-123')
            ->willReturn($group);

        $this->groupServiceMock->expects($this->once())
            ->method('updateGroup')
            ->with('group-123', $this->arrayHasKey('name'))
            ->willReturn($group);

        $request = Request::create('/api/groups/group-123', 'PATCH', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Updated Group']));

        $response = $this->controller->update('group-123', $request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testUpdate_GroupNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $this->groupServiceMock->method('getGroup')->with('missing')->willReturn(null);

        $request = Request::create('/api/groups/missing', 'PATCH', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Test']));

        $response = $this->controller->update('missing', $request);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testUpdate_NotOwner_Returns403(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $group = $this->createGroupMock([
            'id' => 'group-123',
            'ownerId' => '999', // different owner
        ]);

        $this->groupServiceMock->method('getGroup')->with('group-123')->willReturn($group);

        $request = Request::create('/api/groups/group-123', 'PATCH', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Test']));

        $response = $this->controller->update('group-123', $request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode()); // InvalidArgumentException maps to 400
        $this->assertStringContainsString('Access denied', json_decode($response->getContent(), true)['error']);
    }

    public function testUpdate_InvalidId_Returns400(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/groups/dummy', 'PATCH', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Test']));

        $response = $this->controller->update('   ', $request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testUpdate_UpdateFails_Returns400(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $group = $this->createGroupMock([
            'id' => 'group-123',
            'ownerId' => '1',
        ]);

        $this->groupServiceMock->method('getGroup')->with('group-123')->willReturn($group);
        $this->groupServiceMock->method('updateGroup')->with('group-123', $this->anything())->willReturn(null);

        $request = Request::create('/api/groups/group-123', 'PATCH', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Test']));

        $response = $this->controller->update('group-123', $request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Failed to update group', json_decode($response->getContent(), true)['error']);
    }

    // ==================== delete ====================

    public function testDelete_Success(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $group = $this->createGroupMock([
            'id' => 'group-123',
            'ownerId' => '1',
        ]);

        $this->groupServiceMock->method('getGroup')->with('group-123')->willReturn($group);
        $this->groupServiceMock->method('deleteGroup')->with('group-123')->willReturn(true);

        $response = $this->controller->delete('group-123', new Request());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Group deleted successfully', json_decode($response->getContent(), true)['message']);
    }

    public function testDelete_GroupNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $this->groupServiceMock->method('getGroup')->with('missing')->willReturn(null);

        $response = $this->controller->delete('missing', new Request());
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testDelete_NotOwner_Returns400(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $group = $this->createGroupMock([
            'id' => 'group-123',
            'ownerId' => '999',
        ]);

        $this->groupServiceMock->method('getGroup')->with('group-123')->willReturn($group);

        $response = $this->controller->delete('group-123', new Request());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Access denied', json_decode($response->getContent(), true)['error']);
    }

    public function testDelete_DeleteFails_Returns400(): void
    {
        $user = $this->createUser(1, 'owner@example.com');
        $this->setAuthenticatedUser($user);

        $group = $this->createGroupMock([
            'id' => 'group-123',
            'ownerId' => '1',
        ]);

        $this->groupServiceMock->method('getGroup')->with('group-123')->willReturn($group);
        $this->groupServiceMock->method('deleteGroup')->with('group-123')->willReturn(false);

        $response = $this->controller->delete('group-123', new Request());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Failed to delete group', json_decode($response->getContent(), true)['error']);
    }

    // ==================== show ====================

    public function testShow_Success(): void
    {
        $group = $this->createGroupMock([
            'id' => 'group-123',
            'name' => 'Test Group',
        ]);

        $this->groupServiceMock->method('getGroup')->with('group-123')->willReturn($group);

        $response = $this->controller->show('group-123');
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('group-123', json_decode($response->getContent(), true)['group']['id']);
    }

    public function testShow_NotFound_Returns404(): void
    {
        $this->groupServiceMock->method('getGroup')->with('missing')->willReturn(null);

        $response = $this->controller->show('missing');
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    // ==================== listPublic ====================

    public function testListPublic_Success(): void
    {
        $groups = [
            $this->createGroupMock(['id' => 'g1']),
            $this->createGroupMock(['id' => 'g2']),
        ];

        $this->groupServiceMock->method('getPublicGroups')->with(50, 0)->willReturn($groups);

        $request = Request::create('/api/groups', 'GET');
        $response = $this->controller->listPublic($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['groups']);
        $this->assertSame(2, $data['total']);
    }

    public function testListPublic_Pagination(): void
    {
        $this->groupServiceMock->method('getPublicGroups')->with(10, 20)->willReturn([]);

        $request = Request::create('/api/groups', 'GET', ['limit' => '10', 'offset' => '20']);
        $response = $this->controller->listPublic($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testListPublic_InvalidLimit_Returns400(): void
    {
        $this->groupServiceMock->expects($this->never())->method('getPublicGroups');

        $request = Request::create('/api/groups', 'GET', ['limit' => '0']);
        $response = $this->controller->listPublic($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // ==================== listCommunities ====================

    public function testListCommunities_Success(): void
    {
        $groups = [$this->createGroupMock(['id' => 'c1', 'isCommunity' => true])];
        $this->groupServiceMock->method('getCommunities')->with(50, 0)->willReturn($groups);

        $request = Request::create('/api/groups/communities', 'GET');
        $response = $this->controller->listCommunities($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    // ==================== listChannels ====================

    public function testListChannels_Success(): void
    {
        $groups = [$this->createGroupMock(['id' => 'ch1', 'isChannel' => true])];
        $this->groupServiceMock->method('getChannels')->with(50, 0)->willReturn($groups);

        $request = Request::create('/api/groups/channels', 'GET');
        $response = $this->controller->listChannels($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    // ==================== getUserGroups ====================

    public function testGetUserGroups_Success(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);

        $groups = [$this->createGroupMock(['id' => 'g1'])];
        $this->groupServiceMock->method('getUserGroups')->with('1')->willReturn($groups);

        $response = $this->controller->getUserGroups('1', new Request());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetUserGroups_AccessDenied_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);

        $response = $this->controller->getUserGroups('2', new Request());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testGetUserGroups_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);

        $response = $this->controller->getUserGroups('1', new Request());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    // ==================== search ====================

    public function testSearch_Success(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);

        $groups = [$this->createGroupMock(['id' => 'g1', 'name' => 'Test'])];
        $this->groupServiceMock->method('searchGroups')->with('test', 20)->willReturn($groups);

        $request = Request::create('/api/groups/search', 'GET', ['q' => 'test']);
        $response = $this->controller->search($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testSearch_MissingQuery_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/groups/search', 'GET');
        $response = $this->controller->search($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Search query is required', json_decode($response->getContent(), true)['error']);
    }

    public function testSearch_Public_Returns200(): void
    {
        $this->setAuthenticatedUser(null);

        $groups = [$this->createGroupMock(['id' => 'g1', 'name' => 'Test'])];
        $this->groupServiceMock->expects($this->once())
            ->method('searchGroups')
            ->with('test', 20)
            ->willReturn($groups);

        $request = Request::create('/api/groups/search', 'GET', ['q' => 'test']);
        $response = $this->controller->search($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['groups']);
        $this->assertSame(1, $data['total']);
    }

    // ==================== validation helpers ====================

    public function testValidateName_Empty_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Group name is required');
        (new \ReflectionMethod($this->controller, 'validateName'))->invoke($this->controller, '');
    }

    public function testValidateName_TooLong_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 255 characters');
        (new \ReflectionMethod($this->controller, 'validateName'))->invoke($this->controller, str_repeat('a', 256));
    }

    public function testValidateMaxMembers_TooSmall_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 1');
        (new \ReflectionMethod($this->controller, 'validateMaxMembers'))->invoke($this->controller, 0);
    }

    public function testValidateMaxMembers_TooLarge_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 100000');
        (new \ReflectionMethod($this->controller, 'validateMaxMembers'))->invoke($this->controller, 100001);
    }

    public function testValidateAvatarUrl_Invalid_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('valid URL');
        (new \ReflectionMethod($this->controller, 'validateAvatarUrl'))->invoke($this->controller, 'bad');
    }

    public function testValidateAvatarUrl_Null_Ok(): void
    {
        (new \ReflectionMethod($this->controller, 'validateAvatarUrl'))->invoke($this->controller, null);
        $this->assertTrue(true); // no exception
    }

    public function testValidateId_Empty_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ID cannot be empty');
        (new \ReflectionMethod($this->controller, 'validateId'))->invoke($this->controller, '');
    }

    public function testValidatePositiveInt_TooSmall_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be at least 2');
        (new \ReflectionMethod($this->controller, 'validatePositiveInt'))->invoke($this->controller, 1, 'limit', 2);
    }

    public function testValidatePositiveInt_TooLarge_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not exceed 10');
        (new \ReflectionMethod($this->controller, 'validatePositiveInt'))->invoke($this->controller, 11, 'limit', 1, 10);
    }

    public function testValidatePositiveInt_Valid_Returns(): void
    {
        $method = new \ReflectionMethod($this->controller, 'validatePositiveInt');
        $result = $method->invoke($this->controller, 5, 'limit', 1, 10);
        $this->assertSame(5, $result);
    }
}
