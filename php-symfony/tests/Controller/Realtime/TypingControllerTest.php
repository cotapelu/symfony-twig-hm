<?php

declare(strict_types=1);

namespace App\Tests\Controller\Realtime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Controller\TypingController;
use App\Realtime\Service\TypingIndicatorService;
use App\Shared\Security\AuthenticationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class TypingControllerTest extends TestCase
{
    private TypingController $controller;
    private TypingIndicatorService|MockObject $typingService;
    private AuthenticationService|MockObject $authService;

    protected function setUp(): void
    {
        $this->typingService = $this->createMock(TypingIndicatorService::class);
        $this->authService = $this->createMock(AuthenticationService::class);

        $this->controller = new TypingController($this->typingService, $this->authService);
    }

    public function testStartTypingSuccess(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user123']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['conversationId' => 'conv123'])
        );

        $this->typingService
            ->expects($this->once())
            ->method('startTyping')
            ->with('conv123', 'user123');

        $result = $this->controller->startTyping($request);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testStartTypingNotAuthenticated(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->with($this->anything())
            ->willThrowException(new \Exception('Authentication required'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['conversationId' => 'conv123'])
        );

        $result = $this->controller->startTyping($request);

        $this->assertEquals(401, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Authentication required', $data['error']);
    }

    public function testStartTypingInvalidJson(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user123']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->startTyping($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testStartTypingMissingConversationId(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user123']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $result = $this->controller->startTyping($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Conversation ID required', $data['error']);
    }

    public function testStartTypingNullConversationId(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user123']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['conversationId' => null])
        );

        $result = $this->controller->startTyping($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Conversation ID required', $data['error']);
    }

    public function testStopTypingSuccess(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user123']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['conversationId' => 'conv123'])
        );

        $this->typingService
            ->expects($this->once())
            ->method('stopTyping')
            ->with('conv123', 'user123');

        $result = $this->controller->stopTyping($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testStopTypingNotAuthenticated(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willThrowException(new \Exception('Authentication required'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['conversationId' => 'conv123'])
        );

        $result = $this->controller->stopTyping($request);

        $this->assertEquals(401, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Authentication required', $data['error']);
    }

    public function testStopTypingInvalidJson(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user123']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->stopTyping($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testStopTypingMissingConversationId(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user123']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $result = $this->controller->stopTyping($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Conversation ID required', $data['error']);
    }

    public function testStopTypingNullConversationId(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user123']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['conversationId' => null])
        );

        $result = $this->controller->stopTyping($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Conversation ID required', $data['error']);
    }

    public function testGetTypingStatus(): void
    {
        $typingUsers = [
            ['userId' => 'user222', 'name' => 'User 2'],
            ['userId' => 'user333', 'name' => 'User 3'],
        ];

        $this->typingService
            ->expects($this->once())
            ->method('getTypingUsers')
            ->with('conv123')
            ->willReturn($typingUsers);

        $result = $this->controller->getTypingStatus('conv123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('conv123', $data['conversationId']);
        $this->assertCount(2, $data['typingUsers']);
        $this->assertEquals('user222', $data['typingUsers'][0]['userId']);
        $this->assertEquals('User 2', $data['typingUsers'][0]['name']);
    }

    public function testGetTypingStatusNoTypingUsers(): void
    {
        $typingUsers = [];

        $this->typingService
            ->expects($this->once())
            ->method('getTypingUsers')
            ->with('conv123')
            ->willReturn($typingUsers);

        $result = $this->controller->getTypingStatus('conv123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('conv123', $data['conversationId']);
        $this->assertEmpty($data['typingUsers']);
    }

    public function testGetTypingStatusReturnsCorrectStructure(): void
    {
        $typingUsers = [['userId' => 'UserTest', 'name' => 'Test User']];

        $this->typingService
            ->expects($this->once())
            ->method('getTypingUsers')
            ->willReturn($typingUsers);

        $result = $this->controller->getTypingStatus('testConvId');

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('conversationId', $data);
        $this->assertArrayHasKey('typingUsers', $data);
        $this->assertEquals('testConvId', $data['conversationId']);
    }

    public function testStartTypingWithDifferentUser(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user456']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['conversationId' => 'conv456'])
        );

        $this->typingService
            ->expects($this->once())
            ->method('startTyping')
            ->with('conv456', 'user456');

        $result = $this->controller->startTyping($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testStopTypingWithDifferentUser(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user789']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['conversationId' => 'conv789'])
        );

        $this->typingService
            ->expects($this->once())
            ->method('stopTyping')
            ->with('conv789', 'user789');

        $result = $this->controller->stopTyping($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetTypingStatusWithDifferentConversation(): void
    {
        $typingUsers = [['userId' => 'user999', 'name' => 'User 999']];

        $this->typingService
            ->expects($this->once())
            ->method('getTypingUsers')
            ->with('conv999')
            ->willReturn($typingUsers);

        $result = $this->controller->getTypingStatus('conv999');

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('conv999', $data['conversationId']);
        $this->assertCount(1, $data['typingUsers']);
    }

    public function testStartTypingEmptyJson(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user123']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $result = $this->controller->startTyping($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Conversation ID required', $data['error']);
    }

    public function testStopTypingEmptyJson(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('requireAuthentication')
            ->willReturn(['id' => 'user123']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $result = $this->controller->stopTyping($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Conversation ID required', $data['error']);
    }

    public function testGetTypingStatusEmptyConversation(): void
    {
        $typingUsers = [];

        $this->typingService
            ->expects($this->once())
            ->method('getTypingUsers')
            ->willReturn($typingUsers);

        $result = $this->controller->getTypingStatus('');

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('', $data['conversationId']);
        $this->assertEmpty($data['typingUsers']);
    }
}
