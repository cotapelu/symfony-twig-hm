<?php declare(strict_types=1);

namespace App\Tests\Unit\Message\Service;

use App\Message\Service\MessageService;
use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Conversation\Service\ConversationAccessVerifierInterface;
use App\Shared\EventBus\EventBus;
use App\Shared\Service\UuidService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class MessageServiceTest extends TestCase
{
    private MessageRepositoryInterface&MockObject $repository;
    private ConversationAccessVerifierInterface&MockObject $conversationAccessVerifier;
    private EventBus&MockObject $eventBus;
    private UuidService&MockObject $uuidService;
    private EntityManagerInterface&MockObject $entityManager;
    private MessageService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(MessageRepositoryInterface::class);
        $this->conversationAccessVerifier = $this->createMock(ConversationAccessVerifierInterface::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->uuidService = $this->createMock(UuidService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->entityManager->method('wrapInTransaction')
            ->willReturnCallback(function ($callable) { return $callable(); });

        $this->service = new MessageService(
            $this->repository,
            $this->conversationAccessVerifier,
            $this->eventBus,
            $this->uuidService,
            $this->entityManager
        );
    }

    public function testSendMessageThrowsWhenConversationIdMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Conversation ID and content are required');
        $this->service->sendMessage(['content' => 'Hello'], 'user1');
    }

    public function testSendMessageThrowsWhenContentMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->sendMessage(['conversationId' => 'conv1'], 'user1');
    }

    public function testSendMessageThrowsWhenContentTooLong(): void
    {
        $longContent = str_repeat('a', 10000 + 1);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Content too long');
        $this->service->sendMessage(['conversationId' => 'conv1', 'content' => $longContent], 'user1');
    }

    public function testSendMessageThrowsWhenConversationNotFound(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(false);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Conversation not found');
        $this->service->sendMessage(['conversationId' => 'conv1', 'content' => 'Hi'], 'user1');
    }

    public function testSendMessageThrowsWhenUserNotParticipant(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(true);
        $this->conversationAccessVerifier->method('isParticipant')->with('conv1', 'user1')->willReturn(false);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->sendMessage(['conversationId' => 'conv1', 'content' => 'Hi'], 'user1');
    }

    public function testSendMessageThrowsWhenTooManyAttachments(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(true);
        $this->conversationAccessVerifier->method('isParticipant')->with('conv1', 'user1')->willReturn(true);
        $files = array_fill(0, 10 + 1, ['name' => 'f', 'size' => 100, 'type' => 'image/jpeg']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many files');
        $this->service->sendMessage(['conversationId' => 'conv1', 'content' => 'Hi', 'files' => $files], 'user1');
    }

    public function testSendMessageThrowsWhenFileInvalidFormat(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(true);
        $this->conversationAccessVerifier->method('isParticipant')->with('conv1', 'user1')->willReturn(true);
        $files = [['name' => 'f', 'size' => 100]]; // missing type
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file format');
        $this->service->sendMessage(['conversationId' => 'conv1', 'content' => 'Hi', 'files' => $files], 'user1');
    }

    public function testSendMessageThrowsWhenFileTooLarge(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(true);
        $this->conversationAccessVerifier->method('isParticipant')->with('conv1', 'user1')->willReturn(true);
        $files = [['name' => 'f', 'size' => 10485760 + 1, 'type' => 'image/jpeg']];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File too large');
        $this->service->sendMessage(['conversationId' => 'conv1', 'content' => 'Hi', 'files' => $files], 'user1');
    }

    public function testSendMessageThrowsWhenFileTypeNotAllowed(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(true);
        $this->conversationAccessVerifier->method('isParticipant')->with('conv1', 'user1')->willReturn(true);
        $files = [['name' => 'f', 'size' => 100, 'type' => 'application/exe']];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File type not allowed');
        $this->service->sendMessage(['conversationId' => 'conv1', 'content' => 'Hi', 'files' => $files], 'user1');
    }

    public function testSendMessageSuccessWithoutAttachments(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(true);
        $this->conversationAccessVerifier->method('isParticipant')->with('conv1', 'user1')->willReturn(true);
        $this->uuidService->method('generateWithPrefix')->with('msg_')->willReturn('msg_123');
        $this->repository->expects($this->once())->method('save');
        $this->eventBus->expects($this->once())->method('dispatch');
        $this->conversationAccessVerifier->method('getParticipantIds')->with('conv1')->willReturn(['user1', 'user2']);

        $result = $this->service->sendMessage(['conversationId' => 'conv1', 'content' => 'Hello world'], 'user1');

        $this->assertInstanceOf(Message::class, $result);
        $this->assertEquals('msg_123', $result->getId());
        $this->assertEquals('conv1', $result->getConversationId());
        $this->assertEquals('user1', $result->getSenderId());
        $this->assertEquals('Hello world', $result->getContent());
    }

    public function testSendMessageSuccessWithAttachments(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(true);
        $this->conversationAccessVerifier->method('isParticipant')->with('conv1', 'user1')->willReturn(true);
        $this->uuidService->method('generateWithPrefix')->with('msg_')->willReturn('msg_456');
        $this->repository->expects($this->once())->method('save');
        $this->eventBus->expects($this->once())->method('dispatch');
        $this->conversationAccessVerifier->method('getParticipantIds')->with('conv1')->willReturn(['user1']);

        $files = [['name' => 'img.jpg', 'size' => 1024, 'type' => 'image/jpeg']];
        $result = $this->service->sendMessage([
            'conversationId' => 'conv1',
            'content' => 'Check this',
            'files' => $files
        ], 'user1');

        $this->assertInstanceOf(Message::class, $result);
        $this->assertEquals($files, $result->getAttachments());
    }

    public function testGetMessagesForUserMapping(): void
    {
        $msg = new Message('msg1', 'conv1', 'sender1', 'Content', 'text', null);
        // Set createdAt via reflection to known value
        $ref = new \ReflectionProperty($msg, 'createdAt');
        $ref->setAccessible(true);
        $ref->setValue($msg, new \DateTime('2025-01-01 10:00:00'));

        $this->repository->method('findRecentByUserId')->with('user1', 50)->willReturn([$msg]);

        $result = $this->service->getMessagesForUser('user1', 50);

        $this->assertCount(1, $result);
        $this->assertEquals('msg1', $result[0]['id']);
        $this->assertEquals('conv1', $result[0]['conversation_id']);
        $this->assertEquals('sender1', $result[0]['sender_id']);
        $this->assertEquals('Content', $result[0]['content']);
        $this->assertEquals('text', $result[0]['type']);
        $this->assertFalse($result[0]['is_thread']);
    }

    public function testGetConversationMessagesThrowsWhenNotFound(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(false);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getConversationMessages('conv1', 'user1');
    }

    public function testGetConversationMessagesThrowsWhenAccessDenied(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(true);
        $this->conversationAccessVerifier->method('isParticipant')->with('conv1', 'user1')->willReturn(false);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getConversationMessages('conv1', 'user1');
    }

    public function testGetConversationMessagesSuccess(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(true);
        $this->conversationAccessVerifier->method('isParticipant')->with('conv1', 'user1')->willReturn(true);
        $msg = new Message('m1', 'conv1', 'u1', 'Hello', 'text', null);
        $this->repository->method('findByConversationIdWithPagination')->with('conv1', 50, 0)->willReturn([$msg]);
        $result = $this->service->getConversationMessages('conv1', 'user1', 1, 50);
        $this->assertSame([$msg], $result);
    }

    public function testGetConversationMessagesCursorSuccess(): void
    {
        $this->conversationAccessVerifier->method('exists')->with('conv1')->willReturn(true);
        $this->conversationAccessVerifier->method('isParticipant')->with('conv1', 'user1')->willReturn(true);
        $msg = new Message('m1', 'conv1', 'u1', 'Hello', 'text', null);
        $this->repository->method('findByConversationIdWithCursor')->with('conv1', null, 50)->willReturn([$msg]);
        $result = $this->service->getConversationMessagesCursor('conv1', 'user1', null, 50);
        $this->assertSame([$msg], $result);
    }
}
