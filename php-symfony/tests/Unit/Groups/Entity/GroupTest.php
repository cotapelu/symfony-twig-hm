<?php declare(strict_types=1);

namespace App\Tests\Unit\Groups\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Groups\Entity\Group;
use App\Groups\Entity\GroupMember;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GroupTest extends TestCase
{
    private function createBasicGroup(?string $name = null, ?string $ownerId = null): Group
    {
        return new Group(
            name: $name ?? 'Test Group',
            description: null,
            avatar: null,
            isPublic: false,
            isCommunity: false,
            isChannel: false,
            isForum: false,
            maxMembers: 500,
            settings: null,
            ownerId: $ownerId ?? 'owner-123'
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $group = $this->createBasicGroup();
        $this->assertIsString($group->getId());
        $this->assertSame(32, strlen($group->getId())); // hex random 16 bytes
        $this->assertSame('owner-123', $group->getOwnerId());
        $this->assertSame('Test Group', $group->getName());
        $this->assertNull($group->getDescription());
        $this->assertNull($group->getAvatar());
        $this->assertFalse($group->isPublic());
        $this->assertFalse($group->isCommunity());
        $this->assertFalse($group->isChannel());
        $this->assertFalse($group->isForum());
        $this->assertSame(500, $group->getMaxMembers());
        $this->assertIsArray($group->getSettings());
        $this->assertArrayHasKey('allowInvites', $group->getSettings());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $group->getMembers());
        $this->assertEmpty($group->getMembers()->toArray());
        $this->assertTrue($group->isActive());
    }

    public function testGettersAndSetters(): void
    {
        $group = $this->createBasicGroup();

        $group->setName('New Group');
        $this->assertSame('New Group', $group->getName());

        $group->setDescription('A group');
        $this->assertSame('A group', $group->getDescription());

        $group->setAvatar('avatar.jpg');
        $this->assertSame('avatar.jpg', $group->getAvatar());

        $group->setPublic(true);
        $this->assertTrue($group->isPublic());

        $group->setCommunity(true);
        $this->assertTrue($group->isCommunity());

        $group->setChannel(true);
        $this->assertTrue($group->isChannel());

        $group->setForum(true);
        $this->assertTrue($group->isForum());

        $group->setMaxMembers(1000);
        $this->assertSame(1000, $group->getMaxMembers());

        $group->setSettings(['allowInvites' => false]);
        $this->assertSame(['allowInvites' => false], $group->getSettings());

        $group->setOwnerId('owner-999');
        $this->assertSame('owner-999', $group->getOwnerId());

        $group->setActive(false);
        $this->assertFalse($group->isActive());
    }

    public function testMemberCollection(): void
    {
        $group = $this->createBasicGroup();
        $member = $this->createMock(GroupMember::class);
        $group->addMember($member);
        $this->assertCount(1, $group->getMembers());
        $this->assertTrue($group->getMembers()->contains($member));

        $group->removeMember($member);
        $this->assertCount(0, $group->getMembers());
    }

    public function testGetMemberCount(): void
    {
        $group = $this->createBasicGroup();
        $this->assertSame(0, $group->getMemberCount());

        $member1 = $this->createMock(GroupMember::class);
        $member2 = $this->createMock(GroupMember::class);
        $group->addMember($member1);
        $group->addMember($member2);
        $this->assertSame(2, $group->getMemberCount());
    }
}
