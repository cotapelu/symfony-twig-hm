<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Entity;

use App\User\Entity\UserProfile;
use PHPUnit\Framework\TestCase;

class UserProfileTest extends TestCase
{
    public function testConstructorSetsUserId(): void
    {
        $profile = new UserProfile('user_123');

        $this->assertNull($profile->getId());
        $this->assertEquals('user_123', $profile->getUserId());
        $this->assertNull($profile->getDisplayName());
        $this->assertNull($profile->getBio());
        $this->assertNull($profile->getAvatar());
        $this->assertNull($profile->getEmail());
        $this->assertNull($profile->getPhone());
        $this->assertNull($profile->getAddress());
        $this->assertNull($profile->getCity());
        $this->assertNull($profile->getCountry());
    }

    public function testSetId(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setId(456);

        $this->assertEquals(456, $profile->getId());
    }

    public function testSetDisplayName(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setDisplayName('John Doe');

        $this->assertEquals('John Doe', $profile->getDisplayName());
    }

    public function testSetBio(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setBio('Software developer');

        $this->assertEquals('Software developer', $profile->getBio());
    }

    public function testSetAvatar(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setAvatar('/avatars/user.png');

        $this->assertEquals('/avatars/user.png', $profile->getAvatar());
    }

    public function testSetEmail(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setEmail('user@example.com');

        $this->assertEquals('user@example.com', $profile->getEmail());
    }

    public function testSetPhone(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setPhone('+849012345678');

        $this->assertEquals('+849012345678', $profile->getPhone());
    }

    public function testSetAddress(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setAddress('123 Main St');

        $this->assertEquals('123 Main St', $profile->getAddress());
    }

    public function testSetCity(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setCity('Hanoi');

        $this->assertEquals('Hanoi', $profile->getCity());
    }

    public function testSetCountry(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setCountry('Vietnam');

        $this->assertEquals('Vietnam', $profile->getCountry());
    }

    public function testSettersAreChainable(): void
    {
        $profile = new UserProfile('user_123');

        $result = $profile
            ->setDisplayName('John')
            ->setBio('Developer')
            ->setEmail('john@example.com');

        $this->assertSame($profile, $result);
    }

    public function testAllFieldsCanBeSet(): void
    {
        $profile = new UserProfile('user_123');
        $profile
            ->setDisplayName('John Doe')
            ->setBio('Software developer')
            ->setAvatar('/avatars/user.png')
            ->setEmail('john@example.com')
            ->setPhone('+849012345678')
            ->setAddress('123 Main St, Hanoi')
            ->setCity('Hanoi')
            ->setCountry('Vietnam');

        $this->assertEquals('John Doe', $profile->getDisplayName());
        $this->assertEquals('Software developer', $profile->getBio());
        $this->assertEquals('/avatars/user.png', $profile->getAvatar());
        $this->assertEquals('john@example.com', $profile->getEmail());
        $this->assertEquals('+849012345678', $profile->getPhone());
        $this->assertEquals('123 Main St, Hanoi', $profile->getAddress());
        $this->assertEquals('Hanoi', $profile->getCity());
        $this->assertEquals('Vietnam', $profile->getCountry());
    }
}
