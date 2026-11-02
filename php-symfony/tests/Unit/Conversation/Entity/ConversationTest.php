<?php declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Conversation\Entity\Conversation;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ConversationTest extends TestCase
{
    private function createConv(array $participantIds, string $type = 'direct', ?string $title = null): Conversation
    {
        $conv = new Conversation($participantIds, $type);
        if ($title !== null) {
            $conv->setTitle($title);
        }
        return $conv;
    }

    public function testConstructorInitializesProperties(): void
    {
        $conv = $this->createConv([1, 2, 3], 'group', 'Team Chat');
        $this->assertNull($conv->getId());
        $this->assertSame([1, 2, 3], $conv->getParticipantIds());
        $this->assertSame('group', $conv->getType());
        $this->assertSame('Team Chat', $conv->getTitle());
        $this->assertNull($conv->getCreatedBy());
        $this->assertNull($conv->getAvatar());
        $this->assertNull($conv->getLastMessageId());
        $this->assertInstanceOf(\DateTime::class, $conv->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $conv->getUpdatedAt());
    }

    public function testConstructorWithoutTitle(): void
    {
        $conv = new Conversation([42], 'direct');
        $this->assertNull($conv->getTitle());
        $this->assertSame([42], $conv->getParticipantIds());
        $this->assertSame('direct', $conv->getType());
    }

    public function testGettersAndSetters(): void
    {
        $conv = $this->createConv([1], 'direct');

        $conv->setTitle('Support');
        $this->assertSame('Support', $conv->getTitle());

        $conv->setCreatedBy(100);
        $this->assertSame(100, $conv->getCreatedBy());

        $conv->setAvatar('avatar.png');
        $this->assertSame('avatar.png', $conv->getAvatar());

        $conv->setLastMessageId(999);
        $this->assertSame(999, $conv->getLastMessageId());

        $conv->setParticipantIds([5, 6, 7]);
        $this->assertSame([5, 6, 7], $conv->getParticipantIds());

        // hasParticipant
        $this->assertTrue($conv->hasParticipant(5));
        $this->assertFalse($conv->hasParticipant(1));
    }

    public function testIdSetter(): void
    {
        $conv = $this->createConv([1], 'direct');
        $this->assertNull($conv->getId());
        $conv->setId(123);
        $this->assertSame(123, $conv->getId());
    }
}
