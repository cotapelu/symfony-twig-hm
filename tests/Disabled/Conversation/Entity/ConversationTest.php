<?php declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Entity;

use App\Conversation\Entity\Conversation;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Conversation\Entity\Conversation
 */
class ConversationTest extends TestCase
{
    public function testConstructorSetsRequiredProperties(): void
    {
        $conversation = new Conversation([1, 2, 3], 'group');

        $this->assertNull($conversation->getId());
        $this->assertEquals([1, 2, 3], $conversation->getParticipantIds());
        $this->assertEquals('group', $conversation->getType());
        $this->assertNull($conversation->getTitle());
        $this->assertNull($conversation->getCreatedBy());
        $this->assertNull($conversation->getAvatar());
        $this->assertNull($conversation->getLastMessageId());
    }

    public function testDirectConversation(): void
    {
        $conversation = new Conversation([1, 2], 'direct');

        $this->assertEquals([1, 2], $conversation->getParticipantIds());
        $this->assertEquals('direct', $conversation->getType());
    }

    public function testSetId(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setId(123);

        $this->assertEquals(123, $conversation->getId());
    }

    public function testSetTitle(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setTitle('Team Chat');

        $this->assertEquals('Team Chat', $conversation->getTitle());
    }

    public function testSetCreatedBy(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setCreatedBy(1);

        $this->assertEquals(1, $conversation->getCreatedBy());
    }

    public function testSetAvatar(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setAvatar('/avatars/group.png');

        $this->assertEquals('/avatars/group.png', $conversation->getAvatar());
    }

    public function testSetLastMessageId(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setLastMessageId(456);

        $this->assertEquals(456, $conversation->getLastMessageId());
    }

    public function testSetParticipantIds(): void
    {
        $conversation = new Conversation([1, 2], 'group');
        $conversation->setParticipantIds([3, 4, 5]);

        $this->assertEquals([3, 4, 5], $conversation->getParticipantIds());
    }

    public function testSettersAreChainable(): void
    {
        $conversation = new Conversation([1, 2], 'group');

        $result = $conversation
            ->setTitle('Team Chat')
            ->setCreatedBy(1)
            ->setAvatar('/avatars/group.png')
            ->setLastMessageId(456);

        $this->assertSame($conversation, $result);
        $this->assertEquals('Team Chat', $conversation->getTitle());
    }

    public function testGroupConversationWithTitle(): void
    {
        $conversation = new Conversation([1, 2, 3], 'group');
        $conversation->setTitle('Project Discussion');
        $conversation->setCreatedBy(1);

        $this->assertEquals('group', $conversation->getType());
        $this->assertEquals('Project Discussion', $conversation->getTitle());
        $this->assertEquals(1, $conversation->getCreatedBy());
    }
}
