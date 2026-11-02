<?php declare(strict_types=1);

namespace App\Tests\Unit\Messaging\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Messaging\Entity\ConversationMember;
use App\Messaging\Entity\Conversation;
use App\Core\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ConversationMemberTest extends TestCase
{
    private function createMockConversation(string $id = 'conv-123'): MockObject&Conversation
    {
        $conv = $this->createMock(Conversation::class);
        $conv->method('getId')->willReturn($id);
        return $conv;
    }

    private function createMockUser(string $id = 'user-123'): MockObject&User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    public function testConstructor(): void
    {
        $conv = $this->createMockConversation();
        $user = $this->createMockUser();
        $member = new ConversationMember($conv, $user);

        $this->assertIsString($member->getId());
        $this->assertSame($conv, $member->getConversation());
        $this->assertSame($user, $member->getUser());
        $this->assertSame(ConversationMember::ROLE_MEMBER, $member->getRole());
        $this->assertInstanceOf(\DateTime::class, $member->getJoinedAt());
    }

    public function testSetRole(): void
    {
        $conv = $this->createMockConversation();
        $user = $this->createMockUser();
        $member = new ConversationMember($conv, $user);
        $member->setRole(ConversationMember::ROLE_OWNER);
        $this->assertSame(ConversationMember::ROLE_OWNER, $member->getRole());
    }

    public function testIsOwnerIsAdmin(): void
    {
        $conv = $this->createMockConversation();
        $user = $this->createMockUser();
        $owner = new ConversationMember($conv, $user);
        $owner->setRole(ConversationMember::ROLE_OWNER);
        $this->assertTrue($owner->isOwner());
        $this->assertFalse($owner->isAdmin());

        $admin = new ConversationMember($conv, $user);
        $admin->setRole(ConversationMember::ROLE_ADMIN);
        $this->assertFalse($admin->isOwner());
        $this->assertTrue($admin->isAdmin());
    }

    public function testToArray(): void
    {
        $conv = $this->createMockConversation('conv-1');
        $user = $this->createMockUser('user-1');
        $member = new ConversationMember($conv, $user);
        $member->setRole(ConversationMember::ROLE_ADMIN);
        $joinedAt = new \DateTime('2025-01-01 10:00:00');
        // Use reflection to set joinedAt since constructor sets it to now
        $ref = new \ReflectionProperty($member, 'joinedAt');
        $ref->setAccessible(true);
        $ref->setValue($member, $joinedAt);

        $arr = $member->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('conversationId', $arr);
        $this->assertArrayHasKey('userId', $arr);
        $this->assertArrayHasKey('role', $arr);
        $this->assertArrayHasKey('joinedAt', $arr);
        $this->assertSame('conv-1', $arr['conversationId']);
        $this->assertSame('user-1', $arr['userId']);
        $this->assertSame(ConversationMember::ROLE_ADMIN, $arr['role']);
        $this->assertSame('2025-01-01 10:00:00', $arr['joinedAt']);
    }
}
