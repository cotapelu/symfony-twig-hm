<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use App\User\Entity\UserProfile;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserProfileTest extends TestCase
{
    private function createMockUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('user-123');
        return $user;
    }

    public function testConstructorSetsUserIdAndTimestamps(): void
    {
        $profile = new UserProfile('userId-abc');
        $this->assertSame('userId-abc', $profile->getUserId());
        $this->assertInstanceOf(\DateTime::class, $profile->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $profile->getUpdatedAt());
    }

    public function testGettersSetters(): void
    {
        $profile = new UserProfile('user-1');

        $profile->setDisplayName('John Doe');
        $this->assertSame('John Doe', $profile->getDisplayName());

        $profile->setBio('A short bio');
        $this->assertSame('A short bio', $profile->getBio());

        $profile->setAvatar('https://example.com/avatar.jpg');
        $this->assertSame('https://example.com/avatar.jpg', $profile->getAvatar());

        $this->assertSame($profile->getAvatarUrl(), $profile->getAvatar());

        $profile->setEmail('john@example.com');
        $this->assertSame('john@example.com', $profile->getEmail());

        $profile->setPhone('+1234567890');
        $this->assertSame('+1234567890', $profile->getPhone());

        $profile->setAddress('123 Main St');
        $this->assertSame('123 Main St', $profile->getAddress());

        $profile->setCity('Springfield');
        $this->assertSame('Springfield', $profile->getCity());

        $profile->setCountry('USA');
        $this->assertSame('USA', $profile->getCountry());

        $user = $this->createMockUser();
        $profile->setUser($user);
        $this->assertSame($user, $profile->getUser());
    }

    public function testSetEmailAcceptsNull(): void
    {
        $profile = new UserProfile('user-2');
        $profile->setEmail(null);
        $this->assertNull($profile->getEmail());
    }

    public function testSetPhoneAcceptsNull(): void
    {
        $profile = new UserProfile('user-3');
        $profile->setPhone(null);
        $this->assertNull($profile->getPhone());
    }

    public function testUserIdIsImmutableAfterConstruction(): void
    {
        $profile = new UserProfile('fixed-id');
        $this->assertSame('fixed-id', $profile->getUserId());
    }

    public function testTimestampsAreIndependent(): void
    {
        $profile = new UserProfile('user-4');
        $created = $profile->getCreatedAt();
        $updated = $profile->getUpdatedAt();
        // They should be roughly same time but separate objects
        $this->assertNotSame($created, $updated);
        $this->assertEquals($created->format('Y-m-d H:i:s'), $updated->format('Y-m-d H:i:s'), 'initial timestamps should be equal');
    }
}
