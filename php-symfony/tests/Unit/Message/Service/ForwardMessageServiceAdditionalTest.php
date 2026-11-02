<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message\Service;

use PHPUnit\Framework\TestCase;
use App\Message\Service\ForwardMessageService;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\Service\UuidService;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Message\Entity\Message;

class ForwardMessageServiceAdditionalTest extends TestCase
{
    private ForwardMessageService $service;

    protected function setUp(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $messageRepo = $this->createMock(MessageRepositoryInterface::class);
        $uuidService = $this->createMock(UuidService::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $this->service = new ForwardMessageService($entityManager, $messageRepo, $uuidService, $eventPublisher);
    }

    private function createMessageMock(array $overrides = []): \PHPUnit\Framework\MockObject\MockObject
    {
        $msg = $this->createMock(Message::class);
        $msg->method('getId')->willReturn($overrides['id'] ?? 'msg1');
        $msg->method('getConversationId')->willReturn($overrides['conversationId'] ?? 'conv1');
        $msg->method('getSenderId')->willReturn($overrides['senderId'] ?? 'user1');
        $msg->method('getType')->willReturn($overrides['type'] ?? 'text');
        $msg->method('getContent')->willReturn($overrides['content'] ?? 'Hello');
        $msg->method('isDeleted')->willReturn($overrides['isDeleted'] ?? false);
        return $msg;
    }

    // ==================== determineForwardCaption tests ====================

    public function testDetermineForwardCaption_WhenIncludeCaptionFalse_ReturnsNull(): void
    {
        $original = $this->createMessageMock();
        $method = new \ReflectionMethod($this->service, 'determineForwardCaption');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $original, false, null);
        $this->assertNull($result);
    }

    public function testDetermineForwardCaption_WhenCaptionProvided_ReturnsCaption(): void
    {
        $original = $this->createMessageMock();
        $method = new \ReflectionMethod($this->service, 'determineForwardCaption');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $original, true, 'My caption');
        $this->assertSame('My caption', $result);
    }

    public function testDetermineForwardCaption_WhenTextAndNoCaption_ReturnsDefault(): void
    {
        $original = $this->createMessageMock(['type' => 'text']);
        $method = new \ReflectionMethod($this->service, 'determineForwardCaption');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $original, true, null);
        $this->assertSame('Forwarded message', $result);
    }

    public function testDetermineForwardCaption_WhenNonTextAndNoCaption_ReturnsForwardedAttachment(): void
    {
        $original = $this->createMessageMock(['type' => 'image']);
        $method = new \ReflectionMethod($this->service, 'determineForwardCaption');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $original, true, null);
        $this->assertSame('Forwarded attachment', $result);
    }

    // ==================== determineForwardedContent tests ====================

    public function testDetermineForwardedContent_WhenText_ReturnsContent(): void
    {
        $original = $this->createMessageMock(['type' => 'text', 'content' => 'Hello world']);
        $method = new \ReflectionMethod($this->service, 'determineForwardedContent');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $original, true);
        $this->assertSame('Hello world', $result);
    }

    public function testDetermineForwardedContent_WhenNonText_ReturnsNull(): void
    {
        $original = $this->createMessageMock(['type' => 'video']);
        $method = new \ReflectionMethod($this->service, 'determineForwardedContent');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $original, true);
        $this->assertNull($result);
    }

    // ==================== buildForwardMetadata tests ====================

    public function testBuildForwardMetadata_IncludesAllFields(): void
    {
        $original = $this->createMessageMock(['id' => 'orig1', 'conversationId' => 'conv1', 'senderId' => 'user1', 'type' => 'text']);
        $method = new \ReflectionMethod($this->service, 'buildForwardMetadata');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $original, 'user2', true, null);
        $this->assertSame('orig1', $result['forwarded_from_message_id']);
        $this->assertSame('conv1', $result['forwarded_from_conversation_id']);
        $this->assertSame('user2', $result['forwarded_by']);
        $this->assertSame('text', $result['original_type']);
        $this->assertSame('user1', $result['original_sender_id']);
        $this->assertArrayNotHasKey('caption', $result);
    }

    public function testBuildForwardMetadata_IncludesCaptionWhenProvided(): void
    {
        $original = $this->createMessageMock();
        $method = new \ReflectionMethod($this->service, 'buildForwardMetadata');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $original, 'user2', true, 'My caption');
        $this->assertSame('My caption', $result['caption']);
    }
}
