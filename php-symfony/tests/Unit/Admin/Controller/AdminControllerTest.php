<?php declare(strict_types=1);

namespace App\Tests\Unit\Admin\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Admin\Controller\AdminController;
use App\User\Repository\UserRepositoryInterface;
use App\Message\Repository\MessageRepositoryInterface;
use App\Conversation\Repository\ConversationRepositoryInterface;
use App\User\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AllowMockObjectsWithoutExpectations]
class AdminControllerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepo;
    private MessageRepositoryInterface&MockObject $messageRepo;
    private ConversationRepositoryInterface&MockObject $conversationRepo;
    private AdminController $controller;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->messageRepo = $this->createMock(MessageRepositoryInterface::class);
        $this->conversationRepo = $this->createMock(ConversationRepositoryInterface::class);

        $this->controller = new AdminController(
            $this->userRepo,
            $this->messageRepo,
            $this->conversationRepo
        );

        $this->requestStack = new RequestStack();

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->willReturnMap([
                ['request_stack', true],
                ['serializer', false],
            ]);
        $container->method('get')
            ->willReturnMap([
                ['request_stack', $this->requestStack],
            ]);

        $this->controller->setContainer($container);
    }

    private function createUserMock(array $data = []): MockObject&User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($data['id'] ?? 'user-1');
        $user->method('getEmail')->willReturn($data['email'] ?? 'test@example.com');
        $user->method('getDisplayName')->willReturn($data['displayName'] ?? 'Test User');
        $user->method('getBio')->willReturn($data['bio'] ?? null);
        $user->method('getAvatarUrl')->willReturn($data['avatarUrl'] ?? null);
        $createdAt = $data['createdAt'] ?? new \DateTimeImmutable();
        $user->method('getCreatedAt')->willReturn($createdAt);
        $lastLogin = $data['lastLoginAt'] ?? null;
        $user->method('getLastLoginAt')->willReturn($lastLogin);
        $user->method('isActive')->willReturn($data['isActive'] ?? true);
        $user->method('getPhoneNumber')->willReturn($data['phoneNumber'] ?? null);
        $user->method('getPreferredLanguage')->willReturn($data['preferredLanguage'] ?? 'en');
        $user->method('getTimezone')->willReturn($data['timezone'] ?? 'UTC');
        $user->method('getNotificationSettings')->willReturn($data['notificationSettings'] ?? []);
        $user->method('getPrivacySettings')->willReturn($data['privacySettings'] ?? []);
        return $user;
    }

    public function testDashboardSuccess(): void
    {
        $this->userRepo->expects($this->once())->method('countAll')->willReturn(100);
        $this->userRepo->expects($this->once())->method('countActiveUsersToday')->willReturn(50);
        $this->userRepo->expects($this->once())->method('countNewUsersToday')->willReturn(10);
        $this->userRepo->expects($this->once())->method('findRecent')->with(10)->willReturn([$this->createUserMock()]);
        $this->messageRepo->expects($this->once())->method('countAll')->willReturn(500);
        $this->messageRepo->expects($this->once())->method('findRecent')->with(10)->willReturn([]);
        $this->conversationRepo->expects($this->once())->method('countAll')->willReturn(75);

        $response = $this->controller->dashboard();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('recent_users', $data);
        $this->assertArrayHasKey('recent_messages', $data);
        $this->assertEquals(100, $data['stats']['total_users']);
        $this->assertEquals(500, $data['stats']['total_messages']);
        $this->assertEquals(75, $data['stats']['total_conversations']);
        $this->assertEquals(50, $data['stats']['active_users_today']);
        $this->assertEquals(10, $data['stats']['new_users_today']);
    }

    public function testDashboardExceptionHandling(): void
    {
        $this->userRepo->expects($this->once())->method('countAll')->willThrowException(new \RuntimeException('DB failure'));

        $response = $this->controller->dashboard();
        $this->assertSame(500, $response->getStatusCode());
        $this->assertEquals(['error' => 'Internal server error'], json_decode($response->getContent(), true));
    }

    public function testUserDetailsFound(): void
    {
        $userId = 'user-123';
        $user = $this->createUserMock([
            'id' => $userId,
            'email' => 'test@example.com',
            'displayName' => 'Test User',
            'bio' => 'Bio text',
            'avatarUrl' => 'https://example.com/avatar.jpg',
            'createdAt' => new \DateTimeImmutable('2024-01-01'),
            'lastLoginAt' => new \DateTimeImmutable('2024-01-02'),
            'isActive' => true,
            'phoneNumber' => '+1234567890',
            'preferredLanguage' => 'en',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'notificationSettings' => ['email' => true],
            'privacySettings' => ['profile_public' => false],
        ]);

        $this->userRepo->expects($this->once())->method('findById')->with($userId)->willReturn($user);

        $response = $this->controller->userDetails($userId);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($userId, $data['id']);
        $this->assertEquals('Test User', $data['displayName']);
        $this->assertEquals('+1234567890', $data['phoneNumber']);
    }

    public function testUserDetailsNotFound(): void
    {
        $userId = 'nonexistent';
        $this->userRepo->expects($this->once())->method('findById')->with($userId)->willReturn(null);

        $response = $this->controller->userDetails($userId);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertEquals(['error' => 'User not found'], json_decode($response->getContent(), true));
    }

    public function testUpdateUserStatusSuccess(): void
    {
        $userId = 'user-123';
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('setIsActive')->with(false);
        $user->method('isActive')->willReturn(false);
        $this->userRepo->expects($this->once())->method('findById')->with($userId)->willReturn($user);
        $this->userRepo->expects($this->once())->method('save')->with($user);

        $request = Request::create('/api/admin/users/'.$userId.'/status', 'PUT', [], [], [], [], json_encode(['isActive' => false]));
        $this->requestStack->push($request);

        $response = $this->controller->updateUserStatus($request, $userId);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['success' => true, 'isActive' => false], $data);
    }

    public function testUsersSuccess(): void
    {
        $users = [$this->createUserMock(['id' => 'u1']), $this->createUserMock(['id' => 'u2'])];
        $this->userRepo->expects($this->once())
            ->method('findUsersPaginated')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything())
            ->willReturn(['data' => $users, 'total' => 2]);

        $request = Request::create('/api/admin/users', 'GET', [
            'page' => 1,
            'limit' => 20,
            'search' => '',
            'status' => ''
        ]);
        $this->requestStack->push($request);

        $response = $this->controller->users();
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('users', $data);
        $this->assertCount(2, $data['users']);
        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['limit']);
        $this->assertSame(1, $data['pages']);
    }

    public function testConversationsSuccess(): void
    {
        $conversations = [
            ['id' => 'c1', 'subject' => 'Test conversation 1'],
            ['id' => 'c2', 'subject' => 'Test conversation 2'],
        ];
        $this->conversationRepo->expects($this->once())
            ->method('findConversationsPaginated')
            ->with(1, 50, 'all')
            ->willReturn(['data' => $conversations, 'total' => 2]);

        $request = Request::create('/api/admin/conversations', 'GET', [
            'page' => 1,
            'limit' => 50
        ]);
        $this->requestStack->push($request);

        $response = $this->controller->conversations();
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('conversations', $data);
        $this->assertCount(2, $data['conversations']);
        $this->assertSame(2, $data['total']);
    }

    public function testMessagesSuccess(): void
    {
        $messages = [
            ['id' => 'm1', 'content' => 'Hello'],
            ['id' => 'm2', 'content' => 'World'],
        ];
        $this->messageRepo->expects($this->once())
            ->method('findMessagesPaginated')
            ->with(1, 100, 'conv-123')
            ->willReturn(['data' => $messages, 'total' => 2]);

        $request = Request::create('/api/admin/messages', 'GET', [
            'page' => 1,
            'limit' => 100,
            'conversationId' => 'conv-123'
        ]);
        $this->requestStack->push($request);

        $response = $this->controller->messages();
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertCount(2, $data['messages']);
        $this->assertSame(2, $data['total']);
    }

    public function testAnalyticsSuccess(): void
    {
        $start = '2024-01-01';
        $end = '2024-01-31';
        $this->userRepo->expects($this->once())
            ->method('getUserGrowth')
            ->with($start, $end)
            ->willReturn(['2024-01-01' => 5, '2024-01-02' => 3]);
        $this->messageRepo->expects($this->once())
            ->method('getMessageVolume')
            ->with($start, $end)
            ->willReturn(['2024-01-01' => 50, '2024-01-02' => 30]);
        $this->conversationRepo->expects($this->once())
            ->method('getConversationActivity')
            ->with($start, $end)
            ->willReturn(['2024-01-01' => 10, '2024-01-02' => 7]);

        $request = Request::create('/api/admin/analytics', 'GET', [
            'startDate' => $start,
            'endDate' => $end
        ]);
        $this->requestStack->push($request);

        $response = $this->controller->analytics();
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('user_growth', $data);
        $this->assertArrayHasKey('message_volume', $data);
        $this->assertArrayHasKey('conversation_activity', $data);
    }

    // ==================== Additional coverage tests ====================

    private function invokePrivate(object $obj, string $method, array $args = [])
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    // UserDetails edge cases

    public function testUserDetailsEmptyIdReturns500(): void
    {
        $response = $this->controller->userDetails('');
        $this->assertSame(500, $response->getStatusCode());
    }

    // UpdateUserStatus edge cases

    public function testUpdateUserStatusEmptyIdReturns500(): void
    {
        $request = Request::create('/api/admin/users//status', 'PUT', [], [], [], [], json_encode(['isActive' => false]));
        $response = $this->controller->updateUserStatus($request, '');
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testUpdateUserStatusInvalidJsonReturns400(): void
    {
        $userId = 'user-123';
        $user = $this->createUserMock(['id' => $userId]);
        $this->userRepo->expects($this->once())->method('findById')->with($userId)->willReturn($user);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        $response = $this->controller->updateUserStatus($request, $userId);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateUserStatusUserNotFoundReturns404(): void
    {
        $userId = 'nonexistent';
        $this->userRepo->expects($this->once())->method('findById')->with($userId)->willReturn(null);
        $request = Request::create('/api/admin/users/'.$userId.'/status', 'PUT', [], [], [], [], json_encode(['isActive' => false]));
        $response = $this->controller->updateUserStatus($request, $userId);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertEquals(['error' => 'User not found'], json_decode($response->getContent(), true));
    }

    public function testUpdateUserStatusMissingIsActiveReturns200WithCurrentState(): void
    {
        $userId = 'user-123';
        $user = $this->createUserMock(['id' => $userId, 'isActive' => true]);
        $this->userRepo->expects($this->once())->method('findById')->with($userId)->willReturn($user);
        $this->userRepo->expects($this->never())->method('save'); // no save when isActive not provided
        $request = Request::create('/api/admin/users/'.$userId.'/status', 'PUT', [], [], [], [], json_encode([]));
        $response = $this->controller->updateUserStatus($request, $userId);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertTrue($data['isActive']);
    }

    public function testUpdateUserStatusInvalidIsActiveTypeReturns500(): void
    {
        $userId = 'user-123';
        $user = $this->createUserMock(['id' => $userId]);
        $this->userRepo->expects($this->once())->method('findById')->with($userId)->willReturn($user);
        $request = Request::create('/api/admin/users/'.$userId.'/status', 'PUT', [], [], [], [], json_encode(['isActive' => 'yes']));
        $response = $this->controller->updateUserStatus($request, $userId);
        $this->assertSame(500, $response->getStatusCode());
    }

    // Users endpoint edge case: search too long

    public function testUsersWithLongSearchStringReturns500(): void
    {
        $longSearch = str_repeat('a', 101);
        $request = Request::create('/api/admin/users', 'GET', [
            'page' => 1,
            'limit' => 20,
            'search' => $longSearch,
            'status' => 'all'
        ]);
        $this->requestStack->push($request);
        $this->userRepo->expects($this->never())->method('findUsersPaginated');
        $response = $this->controller->users();
        $this->assertSame(500, $response->getStatusCode());
    }

    // Private validation method tests via reflection

    public function testValidatePositiveIntThrowsForLessThanMin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be at least 1');
        $this->invokePrivate($this->controller, 'validatePositiveInt', [0, 'field']);
    }

    public function testValidatePositiveIntThrowsForNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be at least 1');
        $this->invokePrivate($this->controller, 'validatePositiveInt', [-1, 'field']);
    }

    public function testValidatePositiveIntThrowsWhenExceedsMax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not exceed 100');
        $this->invokePrivate($this->controller, 'validatePositiveInt', [101, 'field', 1, 100]);
    }

    public function testValidatePositiveIntAcceptsValid(): void
    {
        $this->invokePrivate($this->controller, 'validatePositiveInt', [5, 'field']);
        $this->assertTrue(true);
    }

    public function testValidatePositiveIntWithMaxAcceptsAtBoundary(): void
    {
        $this->invokePrivate($this->controller, 'validatePositiveInt', [100, 'field', 1, 100]);
        $this->assertTrue(true);
    }

    public function testValidateStringLengthThrowsWhenTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not exceed 10 characters');
        $this->invokePrivate($this->controller, 'validateStringLength', ['12345678901', 'field', 10]);
    }

    public function testValidateStringLengthAcceptsValid(): void
    {
        $this->invokePrivate($this->controller, 'validateStringLength', ['short', 'field', 10]);
        $this->assertTrue(true);
    }

    public function testValidateInArrayThrowsWhenInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be one of');
        $this->invokePrivate($this->controller, 'validateInArray', ['invalid', 'field', ['a', 'b']]);
    }

    public function testValidateInArrayAcceptsValid(): void
    {
        $this->invokePrivate($this->controller, 'validateInArray', ['a', 'field', ['a', 'b']]);
        $this->assertTrue(true);
    }

    public function testValidateBooleanThrowsWhenNotBool(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a boolean');
        $this->invokePrivate($this->controller, 'validateBoolean', ['yes', 'field']);
    }

    public function testValidateBooleanAcceptsValid(): void
    {
        $this->invokePrivate($this->controller, 'validateBoolean', [true, 'field']);
        $this->assertTrue(true);
        $this->invokePrivate($this->controller, 'validateBoolean', [false, 'field']);
        $this->assertTrue(true);
    }

    public function testSanitizeSearchStringRemovesSpecialChars(): void
    {
        $result = $this->invokePrivate($this->controller, 'sanitizeSearchString', ['hello!world']);
        $this->assertSame('helloworld', $result);
    }

    public function testSanitizeStatusStringReturnsAllForInvalid(): void
    {
        $result = $this->invokePrivate($this->controller, 'sanitizeStatusString', ['invalid']);
        $this->assertSame('all', $result);
    }

    public function testSanitizeConversationIdReturnsNullForInvalidPattern(): void
    {
        $result = $this->invokePrivate($this->controller, 'sanitizeConversationId', ['invalid id!']);
        $this->assertNull($result);
    }

    public function testSanitizeDateStringReturnsNullForInvalidFormat(): void
    {
        $result = $this->invokePrivate($this->controller, 'sanitizeDateString', ['01/01/2024']);
        $this->assertNull($result);
    }

    public function testSanitizeDateStringAcceptsValidFormat(): void
    {
        $result = $this->invokePrivate($this->controller, 'sanitizeDateString', ['2024-01-01']);
        $this->assertSame('2024-01-01', $result);
    }
}
