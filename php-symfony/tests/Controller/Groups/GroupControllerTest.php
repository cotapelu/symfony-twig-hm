<?php

declare(strict_types=1);

namespace App\Tests\Controller\Groups;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Groups\Controller\GroupController;
use App\Groups\Service\GroupService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class GroupControllerTest extends TestCase
{
    private GroupController $controller;
    private GroupService|MockObject $groupService;

    protected function setUp(): void
    {
        $this->groupService = $this->createMock(GroupService::class);
        $this->controller = new GroupController($this->groupService);
    }

    private function setAuthenticatedUser(string $userId): void
    {
        $user = new class($userId) implements \Symfony\Component\Security\Core\User\UserInterface {
            private string $uid;
            public function __construct(string $uid) { $this->uid = $uid; }
            public function getUserIdentifier(): string { return $this->uid; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function eraseCredentials() {}
            public function getId(): string { return $this->uid; }
        };
        $this->controller->setTestUser($user);
    }

    public function testCreateGroupSuccess(): void
    {
        $this->setAuthenticatedUser('user123');
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Test Group',
                'description' => 'Test description',
                'avatar' => 'https://example.com/avatar',
                'isPublic' => true,
                'maxMembers' => 500,
                'settings' => ['setting1' => 'value1'],
            ])
        );

        $group = $this->createMockGroup('group123', 'Test Group');

        $this->groupService
            ->expects($this->once())
            ->method('createGroup')
            ->with(
                'Test Group',
                'user123',
                'Test description',
                'https://example.com/avatar',
                true,
                false,
                false,
                false,
                500,
                ['setting1' => 'value1']
            )
            ->willReturn($group);

        $result = $this->controller->create($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('group123', $data['group']['id']);
        $this->assertEquals('Test Group', $data['group']['name']);
    }

    public function testCreateGroupInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->create($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testCreateGroupMissingOwnerId(): void
    {
        $this->setAuthenticatedUser('user123');
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test Group'])
        );

        $this->groupService
            ->expects($this->once())
            ->method('createGroup')
            ->with(
                'Test Group',
                'user123',
                null,
                null,
                false,
                false,
                false,
                false,
                500,
                null
            )
            ->willReturn($this->createMockGroup('g1', 'Test Group'));

        $result = $this->controller->create($request);

        $this->assertEquals(201, $result->getStatusCode());
    }

    public function testCreateGroupWithMinimalFields(): void
    {
        $this->setAuthenticatedUser('user123');
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Group Name',
            ])
        );

        $group = $this->createMockGroup('group456', 'Group Name');

        $this->groupService
            ->expects($this->once())
            ->method('createGroup')
            ->with(
                'Group Name',
                'user123',
                null,
                null,
                false,
                false,
                false,
                false,
                500,
                null
            )
            ->willReturn($group);

        $result = $this->controller->create($request);

        $this->assertEquals(201, $result->getStatusCode());
    }

    public function testUpdateGroupSuccess(): void
    {
        $this->setAuthenticatedUser('user123');
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Updated Name', 'description' => 'Updated description'])
        );

        $existingGroup = $this->createMock(\App\Groups\Entity\Group::class);
        $existingGroup->method('getOwnerId')->willReturn('user123');
        $this->groupService->method('getGroup')->with('group123')->willReturn($existingGroup);

        $group = $this->createMockGroup('group123', 'Updated Name');

        $this->groupService
            ->expects($this->once())
            ->method('updateGroup')
            ->with('group123', ['name' => 'Updated Name', 'description' => 'Updated description'])
            ->willReturn($group);

        $result = $this->controller->update('group123', $request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('group123', $data['group']['id']);
    }

    public function testUpdateGroupInvalidJson(): void
    {
        $this->setAuthenticatedUser('user123');
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $existingGroup = $this->createMock(\App\Groups\Entity\Group::class);
        $existingGroup->method('getOwnerId')->willReturn('user123');
        $this->groupService->method('getGroup')->with('group123')->willReturn($existingGroup);

        $result = $this->controller->update('group123', $request);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testUpdateGroupNotFound(): void
    {
        $this->setAuthenticatedUser('user123');
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Updated'])
        );

        $this->groupService->method('getGroup')->with('notfound')->willReturn(null);

        $result = $this->controller->update('notfound', $request);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testDeleteGroupSuccess(): void
    {
        $this->setAuthenticatedUser('user123');
        $group = $this->createMock(\App\Groups\Entity\Group::class);
        $group->method('getOwnerId')->willReturn('user123');
        $this->groupService->method('getGroup')->with('group123')->willReturn($group);
        $this->groupService
            ->expects($this->once())
            ->method('deleteGroup')
            ->with('group123')
            ->willReturn(true);

        $result = $this->controller->delete('group123', new Request());

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Group deleted successfully', $data['message']);
    }

    public function testDeleteGroupNotFound(): void
    {
        $this->setAuthenticatedUser('user123');
        $this->groupService->method('getGroup')->with('notfound')->willReturn(null);

        $result = $this->controller->delete('notfound', new Request());

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testShowGroupSuccess(): void
    {
        $group = $this->createMockGroup('group123', 'Test Group');

        $this->groupService
            ->expects($this->once())
            ->method('getGroup')
            ->with('group123')
            ->willReturn($group);

        $result = $this->controller->show('group123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('group123', $data['group']['id']);
    }

    public function testShowGroupNotFound(): void
    {
        $this->groupService
            ->expects($this->once())
            ->method('getGroup')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->controller->show('notfound');

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testListPublicGroups(): void
    {
        $request = new Request(['limit' => '10', 'offset' => '0']);

        $groups = [
            $this->createMockGroup('group1', 'Public Group 1'),
            $this->createMockGroup('group2', 'Public Group 2'),
        ];

        $this->groupService
            ->expects($this->once())
            ->method('getPublicGroups')
            ->with(10, 0)
            ->willReturn($groups);

        $result = $this->controller->listPublic($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data['groups']);
        $this->assertEquals(2, $data['total']);
    }

    public function testListPublicGroupsWithDefaults(): void
    {
        $request = new Request();

        $groups = [];

        $this->groupService
            ->expects($this->once())
            ->method('getPublicGroups')
            ->with(50, 0)
            ->willReturn($groups);

        $result = $this->controller->listPublic($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testListCommunities(): void
    {
        $request = new Request(['limit' => '20', 'offset' => '10']);

        $groups = [$this->createMockGroup('comm1', 'Community 1')];

        $this->groupService
            ->expects($this->once())
            ->method('getCommunities')
            ->with(20, 10)
            ->willReturn($groups);

        $result = $this->controller->listCommunities($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(1, $data['groups']);
    }

    public function testListChannels(): void
    {
        $request = new Request(['limit' => '15']);

        $groups = [$this->createMockGroup('chan1', 'Channel 1')];

        $this->groupService
            ->expects($this->once())
            ->method('getChannels')
            ->with(15, 0)
            ->willReturn($groups);

        $result = $this->controller->listChannels($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetUserGroups(): void
    {
        $this->setAuthenticatedUser('user123');
        $groups = [
            $this->createMockGroup('group1', 'User Group 1'),
            $this->createMockGroup('group2', 'User Group 2'),
        ];

        $this->groupService
            ->expects($this->once())
            ->method('getUserGroups')
            ->with('user123')
            ->willReturn($groups);

        $result = $this->controller->getUserGroups('user123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data['groups']);
        $this->assertEquals(2, $data['total']);
    }

    public function testSearchGroups(): void
    {
        $request = new Request(['q' => 'test', 'limit' => '10']);

        $groups = [$this->createMockGroup('group1', 'Test Group')];

        $this->groupService
            ->expects($this->once())
            ->method('searchGroups')
            ->with('test', 10)
            ->willReturn($groups);

        $result = $this->controller->search($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(1, $data['groups']);
    }

    public function testSearchGroupsEmptyQuery(): void
    {
        $request = new Request(['q' => '']);

        $result = $this->controller->search($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Search query is required', $data['error']);
    }

    public function testSearchGroupsWithDefaultLimit(): void
    {
        $request = new Request(['q' => 'search query']);

        $groups = [];

        $this->groupService
            ->expects($this->once())
            ->method('searchGroups')
            ->with('search query', 20)
            ->willReturn($groups);

        $result = $this->controller->search($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testCreateCommunityGroup(): void
    {
        $this->setAuthenticatedUser('user123');
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'ownerId' => 'user123',
                'name' => 'Community Group',
                'isCommunity' => true,
                'isPublic' => true,
            ])
        );

        $group = $this->createMockGroup('comm1', 'Community Group');

        $this->groupService
            ->expects($this->once())
            ->method('createGroup')
            ->with(
                'Community Group',
                'user123', // ownerId from auth
                null, // description
                null, // avatar
                true, // isPublic
                true, // isCommunity
                false, // isChannel
                false, // isForum
                500, // maxMembers
                null // settings
            )
            ->willReturn($group);

        $result = $this->controller->create($request);

        $this->assertEquals(201, $result->getStatusCode());
    }

    public function testCreateChannel(): void
    {
        $this->setAuthenticatedUser('user123');
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'ownerId' => 'user123',
                'name' => 'Channel Name',
                'isChannel' => true,
            ])
        );

        $group = $this->createMockGroup('chan1', 'Channel Name');

        $this->groupService
            ->expects($this->once())
            ->method('createGroup')
            ->with(
                'Channel Name',
                'user123', // ownerId
                null, // description
                null, // avatar
                false, // isPublic
                false, // isCommunity
                true, // isChannel
                false, // isForum
                500, // maxMembers
                null // settings
            )
            ->willReturn($group);

        $result = $this->controller->create($request);

        $this->assertEquals(201, $result->getStatusCode());
    }

    private function createMockGroup(string $id, string $name): MockObject
    {
        $group = $this->createMock(\App\Groups\Entity\Group::class);
        $group->method('toArray')->willReturn([
            'id' => $id,
            'name' => $name,
            'description' => null,
            'isPublic' => false,
            'isCommunity' => false,
            'isChannel' => false,
            'isForum' => false,
            'maxMembers' => 500,
            'memberCount' => 0,
        ]);
        return $group;
    }
}
