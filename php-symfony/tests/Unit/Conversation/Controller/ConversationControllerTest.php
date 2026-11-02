<?php

declare(strict_types=1);

namespace Tests\Unit\Conversation\Controller;

use App\Conversation\Controller\ConversationController;
use App\Conversation\Service\ConversationService;
use App\Conversation\Entity\Conversation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ConversationControllerTest extends TestCase
{
    private ConversationService $serviceMock;
    private ConversationController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(ConversationService::class);
        $this->controller = new ConversationController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setTokenStorageUser(string $userId = '123'): void
    {
        $user = new class($userId) implements \Symfony\Component\Security\Core\User\UserInterface {
            private string $id;
            public function __construct(string $id) { $this->id = $id; }
            public function getId(): string { return $this->id; }
            public function getUserIdentifier(): string { return $this->id; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function getPassword(): ?string { return null; }
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
        };
        $this->controller->setTestUser($user);
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function createConversation(int $id, array $participants, string $type = 'direct', ?string $title = null, ?int $createdBy = null): Conversation
    {
        $conv = new Conversation($participants, $type);
        $conv->setId($id);
        if ($title) $conv->setTitle($title);
        if ($createdBy) $conv->setCreatedBy($createdBy);
        return $conv;
    }

    // ==================== Validation Helpers ====================

    public function testValidateConversationData_MissingParticipants_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one participant ID is required');
        $this->invokePrivate($this->controller, 'validateConversationData', [['type' => 'direct']]);
    }

    public function testValidateConversationData_InvalidType_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Conversation type must be either direct or group');
        $this->invokePrivate($this->controller, 'validateConversationData', [['participant_ids' => [1], 'type' => 'invalid']]);
    }

    public function testValidateConversationData_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateConversationData', [['participant_ids' => [1,2], 'type' => 'direct']]);
        $this->assertTrue(true);
    }

    public function testValidateParticipantIds_Empty_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one participant ID is required');
        $this->invokePrivate($this->controller, 'validateParticipantIds', [[]]);
    }

    public function testValidateParticipantIds_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateParticipantIds', [[1,2]]);
        $this->assertTrue(true);
    }

    public function testValidateConversationType_Invalid_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Conversation type must be either direct or group');
        $this->invokePrivate($this->controller, 'validateConversationType', ['invalid']);
    }

    public function testValidateConversationType_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateConversationType', ['group']);
        $this->assertTrue(true);
    }

    public function testValidateTitleData_TooLong_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Title must not exceed 255 characters');
        $this->invokePrivate($this->controller, 'validateTitleData', [['title' => str_repeat('a', 256)]]);
    }

    public function testValidateTitleData_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateTitleData', [['title' => 'Valid']]);
        $this->assertTrue(true);
    }

    public function testValidateAvatarData_InvalidFormat_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Avatar must be a valid URL');
        $this->invokePrivate($this->controller, 'validateAvatarData', [['avatar' => 'invalid-url']]);
    }

    public function testValidateAvatarData_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateAvatarData', [['avatar' => 'http://example.com/avatar.jpg']]);
        $this->assertTrue(true);
    }

    public function testValidateParticipantData_Invalid_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->invokePrivate($this->controller, 'validateParticipantData', [['participants' => 'not array']]);
    }

    public function testValidateParticipantData_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateParticipantData', [['user_id' => 1]]);
        $this->assertTrue(true);
    }

    public function testValidatePositiveInt_TooSmall_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ID must be at least 1');
        $this->invokePrivate($this->controller, 'validatePositiveInt', [0, 'ID']);
    }

    public function testValidatePositiveInt_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validatePositiveInt', [1, 'ID']);
        $this->assertTrue(true);
    }

    // ==================== createConversation ====================

    public function testCreateConversation_Unauthenticated_Returns401(): void
    {
        $payload = json_encode(['participant_ids' => [1], 'type' => 'direct']);
        $request = Request::create('/api/conversations', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->createConversation($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCreateConversation_InvalidJson_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $request = Request::create('/api/conversations', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid{');
        $response = $this->controller->createConversation($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateConversation_ValidationError_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['participant_ids' => [], 'type' => 'direct']);
        $request = Request::create('/api/conversations', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->createConversation($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateConversation_Success_Returns201(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['participant_ids' => [1,2], 'type' => 'group', 'title' => 'Team Chat']);
        $request = Request::create('/api/conversations', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $conv = $this->createConversation(1, [1,2], 'group', 'Team Chat', 1);
        $this->serviceMock->expects($this->once())
            ->method('createConversation')
            ->with([1,2], 'group', 'Team Chat')
            ->willReturn($conv);
        $response = $this->controller->createConversation($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    // ==================== getConversation ====================

    public function testGetConversation_InvalidId_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $request = Request::create('/api/conversations/0', 'GET');
        $response = $this->controller->getConversation(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetConversation_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/conversations/1', 'GET');
        $response = $this->controller->getConversation(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetConversation_NotFound_Returns404(): void
    {
        $this->setTokenStorageUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn(null);
        $request = Request::create('/api/conversations/1', 'GET');
        $response = $this->controller->getConversation(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetConversation_AccessDenied_Returns403(): void
    {
        $this->setTokenStorageUser('1');
        $conv = $this->createConversation(1, [2,3], 'group', 'Team', 2);
        $this->serviceMock->method('getConversation')->willReturn($conv);
        $request = Request::create('/api/conversations/1', 'GET');
        $response = $this->controller->getConversation(1, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetConversation_Success_Returns200(): void
    {
        $this->setTokenStorageUser('1');
        $conv = $this->createConversation(1, [1,2], 'group', 'Team', 1);
        $this->serviceMock->method('getConversation')->willReturn($conv);
        $request = Request::create('/api/conversations/1', 'GET');
        $response = $this->controller->getConversation(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getUserConversations ====================

    public function testGetUserConversations_InvalidUserId_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $request = Request::create('/api/conversations/user/0', 'GET');
        $response = $this->controller->getUserConversations(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetUserConversations_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/conversations/user/1', 'GET');
        $response = $this->controller->getUserConversations(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetUserConversations_AccessDenied_Returns403(): void
    {
        $this->setTokenStorageUser('1');
        $request = Request::create('/api/conversations/user/2', 'GET');
        $response = $this->controller->getUserConversations(2, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetUserConversations_Success_Returns200(): void
    {
        $this->setTokenStorageUser('1');
        $convs = [$this->createConversation(1, [1], 'direct', null, 1)];
        $this->serviceMock->method('getUserConversations')->with(1)->willReturn($convs);
        $request = Request::create('/api/conversations/user/1', 'GET');
        $response = $this->controller->getUserConversations(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== updateTitle ====================

    public function testUpdateTitle_InvalidId_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['title' => 'New Title']);
        $request = Request::create('/api/conversations/0/title', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->updateTitle(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateTitle_Unauthenticated_Returns401(): void
    {
        $payload = json_encode(['title' => 'New Title']);
        $request = Request::create('/api/conversations/1/title', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->updateTitle(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUpdateTitle_InvalidJson_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $existing = $this->createConversation(1, [1], 'group', 'Old', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $request = Request::create('/api/conversations/1/title', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->updateTitle(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateTitle_NotFound_Returns404(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['title' => 'New']);
        $request = Request::create('/api/conversations/1/title', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $existing = $this->createConversation(1, [1], 'group', 'Old', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $this->serviceMock->expects($this->once())
            ->method('updateTitle')
            ->with(1, 'New')
            ->willReturn(null);
        $response = $this->controller->updateTitle(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateTitle_Success_Returns200(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['title' => 'New Title']);
        $request = Request::create('/api/conversations/1/title', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $existing = $this->createConversation(1, [1], 'group', 'Old Title', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $updated = $this->createConversation(1, [1], 'group', 'New Title', 1);
        $this->serviceMock->expects($this->once())
            ->method('updateTitle')
            ->with(1, 'New Title')
            ->willReturn($updated);
        $response = $this->controller->updateTitle(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== updateAvatar ====================

    public function testUpdateAvatar_InvalidId_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['avatar' => 'http://example.com/avatar.png']);
        $request = Request::create('/api/conversations/0/avatar', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->updateAvatar(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateAvatar_Unauthenticated_Returns401(): void
    {
        $payload = json_encode(['avatar' => 'http://example.com/avatar.png']);
        $request = Request::create('/api/conversations/1/avatar', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->updateAvatar(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUpdateAvatar_InvalidJson_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $existing = $this->createConversation(1, [1], 'group', 'Team', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $request = Request::create('/api/conversations/1/avatar', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->updateAvatar(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateAvatar_NotFound_Returns404(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['avatar' => 'http://example.com/avatar.png']);
        $request = Request::create('/api/conversations/1/avatar', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $existing = $this->createConversation(1, [1], 'group', 'Team', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $this->serviceMock->expects($this->once())
            ->method('updateAvatar')
            ->with(1, 'http://example.com/avatar.png')
            ->willReturn(null);
        $response = $this->controller->updateAvatar(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateAvatar_Success_Returns200(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['avatar' => 'http://example.com/avatar.png']);
        $request = Request::create('/api/conversations/1/avatar', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $existing = $this->createConversation(1, [1], 'group', 'Team', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $conv = $this->createConversation(1, [1], 'group', 'Team', 1);
        $conv->setAvatar('http://example.com/avatar.png');
        $this->serviceMock->expects($this->once())
            ->method('updateAvatar')
            ->with(1, 'http://example.com/avatar.png')
            ->willReturn($conv);
        $response = $this->controller->updateAvatar(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== addParticipant ====================

    public function testAddParticipant_InvalidId_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['user_id' => 2]);
        $request = Request::create('/api/conversations/0/participants', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->addParticipant(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddParticipant_Unauthenticated_Returns401(): void
    {
        $payload = json_encode(['user_id' => 2]);
        $request = Request::create('/api/conversations/1/participants', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->addParticipant(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAddParticipant_InvalidJson_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $existing = $this->createConversation(1, [1], 'group', 'Team', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $request = Request::create('/api/conversations/1/participants', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->addParticipant(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddParticipant_ValidationError_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $existing = $this->createConversation(1, [1], 'group', 'Team', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $payload = json_encode(['user_id' => 'abc']);
        $request = Request::create('/api/conversations/1/participants', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->addParticipant(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddParticipant_ConversationNotFound_Returns404(): void
    {
        $this->setTokenStorageUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn(null);
        $payload = json_encode(['user_id' => 2]);
        $request = Request::create('/api/conversations/1/participants', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->addParticipant(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAddParticipant_Success_Returns200(): void
    {
        $this->setTokenStorageUser('1');
        $payload = json_encode(['user_id' => 2]);
        $request = Request::create('/api/conversations/1/participants', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $existing = $this->createConversation(1, [1], 'group', 'Team', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $conv = $this->createConversation(1, [1,2], 'group', 'Team', 1);
        $this->serviceMock->expects($this->once())
            ->method('addParticipant')
            ->with(1, 2)
            ->willReturn($conv);
        $response = $this->controller->addParticipant(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== removeParticipant ====================

    public function testRemoveParticipant_InvalidIds_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $request = Request::create('/api/conversations/0/participants/0', 'DELETE');
        $response = $this->controller->removeParticipant(0, 0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRemoveParticipant_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/conversations/1/participants/2', 'DELETE');
        $response = $this->controller->removeParticipant(1, 2, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testRemoveParticipant_NotFound_Returns404(): void
    {
        $this->setTokenStorageUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn(null);
        $request = Request::create('/api/conversations/1/participants/2', 'DELETE');
        $response = $this->controller->removeParticipant(1, 2, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRemoveParticipant_Success_Returns200(): void
    {
        $this->setTokenStorageUser('1');
        $existing = $this->createConversation(1, [1], 'group', 'Team', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $conv = $this->createConversation(1, [1], 'group', 'Team', 1);
        $this->serviceMock->expects($this->once())
            ->method('removeParticipant')
            ->with(1, 2)
            ->willReturn($conv);
        $request = Request::create('/api/conversations/1/participants/2', 'DELETE');
        $response = $this->controller->removeParticipant(1, 2, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== deleteConversation ====================

    public function testDeleteConversation_InvalidId_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $request = Request::create('/api/conversations/0', 'DELETE');
        $response = $this->controller->deleteConversation(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testDeleteConversation_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/conversations/1', 'DELETE');
        $response = $this->controller->deleteConversation(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testDeleteConversation_Success_Returns204(): void
    {
        $this->setTokenStorageUser('1');
        $existing = $this->createConversation(1, [1], 'group', 'Team', 1);
        $this->serviceMock->expects($this->once())
            ->method('getConversation')
            ->with(1)
            ->willReturn($existing);
        $this->serviceMock->expects($this->once())
            ->method('deleteConversation')
            ->with(1)
            ->willReturn(true);
        $request = Request::create('/api/conversations/1', 'DELETE');
        $response = $this->controller->deleteConversation(1, $request);
        $this->assertSame(204, $response->getStatusCode());
    }

    // ==================== searchConversations ====================

    public function testSearchConversations_InvalidUserId_Returns400(): void
    {
        $this->setTokenStorageUser('1');
        $request = Request::create('/api/conversations/user/0/search', 'GET', ['q' => 'team']);
        $response = $this->controller->searchConversations(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSearchConversations_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/conversations/user/1/search', 'GET', ['q' => 'team']);
        $response = $this->controller->searchConversations(1, $request);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testSearchConversations_MissingQuery_Returns200(): void
    {
        $this->setTokenStorageUser('1');
        $request = Request::create('/api/conversations/user/1/search', 'GET');
        $this->serviceMock->method('searchConversations')->with(1, '')->willReturn([]);
        $response = $this->controller->searchConversations(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSearchConversations_Success_Returns200(): void
    {
        $this->setTokenStorageUser('1');
        $conv = $this->createConversation(1, [1], 'group', 'Team Chat', 1);
        $this->serviceMock->method('searchConversations')->with(1, 'team')->willReturn([$conv]);
        $request = Request::create('/api/conversations/user/1/search', 'GET', ['q' => 'team']);
        $response = $this->controller->searchConversations(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }
}
