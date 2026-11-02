<?php declare(strict_types=1);

namespace App\Tests\Unit\Core\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Core\Entity\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserTest extends TestCase
{
    public function testConstructorInitializesProperties(): void
    {
        $user = new User();
        $this->assertNotEmpty($user->getId());
        $this->assertIsString($user->getId());
        $this->assertInstanceOf(\DateTime::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $user->getUpdatedAt());
        $this->assertSame(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isSuspended());
        $this->assertFalse($user->isDeleted());
    }

    public function testSetEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->assertSame('test@example.com', $user->getEmail());
    }

    public function testSetPasswordHash(): void
    {
        $user = new User();
        $user->setPasswordHash('hashed_pw');
        $this->assertSame('hashed_pw', $user->getPasswordHash());
    }

    public function testStatusTransitions(): void
    {
        $user = new User();
        $user->setStatus(User::STATUS_INACTIVE);
        $this->assertFalse($user->isActive());
        $user->setStatus(User::STATUS_SUSPENDED);
        $this->assertTrue($user->isSuspended());
        $user->setStatus(User::STATUS_DELETED);
        $this->assertTrue($user->isDeleted());
    }

    public function testUpdateTimestampSetsUpdatedAt(): void
    {
        $user = new User();
        $originalUpdated = $user->getUpdatedAt();
        usleep(100000); // 0.1s
        $user->updateTimestamp();
        $newUpdated = $user->getUpdatedAt();
        $this->assertNotSame($originalUpdated, $newUpdated);
        $this->assertGreaterThanOrEqual($originalUpdated->getTimestamp(), $newUpdated->getTimestamp());
    }

    public function testToArrayReturnsExpectedFormat(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setStatus(User::STATUS_INACTIVE);
        $arr = $user->toArray();
        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame($user->getId(), $arr['id']);
        $this->assertArrayHasKey('email', $arr);
        $this->assertSame('user@example.com', $arr['email']);
        $this->assertArrayHasKey('status', $arr);
        $this->assertSame(User::STATUS_INACTIVE, $arr['status']);
        $this->assertArrayHasKey('createdAt', $arr);
        $this->assertSame($user->getCreatedAt()->format('Y-m-d H:i:s'), $arr['createdAt']);
        $this->assertArrayHasKey('updatedAt', $arr);
        $this->assertSame($user->getUpdatedAt()->format('Y-m-d H:i:s'), $arr['updatedAt']);
        $this->assertArrayNotHasKey('passwordHash', $arr);
    }
}
