<?php declare(strict_types=1);

namespace App\Tests\Unit\Messaging\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Messaging\Entity\Message;
use App\Messaging\Entity\Conversation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MessageTest extends TestCase
{
    private function createMockConversation(string $id = 'conv-123'): MockObject&Conversation
    {
        $conv = $this->createMock(Conversation::class);
        $conv->method('getId')->willReturn($id);
        return $conv;
    }

    public function testConstructor(): void
    {
        $conv = $this->createMockConversation();
        $msg = new Message($conv, Message::TYPE_TEXT);

        $this->assertIsString($msg->getId());
        $this->assertSame($conv, $msg->getConversation());
        $this->assertSame(Message::TYPE_TEXT, $msg->getType());
        $this->assertNull($msg->getContent());
        $this->assertNull($msg->getAttachments());
        $this->assertNull($msg->getReadAt());
        $this->assertFalse($msg->isRead());
        $this->assertInstanceOf(\DateTime::class, $msg->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $conv = $this->createMockConversation();
        $msg = new Message($conv, Message::TYPE_IMAGE);
        $msg->setContent('Hello');
        $msg->setAttachments(['url' => 'http://example.com/img.jpg']);
        $msg->setReadAt(new \DateTime('2025-01-01 00:00:00'));

        $this->assertSame('Hello', $msg->getContent());
        $this->assertSame(['url' => 'http://example.com/img.jpg'], $msg->getAttachments());
        $this->assertInstanceOf(\DateTime::class, $msg->getReadAt());
    }

    public function testMarkAsRead(): void
    {
        $conv = $this->createMockConversation();
        $msg = new Message($conv, Message::TYPE_VIDEO);
        $this->assertFalse($msg->isRead());
        $msg->markAsRead();
        $this->assertTrue($msg->isRead());
        $this->assertInstanceOf(\DateTime::class, $msg->getReadAt());
    }

    public function testToArray(): void
    {
        $conv = $this->createMockConversation('conv-id-456');
        $msg = new Message($conv, Message::TYPE_TEXT);
        $msg->setContent('Test content');
        $msg->setAttachments([['name' => 'file.pdf', 'url' => 'http://example.com/file.pdf']]);
        $readAt = new \DateTime('2025-01-02 12:00:00');
        $msg->setReadAt($readAt);

        $arr = $msg->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame('conv-id-456', $arr['conversationId']);
        $this->assertNull($arr['senderId']);
        $this->assertSame(Message::TYPE_TEXT, $arr['type']);
        $this->assertSame('Test content', $arr['content']);
        $this->assertSame([['name' => 'file.pdf', 'url' => 'http://example.com/file.pdf']], $arr['attachments']);
        $this->assertSame('2025-01-02 12:00:00', $arr['readAt']);
        $this->assertSame($msg->getCreatedAt()->format('Y-m-d H:i:s'), $arr['createdAt']);
    }
}
