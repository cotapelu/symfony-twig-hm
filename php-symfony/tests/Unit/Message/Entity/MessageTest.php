<?php declare(strict_types=1);

namespace App\Tests\Unit\Message\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Message\Entity\Message;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MessageTest extends TestCase
{
    private function createMessage(
        ?string $id = null,
        ?string $conversationId = null,
        ?string $senderId = null,
        ?string $content = null,
        string $type = 'text',
        ?string $threadId = null
    ): Message {
        return new Message(
            id: $id ?? 'msg-1',
            conversationId: $conversationId ?? 'conv-1',
            senderId: $senderId ?? 'user-1',
            content: $content ?? 'Hello',
            type: $type,
            threadId: $threadId
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $msg = $this->createMessage();
        $this->assertSame('msg-1', $msg->getId());
        $this->assertSame('conv-1', $msg->getConversationId());
        $this->assertSame('user-1', $msg->getSenderId());
        $this->assertSame('Hello', $msg->getContent());
        $this->assertSame('text', $msg->getType());
        $this->assertNull($msg->getThreadId());
        $this->assertInstanceOf(\DateTime::class, $msg->getCreatedAt());
        $this->assertNull($msg->getUpdatedAt());
        $this->assertFalse($msg->isEdited());
        $this->assertNull($msg->getEditHistory());
        $this->assertFalse($msg->isDeleted());
        $this->assertNull($msg->getDeletedAt());
        $this->assertNull($msg->getDeletedBy());
        $this->assertFalse($msg->isPinned());
        $this->assertNull($msg->getReplyToMessageId());
        $this->assertNull($msg->getAttachments());
        $this->assertNull($msg->getReactions());
        $this->assertNull($msg->getDeliveredAt());
        $this->assertNull($msg->getReadAt());
        $this->assertNull($msg->getEditedAt());
    }

    public function testConstructorWithOptionalTypeAndThread(): void
    {
        $msg = $this->createMessage(type: 'image', threadId: 'thread-1');
        $this->assertSame('image', $msg->getType());
        $this->assertSame('thread-1', $msg->getThreadId());
    }

    public function testGettersAndSetters(): void
    {
        $msg = $this->createMessage();

        $msg->setContent('New content');
        $this->assertSame('New content', $msg->getContent());
        $this->assertTrue($msg->isEdited());
        $this->assertInstanceOf(\DateTime::class, $msg->getUpdatedAt());

        $msg->setThreadId('thread-2');
        $this->assertSame('thread-2', $msg->getThreadId());

        $msg->setReplyToMessageId('msg-5');
        $this->assertSame('msg-5', $msg->getReplyToMessageId());

        $msg->setAttachments(['url' => 'http://example.com/file.pdf']);
        $att = $msg->getAttachments();
        $this->assertIsArray($att);
        $this->assertSame('http://example.com/file.pdf', $att['url']);

        $msg->setReactions(['like' => 3]);
        $react = $msg->getReactions();
        $this->assertIsArray($react);
        $this->assertSame(3, $react['like']);

        $now = new \DateTime();
        $msg->setDeliveredAt($now);
        $this->assertSame($now, $msg->getDeliveredAt());

        $msg->setReadAt($now);
        $this->assertSame($now, $msg->getReadAt());

        $msg->setEditHistory(['original' => 'Hello']);
        $hist = $msg->getEditHistory();
        $this->assertIsArray($hist);
        $this->assertSame('Hello', $hist['original']);

        $edited = new \DateTime();
        $msg->setEditedAt($edited);
        $this->assertSame($edited, $msg->getEditedAt());

        $msg->setIsDeleted(true);
        $this->assertTrue($msg->isDeleted());

        $deletedAt = new \DateTime();
        $msg->setDeletedAt($deletedAt);
        $this->assertSame($deletedAt, $msg->getDeletedAt());

        $msg->setDeletedBy('admin-1');
        $this->assertSame('admin-1', $msg->getDeletedBy());

        $msg->setIsPinned(true);
        $this->assertTrue($msg->isPinned());
    }

    public function testAttachmentsAndReactionsNullWhenNotSet(): void
    {
        $msg = $this->createMessage();
        $this->assertNull($msg->getAttachments());
        $this->assertNull($msg->getReactions());
        $this->assertNull($msg->getEditHistory());
    }
}
