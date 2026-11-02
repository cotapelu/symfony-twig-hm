<?php declare(strict_types=1);

namespace App\Tests\Unit\Messaging\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Messaging\Entity\Conversation;
use App\Core\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ConversationTest extends TestCase
{
    private function createMockUser(?string $id = 'user-123'): MockObject&User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    public function testConstructorSetsDefaults(): void
    {
        $conv = new Conversation(Conversation::TYPE_DIRECT);
        $this->assertIsString($conv->getId());
        $this->assertSame(Conversation::TYPE_DIRECT, $conv->getType());
        $this->assertNull($conv->getName());
        $this->assertNull($conv->getMetadata());
        $this->assertNull($conv->getCreatedBy());
        $this->assertInstanceOf(\DateTime::class, $conv->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $conv->getUpdatedAt());
    }

    public function testIsDirectAndIsGroup(): void
    {
        $direct = new Conversation(Conversation::TYPE_DIRECT);
        $this->assertTrue($direct->isDirect());
        $this->assertFalse($direct->isGroup());

        $group = new Conversation(Conversation::TYPE_GROUP);
        $this->assertFalse($group->isDirect());
        $this->assertTrue($group->isGroup());
    }

    public function testGettersAndSetters(): void
    {
        $conv = new Conversation(Conversation::TYPE_GROUP);
        $conv->setName('Group Chat');
        $this->assertSame('Group Chat', $conv->getName());

        $user = $this->createMockUser();
        $conv->setCreatedBy($user);
        $this->assertSame($user, $conv->getCreatedBy());

        $metadata = ['topic' => 'work'];
        $conv->setMetadata($metadata);
        $this->assertSame($metadata, $conv->getMetadata());
    }
}
