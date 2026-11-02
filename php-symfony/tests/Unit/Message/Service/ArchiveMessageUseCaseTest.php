<?php declare(strict_types=1);

namespace App\Tests\Unit\Message\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Message\Entity\Message as MessageEntity;
use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Service\ArchiveMessageUseCase;
use App\Message\Service\ArchiveMessageRequest;
use App\Message\Service\ArchiveMessageResponse;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ArchiveMessageUseCaseTest extends TestCase
{
    private MessageRepositoryInterface $messageRepository;
    private ArchiveMessageUseCase $useCase;

    protected function setUp(): void
    {
        $this->messageRepository = $this->createMock(MessageRepositoryInterface::class);
        $this->useCase = new ArchiveMessageUseCase($this->messageRepository);
    }

    public function testExecuteArchivesOwnMessageSuccessfully(): void
    {
        $messageId = 'msg_123';
        $userId = 'user_123';
        $originalContent = 'Hello world';
        $archivedContent = "[Archived by {$userId}] {$originalContent}";

        $message = $this->createMock(MessageEntity::class);
        $message->method('getSenderId')->willReturn($userId);
        $message->method('getContent')->willReturn($originalContent);
        $message->expects($this->once())
            ->method('setContent')
            ->with($archivedContent);
        $this->messageRepository->expects($this->once())
            ->method('findById')
            ->with($messageId)
            ->willReturn($message);
        $this->messageRepository->expects($this->once())
            ->method('save')
            ->with($message);

        $request = new ArchiveMessageRequest($messageId, $userId);
        $response = $this->useCase->execute($request);

        $this->assertInstanceOf(ArchiveMessageResponse::class, $response);
        $this->assertSame($message, $response->message);
    }

    public function testExecuteThrowsWhenMessageNotFound(): void
    {
        $messageId = 'msg_nonexistent';
        $userId = 'user_123';

        $this->messageRepository->expects($this->once())
            ->method('findById')
            ->with($messageId)
            ->willReturn(null);

        $request = new ArchiveMessageRequest($messageId, $userId);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message not found');

        $this->useCase->execute($request);
    }

    public function testExecuteThrowsWhenUserNotSender(): void
    {
        $messageId = 'msg_123';
        $userId = 'user_123';
        $otherUserId = 'other_user';

        $message = $this->createMock(MessageEntity::class);
        $message->method('getSenderId')->willReturn($otherUserId);

        $this->messageRepository->expects($this->once())
            ->method('findById')
            ->with($messageId)
            ->willReturn($message);

        $request = new ArchiveMessageRequest($messageId, $userId);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot archive other users messages');

        $this->useCase->execute($request);
    }
}
