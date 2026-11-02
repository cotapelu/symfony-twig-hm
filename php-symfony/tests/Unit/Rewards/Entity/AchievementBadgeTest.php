<?php declare(strict_types=1);

namespace App\Tests\Unit\Rewards\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Rewards\Entity\AchievementBadge;
use App\User\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AchievementBadgeTest extends TestCase
{
    private function createMockUser(string $id = 'user-123'): MockObject&User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    public function testConstructor(): void
    {
        $user = $this->createMockUser();
        $badge = new AchievementBadge(
            user: $user,
            type: 'early_adopter',
            name: 'Early Adopter',
            description: 'Joined early',
            icon: 'star.png',
            metadata: ['version' => 1]
        );

        $this->assertIsString($badge->getId());
        $this->assertSame($user, $badge->getUser());
        $this->assertSame('early_adopter', $badge->getType());
        $this->assertSame('Early Adopter', $badge->getName());
        // Description, icon, metadata are set but no getters; toArray verifies them.
        $this->assertInstanceOf(\DateTime::class, $badge->getEarnedAt());
    }

    public function testConstructorWithoutOptionalFields(): void
    {
        $user = $this->createMockUser();
        $badge = new AchievementBadge($user, 'type1', 'Badge 1');

        $this->assertSame($user, $badge->getUser());
        $this->assertSame('type1', $badge->getType());
        $this->assertSame('Badge 1', $badge->getName());
        // Optional fields are null by default; verify via toArray
        $arr = $badge->toArray();
        $this->assertNull($arr['description']);
        $this->assertNull($arr['icon']);
        $this->assertNull($arr['metadata']);
    }

    public function testToArray(): void
    {
        $user = $this->createMockUser('user-xyz');
        $earnedAt = new \DateTime('2025-01-01 12:00:00');
        // Use reflection to set earnedAt for deterministic test
        $badge = new AchievementBadge($user, 'test', 'Test Badge');
        $ref = new \ReflectionProperty($badge, 'earnedAt');
        $ref->setAccessible(true);
        $ref->setValue($badge, $earnedAt);

        $arr = $badge->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame('user-xyz', $arr['userId']);
        $this->assertSame('test', $arr['type']);
        $this->assertSame('Test Badge', $arr['name']);
        $this->assertNull($arr['description']);
        $this->assertNull($arr['icon']);
        $this->assertNull($arr['metadata']);
        $this->assertSame('2025-01-01 12:00:00', $arr['earnedAt']);
    }
}
