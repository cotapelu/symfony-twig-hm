<?php declare(strict_types=1);

namespace App\Tests\Unit\GDPR\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\GDPR\Service\GdprService;
use App\User\Repository\UserRepositoryInterface;
use App\Message\Repository\MessageRepositoryInterface;
use App\User\Entity\User;
use App\Message\Entity\Message;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GdprServiceTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private MessageRepositoryInterface&MockObject $messageRepository;
    private GdprService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->messageRepository = $this->createMock(MessageRepositoryInterface::class);
        $this->service = new GdprService($this->userRepository, $this->messageRepository);
    }

    public function testExportUserDataReturnsEmptyArrayWhenUserNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with('nonexistent_user_id')
            ->willReturn(null);

        $result = $this->service->exportUserData('nonexistent_user_id');

        $this->assertSame([], $result);
    }

    public function testExportUserDataReturnsCorrectUserStructure(): void
    {
        $userId = 'user-123';
        $email = 'test@example.com';
        $phone = '+1234567890';
        $lastLoginAt = new \DateTime('-1 day');

        $user = new User($userId, $email);
        $user->setPhoneNumber($phone);
        $user->setLastLoginAt($lastLoginAt);
        $user->setIsActive(true);
        $user->setPreferredLanguage('en');
        $user->setTimezone('UTC');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $this->messageRepository
            ->expects($this->once())
            ->method('findBySenderId')
            ->with($userId)
            ->willReturn([]);

        $result = $this->service->exportUserData($userId);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertSame([], $result['messages']);

        $userData = $result['user'];
        $this->assertSame($userId, $userData['id']);
        $this->assertSame($email, $userData['email']);
        $this->assertSame($phone, $userData['phoneNumber']);
        $this->assertSame('en', $userData['preferredLanguage']);
        $this->assertSame('UTC', $userData['timezone']);
        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $userData['roles']);
        $this->assertTrue($userData['isActive']);
        $this->assertIsString($userData['createdAt']);
        $this->assertIsString($userData['lastLoginAt']);
    }

    public function testExportUserDataMapsMessagesCorrectly(): void
    {
        $userId = 'user-123';
        $user = new User($userId, 'test@example.com');

        $now = new \DateTime();

        $message1 = $this->createMock(Message::class);
        $message1->method('getId')->willReturn('msg-1');
        $message1->method('getConversationId')->willReturn('conv-1');
        $message1->method('getContent')->willReturn('Hello world');
        $message1->method('getType')->willReturn('text');
        $message1->method('getCreatedAt')->willReturn($now);
        $message1->method('isEdited')->willReturn(false);
        $message1->method('isDeleted')->willReturn(false);

        $message2 = $this->createMock(Message::class);
        $message2->method('getId')->willReturn('msg-2');
        $message2->method('getConversationId')->willReturn('conv-1');
        $message2->method('getContent')->willReturn('Second message');
        $message2->method('getType')->willReturn('image');
        $message2->method('getCreatedAt')->willReturn($now);
        $message2->method('isEdited')->willReturn(true);
        $message2->method('isDeleted')->willReturn(false);

        $this->userRepository->expects($this->once())->method('find')->with($userId)->willReturn($user);
        $this->messageRepository->expects($this->once())
            ->method('findBySenderId')
            ->with($userId)
            ->willReturn([$message1, $message2]);

        $result = $this->service->exportUserData($userId);

        $this->assertCount(2, $result['messages']);
        $this->assertSame('msg-1', $result['messages'][0]['id']);
        $this->assertSame('conv-1', $result['messages'][0]['conversationId']);
        $this->assertSame('Hello world', $result['messages'][0]['content']);
        $this->assertFalse($result['messages'][0]['isEdited']);
        $this->assertFalse($result['messages'][0]['isDeleted']);

        $this->assertSame('msg-2', $result['messages'][1]['id']);
        $this->assertTrue($result['messages'][1]['isEdited']);
    }

    public function testDeleteUserDataAnonymizesUserAndSaves(): void
    {
        $userId = 'user-123';
        $user = $this->createMock(User::class);

        $user->expects($this->once())
            ->method('setEmail')
            ->with('deleted_user-123@deleted.com')
            ->willReturnSelf();
        $user->expects($this->once())
            ->method('setPhoneNumber')
            ->with(null)
            ->willReturnSelf();
        $user->expects($this->once())
            ->method('setBiometricData')
            ->with(null)
            ->willReturnSelf();
        $user->expects($this->once())
            ->method('markAsDeleted')
            ->willReturnSelf();
        $user->expects($this->once())
            ->method('setIsActive')
            ->with(false)
            ->willReturnSelf();

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->service->deleteUserData($userId);
    }

    public function testDeleteUserDataDoesNothingWhenUserNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with('nonexistent')
            ->willReturn(null);
        $this->userRepository->expects($this->never())->method('save');

        $this->service->deleteUserData('nonexistent');
    }
}
