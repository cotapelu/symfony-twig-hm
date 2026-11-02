<?php

namespace App\Tests\Unit\Message\Service;

use App\Message\Service\ForwardMessageService;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\Service\UuidService;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use App\Message\DomainEvent\MessageForwardedEvent;
use App\Message\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ForwardMessageServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private MessageRepositoryInterface $messageRepo;
    private UuidService $uuidService;
    private DomainEventPublisherInterface $eventPublisher;
    private ForwardMessageService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->messageRepo = $this->createMock(MessageRepositoryInterface::class);
        $this->uuidService = $this->createMock(UuidService::class);
        $this->eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $this->service = new ForwardMessageService(
            $this->em,
            $this->messageRepo,
            $this->uuidService,
            $this->eventPublisher
        );
    }

    public function testForwardMessageSuccess(): void
    {
        $originalId = 'msg-1';
        $userId = 'user-1';
        $targetConversationId = 'conv-2';
        $includeCaption = true;
        $caption = 'Check this';

        $originalMessage = $this->createMock(Message::class);
        $originalMessage->method('getId')->willReturn($originalId);
        $originalMessage->method('getConversationId')->willReturn('conv-1');
        $originalMessage->method('getSenderId')->willReturn('sender-1');
        $originalMessage->method('getType')->willReturn('text');
        $originalMessage->method('getContent')->willReturn('Original content');
        $originalMessage->method('isDeleted')->willReturn(false);
        $originalMessage->method('getAttachments')->willReturn([]);

        $this->messageRepo->expects($this->once())->method('findById')->with($originalId)->willReturn($originalMessage);
        $this->messageRepo->expects($this->once())->method('isUserParticipant')->with($targetConversationId, $userId)->willReturn(true);
        $this->uuidService->expects($this->once())->method('generate')->willReturn('new-msg-id');
        $this->eventPublisher->expects($this->once())->method('publish')->with($this->isInstanceOf(MessageForwardedEvent::class));

        $result = $this->service->forwardMessage($originalId, $userId, $targetConversationId, $includeCaption, $caption);

        $this->assertInstanceOf(Message::class, $result);
        $this->assertSame('new-msg-id', $result->getId());
        $this->assertSame($targetConversationId, $result->getConversationId());
        $this->assertSame($userId, $result->getSenderId());
        $this->assertSame('forward', $result->getType());
        $this->assertSame('Original content', $result->getContent());
        $this->assertArrayHasKey('caption', $result->getAttachments());
        $this->assertSame($caption, $result->getAttachments()['caption']);
    }

    public function testForwardMessageOriginalNotFound(): void
    {
        $this->messageRepo->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Original message not found');
        $this->service->forwardMessage('missing', 'user-1', 'conv-1', true, null);
    }

    public function testForwardMessageDeletedOriginal(): void
    {
        $originalMessage = $this->createMock(Message::class);
        $originalMessage->method('isDeleted')->willReturn(true);
        $this->messageRepo->expects($this->once())->method('findById')->willReturn($originalMessage);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot forward deleted message');
        $this->service->forwardMessage('msg-1', 'user-1', 'conv-1', true, null);
    }

    public function testForwardMessageUserNotParticipant(): void
    {
        $originalMessage = $this->createMock(Message::class);
        $originalMessage->method('isDeleted')->willReturn(false);
        $this->messageRepo->expects($this->once())->method('findById')->willReturn($originalMessage);
        $this->messageRepo->expects($this->once())->method('isUserParticipant')->with('conv-1', 'user-1')->willReturn(false);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You are not a participant in target conversation');
        $this->service->forwardMessage('msg-1', 'user-1', 'conv-1', true, null);
    }
}
