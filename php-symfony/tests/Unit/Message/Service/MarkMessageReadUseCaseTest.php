<?php declare(strict_types=1);

namespace App\Tests\Unit\Message\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Service\MarkMessageReadRequest;
use App\Message\Service\MarkMessageReadResponse;
use App\Message\Service\MarkMessageReadUseCase;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MarkMessageReadUseCaseTest extends TestCase
{
    private MessageRepositoryInterface $messageRepository;
    private MarkMessageReadUseCase $useCase;

    protected function setUp(): void
    {
        $this->messageRepository = $this->createMock(MessageRepositoryInterface::class);
        $this->useCase = new MarkMessageReadUseCase($this->messageRepository);
    }

    public function testExecuteThrowsIfMessageNotFound(): void
    {
        $messageId = 'msg_123';
        $userId = 'user_123';

        $this->messageRepository->expects($this->once())
            ->method('findById')
            ->with($messageId)
            ->willReturn(null);

        $request = new MarkMessageReadRequest($messageId, $userId);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message not found');

        $this->useCase->execute($request);
    }

    public function testExecuteThrowsIfUserIsSender(): void
    {
        $messageId = 'msg_123';
        $userId = 'user_123';
        $senderId = 'user_123';

        $message = $this->createMock(Message::class);
        $message->method('getSenderId')->willReturn($senderId);

        $this->messageRepository->expects($this->once())
            ->method('findById')
            ->willReturn($message);

        $request = new MarkMessageReadRequest($messageId, $userId);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot mark own message as read');

        $this->useCase->execute($request);
    }

    public function testExecuteSetsDeliveredAndReadTimestamps(): void
    {
        $messageId = 'msg_123';
        $userId = 'user_456';
        $senderId = 'other_user_789';

        $message = $this->createMock(Message::class);
        $message->method('getSenderId')->willReturn($senderId);
        $message->method('getDeliveredAt')->willReturn(null);

        $this->messageRepository->expects($this->once())
            ->method('findById')
            ->willReturn($message);

        $message->expects($this->once())
            ->method('setDeliveredAt')
            ->with($this->anything());
        $message->expects($this->once())
            ->method('setReadAt')
            ->with($this->anything());

        $this->messageRepository->expects($this->once())
            ->method('save')
            ->with($message);

        $request = new MarkMessageReadRequest($messageId, $userId);
        $response = $this->useCase->execute($request);

        $this->assertInstanceOf(MarkMessageReadResponse::class, $response);
        $this->assertSame($message, $response->message);
    }

    public function testExecuteDoesNotOverwriteExistingDeliveredAt(): void
    {
        $messageId = 'msg_123';
        $userId = 'user_456';
        $senderId = 'other_user';
        $existingDelivered = new \DateTime('-1 hour');

        $message = $this->createMock(Message::class);
        $message->method('getSenderId')->willReturn($senderId);
        $message->method('getDeliveredAt')->willReturn($existingDelivered);
        $message->expects($this->once())
            ->method('setDeliveredAt')
            ->with($existingDelivered);
        $message->expects($this->once())
            ->method('setReadAt')
            ->with($this->anything());

        $this->messageRepository->expects($this->once())
            ->method('findById')
            ->willReturn($message);
        $this->messageRepository->expects($this->once())
            ->method('save')
            ->with($message);

        $request = new MarkMessageReadRequest($messageId, $userId);
        $response = $this->useCase->execute($request);

        $this->assertSame($message, $response->message);
    }
}
